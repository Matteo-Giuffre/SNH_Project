// Validazione form di login
const loginForm = document.getElementById('loginForm');

loginForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const username = document.getElementById('loginUsername').value.trim();
    if (!username.match(/^[a-zA-Z0-9_-]{3,20}$/)) {
        return;
    }

    const password = document.getElementById('loginPassword').value.trim();
    if (!password.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{}\[\]|;:\'",.<>?\\/`~]).{8,64}$/)) {
        return;
    }

    //invio dati al backend php
    fetch('be_login_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status == "success") {
            // Se la connessione è riuscita, reindirizza l'utente
            window.location.href = '/admin-portal/admin_panel.php';
        } else {// se il db non è raggiungibile o le credenziali sono errate
            document.getElementById('message').innerHTML = data.message;
        }
    })
    .catch(error => {
        console.error('Errore durante il login:', error);
    });

});