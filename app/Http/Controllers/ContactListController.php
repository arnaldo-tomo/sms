<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactListRequest;
use App\Models\ContactList;
use Illuminate\Http\RedirectResponse;

class ContactListController extends Controller
{
    public function store(StoreContactListRequest $request): RedirectResponse
    {
        ContactList::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Grupo criado.');
    }

    public function update(StoreContactListRequest $request, ContactList $list): RedirectResponse
    {
        $list->update($request->validated());

        return back()->with('success', 'Grupo atualizado.');
    }

    public function destroy(ContactList $list): RedirectResponse
    {
        $this->authorize('delete', $list);

        $list->delete();

        return back()->with('success', 'Grupo eliminado.');
    }
}
