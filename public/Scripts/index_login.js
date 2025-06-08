    const year = new Date().getFullYear();
    document.getElementById("p_footer").innerHTML = "&copy " + year + " Novelist Space. All rights reserved.";
            
    // Gestione del cambio tra login e signup
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.auth-tab');
        const forms = document.querySelectorAll('.auth-form');
        const switchButtons = document.querySelectorAll('.switch-form');

        function switchForm(formId) {
            // Aggiorna i tab
            tabs.forEach(tab => {
                tab.classList.toggle('active', tab.dataset.tab === formId);
            });

            // Aggiorna i form
            forms.forEach(form => {
                form.classList.toggle('active', form.id === `${formId}Form`);
            });
        }

        // Event listener per i tab
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                switchForm(tab.dataset.tab);
            });
        });

        // Event listener per i link di switch
        switchButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                switchForm(button.dataset.form);
            });
        });
    });


    // Validazione form di login
    const loginForm = document.getElementById('loginForm');

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Valida l'username
        const username = document.getElementById('loginUsername').value.trim();
        if (!username.match(/^[a-zA-Z0-9_-]{3,20}$/)) {
            return;
        }

        // Valida la password
        const password = document.getElementById('loginPassword').value.trim();
        if (!password.match(/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&_-])[A-Za-z\d@$!%*?&_-]{8,64}$/)) {
            return;
        }

        //invio dati al backend php
        fetch('be_login.php', {
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
                window.location.href = '/novels/index.php';
            } else {
                // se il db non è raggiungibile o le credenziali sono errate
                document.getElementById('loginError').innerHTML = data.message;
            }
        })
        .catch(error => {
            console.error('Errore durante il login:', error);
        });
    });

    // Validazione form di signup (AGGIUSTARE ULTIMA PARTE)
    const signupForm = document.getElementById('signupForm');

    signupForm.addEventListener('submit', function(e) {
    e.preventDefault();
    let isValid = true;

    // Validazione email
    const email = document.getElementById('signupEmail').value.trim();
    var emailError = document.getElementById('emailError');
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        emailError.innerHTML = 'Please enter a valid email address';
        isValid = false;
    } else {
        emailError.innerHTML = '';
    }

    // Validazione username
    const username = document.getElementById('signupUsername').value.trim();
    var usernameError = document.getElementById('usernameError');
    if (!username.match(/^[a-zA-Z0-9_-]{3,20}$/)) {
        usernameError.innerHTML = 'Username must be 3-20 characters long and can only contain letters, numbers and underscore';
        isValid = false;
    } else {
        usernameError.innerHTML = '';
    }

    // Validazione password
    const password = document.getElementById('signupPassword').value.trim();
    var passwordError = document.getElementById('passwordError');
    if (!password.match(/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&_-])[A-Za-z\d@$!%*?&_-]{8,64}$/)) {
        passwordError.innerHTML = 'Invalid Password';
        isValid = false;
    } else {
        passwordError.innerHTML = '';
    }

    // Validazione conferma password
    const confirmPassword = document.getElementById('confirmPassword').value.trim();
    var confirmPasswordError = document.getElementById('confirmPasswordError');
    if (password !== confirmPassword) {
        confirmPasswordError.innerHTML = 'Passwords do not match!';
        isValid = false;
    } else {
        confirmPasswordError.innerHTML = '';
    }

    // Se è tutto valido manda tutto al server:
    if (isValid) {
        //invio dati al backend php
        fetch('be_registration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status == "success") {
                // Se la connessione è riuscita, reindirizza l'utente
                document.getElementById("main_auth").hidden = true;
                document.getElementById("main_success").hidden = false;
                document.getElementById("success_message").innerText = data.message;
            } else {
                document.getElementById('message2').innerHTML = data.message;
            }
        })
        .catch(error => {
            console.error('Errore durante la regitrazione:', error);
        });
    }
    });

    const returnlogin = document.getElementById("successForm");
    returnlogin.addEventListener('submit', function(e) {
        e.preventDefault();
        location.reload();
    });