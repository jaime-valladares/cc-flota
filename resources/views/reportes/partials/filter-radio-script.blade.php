<script>
document.querySelectorAll('[data-cc-filter-radio]').forEach(function (filter) {
    const toggle = filter.querySelector('[data-cc-filter-toggle]');
    const menu = filter.querySelector('[data-cc-filter-menu]');
    const label = filter.querySelector('[data-cc-filter-label]');

    function close() {
        filter.classList.remove('is-open');
        toggle?.classList.remove('is-open');
        menu?.classList.remove('is-open');
    }

    toggle?.addEventListener('click', function () {
        const open = !menu.classList.contains('is-open');
        close();
        if (open) {
            filter.classList.add('is-open');
            toggle.classList.add('is-open');
            menu.classList.add('is-open');
        }
    });

    filter.querySelectorAll('input[type="radio"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            label.textContent = radio.closest('[data-cc-filter-option]').querySelector('[data-cc-filter-option-label]').textContent.trim();
            close();
        });
    });

    document.addEventListener('click', function (event) {
        if (!filter.contains(event.target)) close();
    });
});
</script>
