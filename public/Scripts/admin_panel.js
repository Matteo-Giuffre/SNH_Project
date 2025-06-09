       
    function togglePremium(userId) {

        fetch('update_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${encodeURIComponent(userId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occured');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchBar = document.getElementById('searchBar');

        if (!searchBar) {
            return;
        }

        searchBar.addEventListener('input', function () {
            const searchValue = searchBar.value.toLowerCase().trim();
            const userCards = document.querySelectorAll('.user-card');
            
            if (searchValue === '') {
                userCards.forEach(card => {
                    card.style.display = ""; // Mostra la card
                });
                return;
            }

            userCards.forEach(card => {
                const username = card.querySelector('h3').textContent.toLowerCase();
                const match = username.includes(searchValue);
                card.style.display = match ? "" : "none";
            });
        });

        document.querySelectorAll('.button-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const id = button.dataset.id;
                togglePremium(id);
            });
        });
    });