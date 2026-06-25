<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('companies.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('companies.manage');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can('companies.manage');
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can('companies.manage');
    }
}
