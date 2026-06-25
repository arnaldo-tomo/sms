<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendSmsRequest;
use App\Http\Resources\ContactListResource;
use App\Http\Resources\DeviceResource;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Device;
use App\Repositories\ContactRepository;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SendController extends Controller
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly ContactRepository $contacts,
    ) {
    }

    public function create(): Response
    {
        $this->authorize('create', \App\Models\Message::class);

        return Inertia::render('Messages/Send', [
            'devices' => DeviceResource::collection(Device::where('is_active', true)->get()),
            'lists' => ContactListResource::collection(ContactList::withCount('contacts')->get()),
            'contacts' => Contact::orderBy('name')->get(['id', 'name', 'phone_number']),
        ]);
    }

    public function store(SendSmsRequest $request): RedirectResponse
    {
        $recipients = $this->resolveRecipients($request);

        if ($recipients->isEmpty()) {
            return back()->with('error', 'Nenhum destinatário válido foi encontrado.');
        }

        $scheduledAt = $request->filled('scheduled_at')
            ? Carbon::parse($request->input('scheduled_at'))
            : null;

        $messages = $this->sms->dispatchBulk(
            recipients: $recipients,
            content: $request->string('content'),
            deviceId: $request->integer('device_id') ?: null,
            from: $request->input('from'),
            scheduledAt: $scheduledAt,
            user: $request->user(),
        );

        $verb = $scheduledAt ? 'agendada(s)' : 'colocada(s) na fila';

        return redirect()
            ->route('messages.index')
            ->with('success', "{$messages->count()} mensagem(ns) {$verb} com sucesso.");
    }

    /**
     * Reúne e deduplica os destinatários a partir de números, contactos e grupos.
     *
     * @return Collection<int, array{to: string, contact_id: int|null}>
     */
    private function resolveRecipients(SendSmsRequest $request): Collection
    {
        $recipients = collect();

        foreach ($request->input('recipients', []) as $number) {
            $number = trim((string) $number);
            if ($number !== '') {
                $recipients->push(['to' => $number, 'contact_id' => null]);
            }
        }

        if ($ids = $request->input('contact_ids', [])) {
            Contact::whereIn('id', $ids)->get()->each(function (Contact $c) use ($recipients) {
                $recipients->push(['to' => $c->phone_number, 'contact_id' => $c->id]);
            });
        }

        if ($listIds = $request->input('list_ids', [])) {
            $this->contacts->fromLists($listIds)->each(function (Contact $c) use ($recipients) {
                $recipients->push(['to' => $c->phone_number, 'contact_id' => $c->id]);
            });
        }

        return $recipients->unique('to')->values();
    }
}
