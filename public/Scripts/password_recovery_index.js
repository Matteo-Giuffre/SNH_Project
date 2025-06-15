    function sendmail(event) {    
        // Impedire il comportamento predefinito (l'invio del form e il ricaricamento della pagina)
        event.preventDefault();

        // Retrieve email
        const email = document.getElementById("email").value.trim();
        const emailError = document.getElementById('post_recovery_notok');
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            emailError.innerHTML = 'Please enter a valid email address format';
            emailError.hidden = false;
            return;
        } else {
            emailError.hidden = true;
            emailError.innerHTML = '';
        }

        // Send data to sendmail.php
        fetch("sendmail.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `email=${encodeURIComponent(email)}`
        })
        .then(response => {
            if (response.ok) {
                const email_div = document.getElementById("recovery").hidden = true;
                const email_span = document.getElementById("post_recovery_ok").hidden = false;
                return null;
            }
            return response.json();
        })
        .then(data => {
            if (data && data.status === 'error') {
                emailError.innerHTML = data.message;
                document.getElementById("recovery").hidden = true;
                emailError.hidden = false;
            }
        })
        .catch(error => {
            console.error("Error: " + error);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('recovery-form');

        if (form) {
            form.addEventListener('submit', sendmail);
        }
    });