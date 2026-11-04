@if (session()->has('nutrition_duplicate_confirmation'))
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', async () => {
                const duplicate = @json(session('nutrition_duplicate_confirmation'));
                const form = document.getElementById(duplicate.form_id);
                if (!form || !window.Swal) return;

                const content = document.createElement('div');
                content.className = 'space-y-2 text-left';

                const message = document.createElement('p');
                message.className = 'mb-3';
                message.textContent = duplicate.message;
                content.append(message);

                const details = document.createElement('dl');
                details.className = 'grid grid-cols-[max-content_1fr] gap-x-3 gap-y-1 rounded border border-amber-200 bg-amber-50 p-3';
                [
                    ['Solicitud previa', `#${duplicate.request.folio}`],
                    ['Paciente', duplicate.request.patient],
                    ['Entrega', duplicate.request.delivery_at],
                    ['Estado', duplicate.request.status],
                    ...(duplicate.request.lot ? [['Lote', duplicate.request.lot]] : []),
                ].forEach(([label, value]) => {
                    const term = document.createElement('dt');
                    term.className = 'font-semibold';
                    term.textContent = `${label}:`;
                    const description = document.createElement('dd');
                    description.textContent = value || 'No disponible';
                    details.append(term, description);
                });
                content.append(details);

                const result = await Swal.fire({
                    title: duplicate.title,
                    html: content,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: duplicate.confirm_text,
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                    allowOutsideClick: false,
                });

                if (!result.isConfirmed) return;

                let confirmation = form.querySelector(`input[name="${duplicate.confirmation_field}"]`);
                if (!confirmation) {
                    confirmation = document.createElement('input');
                    confirmation.type = 'hidden';
                    confirmation.name = duplicate.confirmation_field;
                    form.append(confirmation);
                }
                confirmation.value = '1';

                if (duplicate.action) {
                    const action = form.querySelector('[name="accion"]');
                    if (action) action.value = duplicate.action;
                }

                form.submit();
            });
        </script>
    @endpush
@endif
