<?php

namespace App\Repositories;

use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MessageRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Message>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Message::query()
            ->with(['device:id,name,phone_number', 'contact:id,name', 'user:id,name'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('to_number', 'like', "%{$search}%")
                        ->orWhere('from_number', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['direction'] ?? null, fn ($q, $dir) => $q->where('direction', $dir))
            ->when($filters['from_date'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to_date'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Contagens por estado para os cartões do dashboard.
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = Message::query()
            ->outbound()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $pending = ($counts[Message::STATUS_PENDING] ?? 0)
            + ($counts[Message::STATUS_SCHEDULED] ?? 0)
            + ($counts[Message::STATUS_QUEUED] ?? 0)
            + ($counts[Message::STATUS_SENDING] ?? 0);

        $sent = ($counts[Message::STATUS_SENT] ?? 0)
            + ($counts[Message::STATUS_DELIVERED] ?? 0);

        $failed = ($counts[Message::STATUS_FAILED] ?? 0)
            + ($counts[Message::STATUS_EXPIRED] ?? 0);

        return [
            'sent' => $sent,
            'delivered' => $counts[Message::STATUS_DELIVERED] ?? 0,
            'pending' => $pending,
            'failed' => $failed,
            'total' => array_sum($counts),
        ];
    }

    /**
     * Utilização diária dos últimos N dias para os gráficos.
     *
     * @return array<int, array{date: string, sent: int, delivered: int, failed: int}>
     */
    public function dailyUsage(int $days = 14): array
    {
        $since = now()->subDays($days - 1)->startOfDay();

        $rows = Message::query()
            ->outbound()
            ->where('created_at', '>=', $since)
            ->get(['status', 'created_at']);

        $buckets = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $buckets[$date] = ['date' => $date, 'sent' => 0, 'delivered' => 0, 'failed' => 0];
        }

        foreach ($rows as $row) {
            $date = Carbon::parse($row->created_at)->toDateString();
            if (! isset($buckets[$date])) {
                continue;
            }
            if (in_array($row->status, [Message::STATUS_FAILED, Message::STATUS_EXPIRED], true)) {
                $buckets[$date]['failed']++;
            } elseif ($row->status === Message::STATUS_DELIVERED) {
                $buckets[$date]['delivered']++;
                $buckets[$date]['sent']++;
            } elseif (in_array($row->status, [Message::STATUS_SENT], true)) {
                $buckets[$date]['sent']++;
            }
        }

        return array_values($buckets);
    }
}
