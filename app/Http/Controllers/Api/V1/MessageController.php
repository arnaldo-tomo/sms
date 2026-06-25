<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendMessageRequest;
use App\Http\Resources\Api\ApiMessageResource;
use App\Models\Company;
use App\Models\Message;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private readonly SmsService $sms)
    {
    }

    private function company(Request $request): Company
    {
        return $request->attributes->get('company');
    }

    /**
     * POST /api/v1/sms — envia um ou vários SMS.
     */
    public function store(SendMessageRequest $request): JsonResponse
    {
        $company = $this->company($request);

        if (! $company->isConfigured()) {
            return response()->json([
                'error' => 'company_not_configured',
                'message' => 'A empresa não tem a integração httpSMS configurada (API key em falta).',
            ], 422);
        }

        // Resolve o número de origem: o indicado, ou o primeiro dispositivo da empresa.
        $device = $request->filled('from')
            ? $company->devices()->where('phone_number', $request->string('from'))->first()
            : $company->devices()->where('is_active', true)->first();

        if (! $device) {
            return response()->json([
                'error' => 'no_sender_number',
                'message' => $request->filled('from')
                    ? "O número '{$request->string('from')}' não pertence a esta empresa."
                    : 'A empresa não tem nenhum número/dispositivo associado. Sincroniza um dispositivo primeiro.',
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
