import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/*
|--------------------------------------------------------------------------
| CC-Flota — Filtros multiselección estándar
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {
    const multiselects = document.querySelectorAll('[data-cc-filter-multiselect]');

    multiselects.forEach(function (multiselect) {
        const toggle = multiselect.querySelector('[data-cc-filter-toggle]');
        const menu = multiselect.querySelector('[data-cc-filter-menu]');
        const label = multiselect.querySelector('[data-cc-filter-label]');
        const search = multiselect.querySelector('[data-cc-filter-search]');
        const checkAll = multiselect.querySelector('[data-cc-filter-check-all]');
        const checkboxes = Array.from(multiselect.querySelectorAll('[data-cc-filter-checkbox]'));
        const options = Array.from(multiselect.querySelectorAll('[data-cc-filter-option]'));

        if (! toggle || ! menu || ! label || ! checkAll || checkboxes.length === 0) {
            return;
        }

        const allText = multiselect.dataset.allText || 'Todos';
        const singularText = multiselect.dataset.singularText || 'seleccionado';
        const pluralText = multiselect.dataset.pluralText || 'seleccionados';

        function normalizeText(value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();
        }

        function getSelectedCheckboxes() {
            return checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            });
        }

        function updateLabel() {
            const selected = getSelectedCheckboxes();

            if (selected.length === 0 || selected.length === checkboxes.length) {
                label.textContent = allText;
                checkAll.checked = true;
                checkAll.indeterminate = false;
                return;
            }

            checkAll.checked = false;
            checkAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;

            if (selected.length === 1) {
                const selectedOption = selected[0].closest('[data-cc-filter-option]');
                const selectedText = selectedOption
                    ? selectedOption.querySelector('[data-cc-filter-option-label]')
                    : null;

                label.textContent = selectedText
                    ? selectedText.textContent.trim()
                    : '1 ' + singularText;

                return;
            }

            label.textContent = selected.length + ' ' + pluralText;
        }

        function filterOptions() {
            if (! search) {
                return;
            }

            const term = normalizeText(search.value);

            options.forEach(function (option) {
                const configuredText = option.getAttribute('data-cc-filter-text');
                const visibleText = option.textContent;
                const text = normalizeText(configuredText || visibleText);

                option.style.display = text.includes(term) ? 'grid' : 'none';
            });
        }

        function openMenu() {
            multiselect.classList.add('is-open');
            menu.classList.add('is-open');
            toggle.classList.add('is-open');

            if (search) {
                search.focus();
                filterOptions();
            }
        }

        function closeMenu() {
            multiselect.classList.remove('is-open');
            menu.classList.remove('is-open');
            toggle.classList.remove('is-open');
        }

        function toggleMenu() {
            if (menu.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        }

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            toggleMenu();
        });

        checkAll.addEventListener('change', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = checkAll.checked;
            });

            updateLabel();
        });

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateLabel);
        });

        if (search) {
            search.addEventListener('input', filterOptions);
            search.addEventListener('keyup', filterOptions);
        }

        document.addEventListener('click', function (event) {
            if (! multiselect.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });

        updateLabel();
    });
});
