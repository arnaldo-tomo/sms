<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendMessageRequest;
use App\Http\Resources\Api\ApiMessageResource;
use App\Models\Company;
use App\Models\Device;
use App\Services\HttpSms\HttpSmsClient;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly HttpSmsClient $platform,
    ) {
    }

    private function company(Request $request): Company
    {
        return $request->attributes->get('company');
    }

    /**
     * Resolve o número de origem a partir do POOL da plataforma.
     * Se a empresa tiver números atribuídos, escolhe entre esses; caso
     * contrário pode usar qualquer número ativo da plataforma.
     */
    private function resolveSenderNumber(Request $request, Company $company): ?Device
    {
        // Pool da empresa: os seus números dedicados + o pool partilhado (sem dono).
        $pool = fn () => Device::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('company_id', $company->id)->orWhereNull('company_id'));

        if ($request->filled('from')) {
            return $pool()->where('phone_number', $request->string('from'))->first();
        }

        // Auto-seleção: prefere os dedicados e os que estão online.
        return $pool()->orderByRaw('company_id is null')->where('status', 'online')->first()
            ?? $pool()->orderByRaw('company_id is null')->first();
    }

    /**
     * POST /api/v1/sms — envia um ou vários SMS.
     */
    public function store(SendMessageRequest $request): JsonResponse
    {
        $company = $this->company($request);

        // Verifica que a PLATAFORMA tem a integração httpSMS configurada.
        if (! $this->platform->isConfigured()) {
            return response()->json([
                'error' => 'platform_not_configured',
                'message' => 'O serviço de SMS não está disponível de momento.',
            ], 503);
        }

        // Número de origem: escolhido pela empresa, do POOL de números da plataforma.
        // Se não for indicado, o sistema escolhe um (prefere um que esteja online).
        $device = $this->resolveSenderNumber($request, $company);

        if (! $device) {
            return response()->json([
                'error' => 'no_sender_number',
                'message' => $request->filled('from')
                    ? "O número '{$request->string('from')}' não está disponível."
                    : 'Não há nenhum número disponível para enviar.',
            ], 422);
        }

        $recipients = collect($request->recipients())->map(fn ($to) => ['to' => $to]);

        $messages = $this->sms->dispatchBulk(
            recipients: $recipients,
            content: $request->string('content'),
            deviceId: $device->id,
            from: $device->phone_number,
            company: $company,
            source: 'api',
        );

        // Um destinatário → objeto único; vários → coleção (estilo batch).
        if ($messages->count() === 1) {
            return ApiMessageResource::make($messages->first())
                ->response()
                ->setStatusCode(201);
        }

        return response()->json([
            'count' => $messages->count(),
            'messages' => ApiMessageResource::collection($messages),
        ], 201);
    }

    /**
     * GET /api/v1/sms/{id} — estado de uma mensagem (pelo uuid público).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $message = $this->company($request)->messages()->where('uuid', $id)->first();

        if (! $message) {
            return response()->json(['error' => 'not_found', 'message' => 'Mensagem não encontrada.'], 404);
        }

        return ApiMessageResource::make($message)->response();
    }

    /**
     * GET /api/v1/sms — lista as mensagens da empresa.
     */
    public function index(Request $request): JsonResponse
    {
        $messages = $this->company($request)
            ->messages()
            ->latest()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->paginate(min(100, (int) $request->integer('per_page', 25)));

        return ApiMessageResource::collection($messages)->response();
    }
}
