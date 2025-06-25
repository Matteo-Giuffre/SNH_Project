    function updateFileInput() {
        // Radios
        const shortRadio = document.getElementById("short");
        const longRadio = document.getElementById("long");

        // Short
        const shortDiv = document.getElementById("short-insert");
        const shortText = document.getElementById("short-novel-text");

        // Long
        const longDiv = document.getElementById("long-insert");
        const fileInput = document.getElementById("novel-file");
        fileInput.value = "";
        
        if (shortRadio.checked) {
            shortText.value = '';
            longDiv.style.display = 'none';
            shortDiv.style.display = 'flex';
        }

        if (longRadio.checked) {
            shortDiv.style.display = 'none';
            longDiv.style.display = 'flex';
        }

        clearFile();
    }
    
    function displayPreview(event) {
        const file = event.target.files[0];
        if (!file) return;
        document.getElementById("file-name").value = file.name;
        document.getElementById("clear-file").style.display = "flex"; 
        
        document.getElementById("text-preview").style.display = "none";
        document.getElementById("pdf-preview").style.display = "block";
        document.getElementById("pdf-preview-name").innerText = file.name;
    }

    function clearFile() {
        document.getElementById("novel-file").value = "";
        document.getElementById("file-name").value = "";
        document.getElementById("clear-file").style.display = "none";
        document.getElementById("text-preview").style.display = "none";
        document.getElementById("pdf-preview").style.display = "none";
    }
    
    function updatePreview() {
        document.getElementById("preview-title").innerText = document.getElementById("title").value || "";
        document.getElementById("preview-author").innerText = document.getElementById("author").value || "";
        document.getElementById("preview-genre").innerText = document.getElementById("genre").value || "Not set";
        
        const selectedOption = document.querySelector('input[name="option"]:checked').value;
        document.getElementById("preview-option").innerText = selectedOption.charAt(0).toUpperCase() + selectedOption.slice(1);
        const selectedType = document.querySelector('input[name="novel-type"]:checked').value;
        document.getElementById("preview-type").innerText = selectedType.charAt(0).toUpperCase() + selectedType.slice(1);
    }

    function uploadNovel() {
        // Remove messages
        const successMessage = document.getElementById("success-message");
        successMessage.style.display = "none";
        const errorMessage = document.getElementById("error-message");
        errorMessage.style.display = 'none';
        
        // Retrieve info
        const title = document.getElementById("title").value.trim();
        const author = document.getElementById("author").value.trim();
        const genre = document.getElementById('genre').value;

        // Retrieve checked type
        const optionChecked = document.querySelector('input[name="option"]:checked')
        const free = (optionChecked && optionChecked.value === 'premium') ? 1 : 0;

        // Retrieve checked radio
        const shortNovel = document.getElementById('short').checked;
        const longNovel = document.getElementById('long').checked;

        // Retrieve novel
        const shortNovelBody = document.getElementById('short-novel-text').value.trim();
        const fileInput = document.getElementById("novel-file");

        // Validation check
        if (title === "" || author === "") {
            errorMessage.innerText = "Please fill in all fields!";
            errorMessage.style.display = "flex";
            return;
        }

        if (shortNovel && shortNovelBody === "") {
            errorMessage.innerText = "Please enter text for the short novel!";
            errorMessage.style.display = "flex";
            return;
        }

        if (longNovel && fileInput.files.length === 0) {
            errorMessage.innerText = "Please upload a PDF file for the long novel!";
            errorMessage.style.display = "flex";
            return;
        }

        // Create form data
        let formData = new FormData();
        formData.append("title", title);
        formData.append("author", author);
        formData.append("genre", genre);
        formData.append("free", free);

        // Add short novel text if provided
        if (shortNovel && shortNovelBody !== "") {
            formData.append("novel-type", 0);
            formData.append("novel-text", shortNovelBody);
        } else if (longNovel && fileInput.files.length > 0) {
            formData.append("novel-type", 1);
            formData.append("novel-file", fileInput.files[0]);
        }

        fetch("upload_novel.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                successMessage.innerText = data.message;
                successMessage.style.display = "block";
                clearFile();
                document.getElementById('title').value = '';
                document.getElementById('author').value = '';
                document.getElementById('genre').value = '';
                document.getElementById('short-novel-text').value = '';
                document.getElementById("preview-title").innerText = "";
                document.getElementById("preview-author").innerText = "";
                document.getElementById("preview-genre").innerText = "Not set";
            } else {
                errorMessage.innerText = data.message;
                errorMessage.style.display = 'block';
            }
        })
        .catch (error => {
            console.error("Upload error: ", error);
            errorMessage.innerText = "Error uploading file. Try again later. " + error.message;
            errorMessage.style.display = 'block';
        })
    }

    document.addEventListener("DOMContentLoaded", function() {

        // Real-time update info
        document.getElementById("title").addEventListener("input", updatePreview);
        document.getElementById("author").addEventListener("input", updatePreview);
        document.getElementById("genre").addEventListener("change", updatePreview);
        
        const optionRadios = document.querySelectorAll('input[name="option"]');
        optionRadios.forEach(radio => radio.addEventListener("change", updatePreview));

        const TypeRadios = document.querySelectorAll('input[name="novel-type"]');
        TypeRadios.forEach(radio => radio.addEventListener("change", function(e) {
            updateFileInput();
            updatePreview();
        }));
        
        // Display file preview
        const novelFile = document.getElementById("novel-file");
        novelFile.addEventListener('change', displayPreview);
        
        // Upload a pdf file
        const uploadButton = document.getElementById("upload-btn");
        uploadButton.addEventListener('click', function() {
            novelFile.click()
        });

        // Delete inserted file
        const clearButton = document.getElementById("clear-file");
        clearButton.addEventListener("click", clearFile);

        // Trigger validateForm function when clicking on submit button
        const submitButton = document.getElementById('submit-novel');
        submitButton.addEventListener('click', function(event) {
            event.preventDefault();
            uploadNovel();
        });
    });