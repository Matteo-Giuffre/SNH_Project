    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.filter-tag');
        const searchBar = document.getElementById('search-bar');
        const searchForm = document.getElementById('search-form');
        const reload = document.getElementById("reload-section");

        filterButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();

                const filterValue = this.dataset.filter;

                // Create an URL only containing filter value (without search)
                if (filterValue !== 'all') {
                    const url = new URL(window.location.href);
                    url.searchParams.set('filter', filterValue);
                    url.searchParams.set('page', 1);
                    url.searchParams.delete('search');
                    window.location.href = url.toString();
                } else {
                    window.location.href = 'index.php';
                }
            });
        });

        searchForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Save search bar value inside localStorage
            const searchValue = searchBar.value.trim();
            if (!searchValue) window.location.href = 'index.php';

            const url = new URL(window.location.href);
            url.searchParams.set('search', searchValue);
            window.location.href = url.toString();
        });

        searchBar.addEventListener('input', function() {
            const searchValue = searchBar.value.trim();
            if (searchValue) {
                localStorage.setItem('searchBackup', searchValue);
            } else {
                localStorage.removeItem('searchBackup');
            }
        })

        reload.addEventListener('click', function() {
            searchBar.value = "";
            localStorage.removeItem('searchBackup');
            window.location.href = 'index.php';
        })

        const backup = localStorage.getItem('searchBackup');
        if (backup && !searchBar.value) {
            searchBar.value = backup;
        }
    });