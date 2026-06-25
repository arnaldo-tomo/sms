<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Users/Index', [
            'users' => UserResource::collection(User::with('roles')->latest()->paginate(15)),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($request->input('roles'));

        return back()->with('success', 'Utilizador criado.');
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            ...($request->filled('password') ? ['password' => Hash::make($request->string('password'))] : []),
        ]);

        $user->syncRoles($request->input('roles'));

        return back()->with('success', 'Utilizador atualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return back()->with('success', 'Utilizador eliminado.');
    }
}
