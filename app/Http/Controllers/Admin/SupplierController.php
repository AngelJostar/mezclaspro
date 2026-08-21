<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    private const DOCUMENT_FIELDS = [
        'tax_certificate',
        'bank_cover',
        'additional_document',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));
        $status = trim((string) $request->query('status', ''));

        $suppliers = Supplier::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        $categories = Supplier::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.suppliers.index', compact(
            'suppliers',
            'categories',
            'search',
            'category',
            'status'
        ));
    }

    public function create(): View
    {
        return view('admin.suppliers.create', $this->formViewData(new Supplier()));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $documents = Arr::only($validated, self::DOCUMENT_FIELDS);

        $supplier = Supplier::create([
            ...Arr::except($validated, self::DOCUMENT_FIELDS),
            'created_by' => $request->user()?->id,
        ]);

        $this->storeDocuments($supplier, $documents);

        return redirect()
            ->route('admin.suppliers.show', $supplier)
            ->with('success', 'Proveedor registrado correctamente.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load([
            'creator:id,name,lastname',
            'assignedBuyer:id,name,lastname,username',
        ]);

        return view('admin.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', $this->formViewData($supplier));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $this->validatedData($request, $supplier);
        $documents = Arr::only($validated, self::DOCUMENT_FIELDS);

        $supplier->update(Arr::except($validated, self::DOCUMENT_FIELDS));
        $this->storeDocuments($supplier, $documents);

        return redirect()
            ->route('admin.suppliers.show', $supplier)
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    private function validatedData(Request $request, ?Supplier $supplier = null): array
    {
        $validated = $request->validate([
            'assigned_buyer_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereNull('hospital_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'commercial_name' => ['nullable', 'string', 'max:255'],
            'rfc' => [
                'required',
                'string',
                'max:20',
                Rule::unique('suppliers', 'rfc')->ignore($supplier),
            ],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'fax' => ['nullable', 'string', 'max:40'],
            'category' => ['required', 'string', 'max:80'],
            'subcategory' => ['required', 'string', 'max:80'],
            'location' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:150'],
            'postal_code' => ['required', 'string', 'max:10'],
            'address' => ['required', 'string', 'max:2000'],
            'bank_name' => ['required', 'string', 'max:120'],
            'bank_account' => ['required', 'string', 'max:50'],
            'bank_clabe' => ['required', 'regex:/^\d{18}$/'],
            'bank_reference' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Supplier::statuses()))],
            'tax_certificate' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'bank_cover' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'additional_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['rfc'] = filled($validated['rfc'] ?? null)
            ? Str::upper(trim($validated['rfc']))
            : null;

        foreach ([
            'commercial_name',
            'contact_name',
            'phone',
            'email',
            'fax',
            'category',
            'subcategory',
            'location',
            'municipality',
            'postal_code',
            'address',
            'bank_name',
            'bank_account',
            'bank_clabe',
            'bank_reference',
        ] as $field) {
            $validated[$field] = filled($validated[$field] ?? null)
                ? trim($validated[$field])
                : null;
        }

        $validated['bank_details'] = collect([
            'Banco' => $validated['bank_name'],
            'Cuenta' => $validated['bank_account'],
            'CLABE' => $validated['bank_clabe'],
            'Referencia' => $validated['bank_reference'],
        ])->map(fn ($value, $label) => "{$label}: {$value}")
            ->implode(PHP_EOL);

        return $validated;
    }

    private function formViewData(Supplier $supplier): array
    {
        return [
            'supplier' => $supplier,
            'statuses' => Supplier::statuses(),
            'buyers' => User::query()
                ->whereNull('hospital_id')
                ->where('is_active', true)
                ->orderBy('name')
                ->orderBy('lastname')
                ->get(['id', 'name', 'lastname', 'username']),
        ];
    }

    /** @param  array<string, UploadedFile|null>  $documents */
    private function storeDocuments(Supplier $supplier, array $documents): void
    {
        $pathByInput = [
            'tax_certificate' => 'tax_certificate_path',
            'bank_cover' => 'bank_cover_path',
            'additional_document' => 'additional_document_path',
        ];

        $updates = [];

        foreach ($pathByInput as $input => $column) {
            $file = $documents[$input] ?? null;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            if ($supplier->{$column}) {
                Storage::disk('local')->delete($supplier->{$column});
            }

            $updates[$column] = $file->store("suppliers/{$supplier->id}", 'local');
        }

        if ($updates !== []) {
            $supplier->update($updates);
        }
    }
}
