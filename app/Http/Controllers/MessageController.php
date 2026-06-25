<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Repositories\MessageRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function __construct(private readonly MessageRepository $messages)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Message::class);

        $filters = $request->only(['search', 'status', 'direction', 'from_date', 'to_date']);

        return Inertia::render('Messages/Index', [
            'messages' => MessageResource::collection($this->messages->paginate($filters)),
            'filters' => $filters,
            'statuses' => [
                Message::STATUS_PENDING,
                Message::STATUS_SCHEDULED,
                Message::STATUS_QUEUED,
                Message::STATUS_SENDING,
                Message::STATUS_SENT,
                Message::STATUS_DELIVERED,
                Message::STATUS_FAILED,
                Message::STATUS_EXPIRED,
                Message::STATUS_RECEIVED,
            ],
        ]);
    }

    public function show(Message $message): Response
    {
        $this->authorize('view', $message);

        $message->load(['device', 'contact', 'user']);

        return Inertia::render('Messages/Show', [
            'message' => new MessageResource($message),
        ]);
    }
}
