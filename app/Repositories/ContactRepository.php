<?php

namespace App\Repositories;

use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ContactRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Contact>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Contact::query()
            ->with('lists:id,name,color')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['list_id'] ?? null, function ($q, $listId) {
                $q->whereHas('lists', fn ($q) => $q->where('contact_lists.id', $listId));
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Resolve uma coleção de contactos a partir de IDs de listas (para envio em massa).
     *
     * @param  array<int, int>  $listIds
     * @return Collection<int, Contact>
     */
    public function fromLists(array $listIds): Collection
    {
        return Contact::query()
            ->whereHas('lists', fn ($q) => $q->whereIn('contact_lists.id', $listIds))
            ->get()
            ->unique('phone_number')
            ->values();
    }
}
