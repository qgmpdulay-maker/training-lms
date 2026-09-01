<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Generic live-search wiring for any form marked data-live-form — fetches
        // its own section's results partial and swaps it in, no page reload. Mirrors
        // the pattern already used on the Summary tab, generalized so multiple
        // independent search boxes on one page (e.g. Manage Admins) can each fire
        // on their own while keeping each other's hidden mirror fields in sync,
        // without needing a page-specific state object.

        function fieldValue(el) {
            if (el.type === 'checkbox') {
                return el.checked ? (el.value || '1') : '';
            }
            return el.value;
        }

        function propagateToOtherForms(sourceForm) {
            document.querySelectorAll('form[data-live-form]').forEach(function (otherForm) {
                if (otherForm === sourceForm) {
                    return;
                }
                Array.from(sourceForm.elements).forEach(function (el) {
                    // _section identifies which results partial a form's own
                    // submit should get back — it's fixed per form, never shared
                    // state, so it must never be copied onto another form.
                    if (!el.name || el.name === '_section' || el.tagName === 'BUTTON' || el.type === 'submit') {
                        return;
                    }
                    const field = otherForm.elements.namedItem(el.name);
                    if (!field || field.tagName === 'SELECT') {
                        return;
                    }
                    field.value = fieldValue(el);
                });
            });
        }

        function wireLiveSearch(form) {
            const target = document.getElementById(form.dataset.liveTarget);
            const section = form.dataset.liveSection;
            if (!target || !section) {
                return;
            }

            let debounceTimer;

            function applyResponse(html, url) {
                target.innerHTML = html;
                window.history.replaceState({}, '', url);
            }

            function request(url) {
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (response) { return response.text(); })
                    .then(function (html) { applyResponse(html, url); });
            }

            function submitLive() {
                propagateToOtherForms(form);
                const params = new URLSearchParams(new FormData(form));
                request(form.action.split('#')[0] + '?' + params.toString() + '#' + section);
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                clearTimeout(debounceTimer);
                submitLive();
            });

            form.querySelectorAll('input[type="text"]').forEach(function (input) {
                input.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(submitLive, 350);
                });
            });

            form.querySelectorAll('select, input[type="checkbox"]').forEach(function (control) {
                control.addEventListener('change', function () {
                    clearTimeout(debounceTimer);
                    submitLive();
                });
            });

            target.addEventListener('click', function (event) {
                const link = event.target.closest('.' + section + '-pagination a[href]');
                if (!link) {
                    return;
                }
                event.preventDefault();
                const url = new URL(link.href, window.location.origin);
                url.searchParams.set('_section', section);
                request(url.toString());
            });
        }

        document.querySelectorAll('form[data-live-form]').forEach(wireLiveSearch);
    });
</script>
