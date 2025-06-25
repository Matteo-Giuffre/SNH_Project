document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('delete-form');
    const downloadForm = document.getElementById('download-form');
    const novelId = document.getElementById("novel_id").value;
    const title = document.getElementById("novel_title").value;

    if (deleteForm) {
        // DELETE FORM
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const confirmed = confirm('Are you sure you want to delete this novel?');

            // If not confirmed, block form sending
            if (!confirmed) return;

            fetch("delete.php", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `novel_id=${encodeURIComponent(novelId)}`
            })
            .then(response => response.json())
            .then(data => {
                const message = data.message;
                // Se l'eliminazione è avvenuta con successo creo l'alert con il messaggio di successo e redirigo l'utente
                if (data.status === "success") {
                    alert(message);
                    window.location.href = 'index.php?filter=my_novels';
                } else {
                    // Altrimenti creo un alert con il messaggio di errore
                    alert(message);
                }
            })
            .catch(error => {
                console.error("Error: " + error);
            })
        });
    }

    // DOWNLOAD FORM
    downloadForm.addEventListener('submit', function(event) {
        event.preventDefault();

        fetch("download.php", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `download-id=${encodeURIComponent(novelId)}`
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message);
                });
            }
            return response.blob();
        })
        .then(blob => {
            // Crea un link temporaneo per scaricare il file
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${title}.pdf`; // Nome del file
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => alert("Download error: " + error.message))
    });
});
