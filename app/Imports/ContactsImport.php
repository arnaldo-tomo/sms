<?php

namespace App\Imports;

use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importa contactos de um ficheiro Excel/CSV.
 * Colunas esperadas (cabeçalho): name, phone_number (ou phone), email, notes.
 */
class ContactsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped = 0;

    public function __construct(private readonly ?ContactList $list = null)
    {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $phone = $this->normalizePhone(
                $row['phone_number'] ?? ($row['phone'] ?? ($row['telefone'] ?? null))
            );
            $name = trim((string) ($row['name'] ?? ($row['nome'] ?? '')));

            if (! $phone) {
                $this->skipped++;

                continue;
            }

            $contact = Contact::updateOrCreate(
                ['phone_number' => $phone],
                [
                    'name' => $name !== '' ? $name : $phone,
                    'email' => $row['email'] ?? null,
                    'notes' => $row['notes'] ?? ($row['notas'] ?? null),
                    'user_id' => Auth::id(),
                ]
            );

            if ($this->list) {
                $contact->lists()->syncWithoutDetaching([$this->list->id]);
            }

            $this->imported++;
        }
    }

    private function normalizePhone(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = trim((string) $value);
        $value = preg_replace('/[\s\-()]/', '', $value);

        if (! str_starts_with($value, '+') && ctype_digit($value)) {
            return $value;
        }

        return $value;
    }
}
