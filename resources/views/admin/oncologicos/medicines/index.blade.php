<x-admin-layout>
    <div class="mt-2 mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-medium text-gray-800">Lista de precios del hospital</h1>

        <a href="{{ route('admin.oncologicos.medicines.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            + Crear Lista
        </a>
    </div>

    <div class="relative overflow-x-auto">
        <livewire:oncologicos.medicine-lists-table />
    </div>

    @push('js')
        <script>
            // ✅ Delegación: funciona aunque Livewire re-renderice
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-eliminar');
                if (!btn) return;

                const id = btn.getAttribute('data-id');

                Swal.fire({
                    title: '¿Eliminar lista?',
                    text: "Esta acción no se puede deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    customClass: {
                        confirmButton: 'swal-button-confirm',
                        cancelButton: 'swal-button-cancel'
                    },
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('delete-form-' + id);
                        if (form) form.submit();
                    }
                });
            }, true);
        </script>
    @endpush
</x-admin-layout>
