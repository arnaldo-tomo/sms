<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('audit.view');

        $logs = Activity::query()
            ->with('causer:id,name')
            ->when($request->input('search'), function ($q, $search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Activity $a) => [
                'id' => $a->id,
                'log_name' => $a->log_name,
                'description' => $a->description,
                'event' => $a->event,
                'subject_type' => class_basename((string) $a->subject_type),
                'subject_id' => $a->subject_id,
                'causer' => $a->causer?->name,
                'properties' => $a->properties,
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return Inertia::render('AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only('search'),
        ]);
    }
}
