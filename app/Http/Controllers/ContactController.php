<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\ContactListResource;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\ContactList;
use App\Repositories\ContactRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(private readonly ContactRepository $contacts)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Contact::class);

        $filters = $request->only(['search', 'list_id']);

        return Inertia::render('Contacts/Index', [
            'contacts' => ContactResource::collection($this->contacts->paginate($filters)),
            'lists' => ContactListResource::collection(ContactList::withCount('contacts')->get()),
            'filters' => $filters,
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $contact = Contact::create([
            ...$request->safe()->except('list_ids'),
            'user_id' => $request->user()->id,
        ]);

        $contact->lists()->sync($request->input('list_ids', []));

        return back()->with('success', 'Contacto criado com sucesso.');
    }

    public function update(StoreContactRequest $request, Contact $contact): RedirectResponse
    {
        $contact->update($request->safe()->except('list_ids'));
        $contact->lists()->sync($request->input('list_ids', []));

        return back()->with('success', 'Contacto atualizado.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return back()->with('success', 'Contacto eliminado.');
    }
}
