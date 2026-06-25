<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('messages.view');
    }

    public function view(User $user, Message $message): bool
    {
        return $user->can('messages.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sms.send');
    }

    public function delete(User $user, Message $message): bool
    {
        return $user->can('messages.view');
    }
}
