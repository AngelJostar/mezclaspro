@once
    <style>
        [data-nutrition-request-form] input[name^="i_"]:-webkit-autofill,
        [data-nutrition-request-form] input[name^="nutrition_diluents"]:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
            -webkit-text-fill-color: inherit !important;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-nutrition-request-form]').forEach((form) => {
                const componentFields = () => form.querySelectorAll(
                    'input[name^="i_"], input[name^="nutrition_diluents"]'
                );

                componentFields().forEach((field) => field.setAttribute('autocomplete', 'off'));

                const clearUnexpectedComponentAutofill = () => {
                    componentFields().forEach((field) => {
                        let browserAutofilled = false;
                        try {
                            browserAutofilled = field.matches(':-webkit-autofill');
                        } catch (error) {
                            return;
                        }
                        if (field.defaultValue !== '' || !browserAutofilled) return;

                        field.value = '';
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                };

                form.addEventListener('input', () => {
                    window.setTimeout(clearUnexpectedComponentAutofill, 0);
                    window.setTimeout(clearUnexpectedComponentAutofill, 150);
                });

                [0, 100, 500, 1200].forEach((delay) => {
                    window.setTimeout(clearUnexpectedComponentAutofill, delay);
                });
            });
        });
    </script>
@endonce
