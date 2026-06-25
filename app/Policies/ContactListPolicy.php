<?php

namespace App\Policies;

use App\Models\ContactList;
use App\Models\User;

class ContactListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contacts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('contacts.manage');
    }

    public function update(User $user, ContactList $list): bool
    {
        return $user->can('contacts.manage');
    }

    public function delete(User $user, ContactList $list): bool
    {
        return $user->can('contacts.manage');
    }
}
