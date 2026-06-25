<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\ContactList;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Administrador inicial
        $admin = User::firstOrCreate(
            ['email' => 'admin@smsgateway.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles('admin');

        $operator = User::firstOrCreate(
            ['email' => 'operador@smsgateway.local'],
            [
                'name' => 'Operador',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $operator->syncRoles('operator');

        // Dados de demonstração
        if (Contact::count() === 0) {
            $vip = ContactList::create(['name' => 'Clientes VIP', 'color' => '#6366f1', 'user_id' => $admin->id]);
            $news = ContactList::create(['name' => 'Newsletter', 'color' => '#10b981', 'user_id' => $admin->id]);

            $contacts = [
                ['name' => 'João Mondlane', 'phone_number' => '+258840000001'],
                ['name' => 'Ana Sitoe', 'phone_number' => '+258840000002'],
                ['name' => 'Carlos Tembe', 'phone_number' => '+258840000003'],
                ['name' => 'Marta Chissano', 'phone_number' => '+258840000004'],
            ];

            foreach ($contacts as $i => $data) {
                $contact = Contact::create([...$data, 'user_id' => $admin->id]);
                $contact->lists()->attach($i % 2 === 0 ? $vip->id : $news->id);
            }
        }

        // Os dispositivos são importados do httpSMS via "Dispositivos → Sincronizar",
        // por isso não criamos nenhum dispositivo de demonstração aqui.
    }
}
