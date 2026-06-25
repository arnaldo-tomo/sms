<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportContactsRequest;
use App\Imports\ContactsImport;
use App\Models\ContactList;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;

class ContactImportController extends Controller
{
    public function store(ImportContactsRequest $request): RedirectResponse
    {
        $list = $request->filled('list_id')
            ? ContactList::find($request->integer('list_id'))
            : null;

        $import = new ContactsImport($list);
        Excel::import($import, $request->file('file'));

        return back()->with(
            'success',
            "Importação concluída: {$import->imported} contactos importados, {$import->skipped} ignorados."
        );
    }
}
