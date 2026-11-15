(function () {
    'use strict';
    function getModule() {
        var path = window.location.pathname;
        if (path.indexOf('/crops/') !== -1) return 'crops';
        if (path.indexOf('/farmers/') !== -1) return 'farmers';
        if (path.indexOf('/farms/') !== -1) return 'farms';
        if (path.indexOf('/plantings/') !== -1) return 'planting';
        if (path.indexOf('/harvests/') !== -1) return 'harvest';
        return null;
    }
    var module = getModule();
    if (!module) return;
    var searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    var dropdown = document.createElement('div');
    dropdown.id = 'searchSuggestions';
    dropdown.className = 'position-absolute start-0 end-0 mt-1 bg-white border rounded shadow-sm overflow-auto';
    dropdown.style.maxHeight = '240px';
    dropdown.style.zIndex = '1050';
    dropdown.classList.add('d-none');
    searchInput.parentElement.style.position = 'relative';
    searchInput.parentElement.appendChild(dropdown);

    var debounceTimer = null;
    function fetchSuggestions(query) {
        if (query.length < 2) { dropdown.classList.add('d-none'); dropdown.innerHTML = ''; return; }
        fetch('/api/search.php?module=' + encodeURIComponent(module) + '&q=' + encodeURIComponent(query) + '&limit=8')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.results || data.results.length === 0) { dropdown.classList.add('d-none'); dropdown.innerHTML = ''; return; }
                dropdown.innerHTML = data.results.map(function (r) {
                    return '<div class="px-3 py-2 border-bottom" data-id="' + r.id + '" data-label="' + (r.label || '').replace(/"/g, '&quot;') + '">'
                        + '<div class="fw-medium">' + (r.label || '') + '</div>'
                        + (r.sub ? '<div class="small text-muted">' + r.sub + '</div>' : '')
                        + '</div>';
                }).join('');
                dropdown.classList.remove('d-none');
                dropdown.querySelectorAll('[data-id]').forEach(function (item) {
                    item.addEventListener('click', function () {
                        searchInput.value = item.getAttribute('data-label');
                        dropdown.classList.add('d-none');
                        var form = searchInput.closest('form');
                        if (form) form.submit();
                    });
                });
            }).catch(function () { dropdown.classList.add('d-none'); });
    }

    function filterTableRows(query) {
        var table = document.querySelector('table tbody');
        if (!table) return;
        var q = query.toLowerCase();
        table.querySelectorAll('tr').forEach(function (row) {
            row.style.display = (q === '' || row.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', function () {
        var query = searchInput.value.trim();
        filterTableRows(query);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { fetchSuggestions(query); }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) dropdown.classList.add('d-none');
    });
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dropdown.classList.add('d-none');
    });
})();
