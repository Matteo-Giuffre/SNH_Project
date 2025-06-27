<?php
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'cookie_secure' => true, // Assicurati che il sito usi HTTPS
        'cookie_samesite' => 'Strict'
    ]);

    ob_clean();
    header("Content-Type: application/json");
    
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        die(json_encode(["status" => "error", "message" => "Unauthorized"]));
    }

    if ($_SESSION['IP'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['User-Agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        header("Location: /login/");
        exit;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {//15 minuti
        session_unset();
        session_destroy();
        header("Location: /login/");
        exit;
    }

    $_SESSION['last_activity'] = time(); // Aggiorna il timer
    $basedir = realpath('/var/www/uploaded_novels/');
    $user_id = $_SESSION['id']; // Ottieni l'id dalla sessione
    
    // Configurazione del database
    require_once '/var/www/mysql_client/config_db.php';
    require_once '/var/www/app/sanitizer.php';
    require_once '/var/www/app/logger.php';
    
    if (!isset($_POST['author'], $_POST['title'], $_POST['genre'], $_POST['free'], $_POST['novel-type'])) {
        logs_webapp("bad request (parameters missing)", $_SESSION['username'], 'novels_upload.log');

        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing required parameters"]);
        exit;
    }

    $sanitizer = new InputSanitizer();

    $title = $_POST['title'];
    if (!($title = $sanitizer->sanitizeString($title))) {
        logs_webapp('tried to use an invalid title format', $_SESSION['username'], 'novels_upload.log');

        http_response_code(400);
        echo json_encode(["status" => "error", "message", "Invalid title format"]);
        exit;
    }
    $author = $_POST['author'];
    if (!($author = $sanitizer->sanitizeString($author))) {
        logs_webapp('tried to use an invalid author format', $_SESSION['username'], 'novels_upload.log');

        http_response_code(400);
        echo json_encode(["status" => "error", "message", "Invalid author format"]);
        exit;
    }
    $genre = $_POST['genre'];
    if (!($genre = $sanitizer->sanitizeString($genre))) {
        logs_webapp('tried to use an invalid genre format', $_SESSION['username'], 'novels_upload.log');

        http_response_code(400);
        echo json_encode(["status" => "error", "message", "Invalid genre format"]);
        exit;
    }
    $free = (int)$_POST['free'];
    if ($free !== 0 && $free !== 1) {
        logs_webapp('tried to use an invalid priviledge format', $_SESSION['username'], 'novels_upload.log');

        http_response_code(400);
        echo json_encode(["status" => "error", "message", "Invalid priviledge format"]);
        exit;
    }
    $novelType = (int)$_POST['novel-type'];
    if ($novelType !== 0 && $novelType !== 1) {
        logs_webapp('tried to use an invalid novel type format', $_SESSION['username'], 'novels_upload.log');

        http_response_code(400);
        echo json_encode(["status" => "error", "message", "Invalid novel type format"]);
        exit;
    }

    // Check short novel
    if ($novelType === 0) {
        if (!isset($_POST['novel-text']) || empty($_POST['novel-text'])) {
            logs_webapp('missing text to upload a short novel', $_SESSION['username'], 'novels_upload.log');

            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Short novel text is required.']);
            exit;
        }

        // Sanitize novel text
        $short_novel = $_POST['novel-text'];
        if (!($short_novel = $sanitizer->sanitizeMultiLineString($short_novel))) {
            logs_webapp('tried to use an invalid novel text format', $_SESSION['username'], 'novels_upload.log');

            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid short novel format"]);
            exit;
        }

        // genero il nome del file
        $timestamp = date("Y-m-d-H-i-s");
        $string_id = strval($user_id);
        $filename = "$timestamp-$string_id.txt"; 
        $targetFile = $basedir . '/' . $filename;

        $result = file_put_contents($targetFile, $short_novel);

        if ($result === false) {
            logs_webapp('something gone wrong during novel uploading ', $_SESSION['username'], 'novels_upload.log');

            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Something gone wrong during novel uploading!"]);
            exit;
        }

        try {
            // Query di inserimento
            $query = "INSERT INTO novels (uploader_id, title, author_name, genre, free, novel_type, file_path) 
                    VALUES (:uploader_id, :title, :author_name, :genre, :free, :novel_type, :file_path)";
            
            $stmt = $pdo->prepare($query);
            
            // Bind dei parametri
            $stmt->bindParam(':uploader_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':author_name', $author, PDO::PARAM_STR);
            $stmt->bindParam(':genre', $genre, PDO::PARAM_STR);
            $stmt->bindParam(':free', $free, PDO::PARAM_INT);
            $stmt->bindParam(':novel_type', $novelType, PDO::PARAM_INT);
            $stmt->bindParam(':file_path', $targetFile, PDO::PARAM_STR);
            
            // Esegui l'inserimento
            if ($stmt->execute()) {
                $last_id = $pdo->lastInsertId();
                logs_webapp("uploaded a short novel (novel ID: $last_id)", $_SESSION['username'], 'novels_upload.log');

                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Novel uploaded successfully"]);
                exit;
            }

            // Se arrivo qui, l'inserimento non è andato a buon fine e lancio una PDOExpection
            throw new PDOException("Something gone wrong during novel uploading!");

        } catch (PDOException $e) {
            logs_webapp("tried to insert a short novel but something gone wrong", $_SESSION['username'], 'novels_upload.log');

            // If something gone wrong delete the uploaded file
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }

            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
    }

    // Check long novel
    if ($novelType === 1) {
        if (!isset($_FILES['novel-file']) || $_FILES['novel-file']['error'] !== UPLOAD_ERR_OK) {
            logs_webapp('tried to use an invalid file format (only PDF are valid)', $_SESSION['username'], 'novels_upload.log');

            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Valid PDF file is required for long novel.']);
            exit;
        }
        
        // Check file extension
        $fileExtension = strtolower(pathinfo($_FILES['novel-file']['name'], PATHINFO_EXTENSION));
        if ($fileExtension !== "pdf") {
            logs_webapp('tried to use an invalid file format (only PDF are valid)', $_SESSION['username'], 'novels_upload.log');

            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PDF files are allowed.']);
            exit;
        }

        // Verify MIME type (more accurate to check file type)
        $fileTmpPath = $_FILES['novel-file']['tmp_name'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpPath);
        finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            logs_webapp('tried to use an invalid file format (only PDF are valid)', $_SESSION['username'], 'novels_upload.log');

            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PDF files are allowed.']);
            exit;
        }

        //genero il nome del file
        $timestamp = date("Y-m-d-H-i-s");
        $string_id = strval($user_id);
        $filename = "$timestamp-$string_id.pdf"; 
        $targetFile = $basedir . '/' . $filename;

        if (move_uploaded_file($_FILES['novel-file']['tmp_name'], $targetFile)) {//se il file viene caricato correttamente inserisco i dati nel db
        
            try {
                // Query di inserimento
                $query = "INSERT INTO novels (uploader_id, title, author_name, genre, free, novel_type, file_path) 
                        VALUES (:uploader_id, :title, :author_name, :genre, :free, :novel_type, :file_path)";
                
                $stmt = $pdo->prepare($query);
                
                // Bind dei parametri
                $stmt->bindParam(':uploader_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                $stmt->bindParam(':author_name', $author, PDO::PARAM_STR);
                $stmt->bindParam(':genre', $genre, PDO::PARAM_STR);
                $stmt->bindParam(':free', $free, PDO::PARAM_INT);
                $stmt->bindParam(':novel_type', $novelType, PDO::PARAM_INT);
                $stmt->bindParam(':file_path', $targetFile, PDO::PARAM_STR);
                
                // Esegui l'inserimento
                if ($stmt->execute()) {
                    // Retrieve last id and log the event
                    $last_id = $pdo->lastInsertId();
                    logs_webapp("uploaded a long novel (novel ID: $last_id)", $_SESSION['username'], 'novels_upload.log');
                    
                    // Send success response
                    http_response_code(200);
                    echo json_encode(["status" => "success", "message" => "Novel uploaded successfully"]);
                    exit;
                }

                throw new PDOException("Something gone wrong during novel uploading!");

            } catch (PDOException $e) {
                logs_webapp("tried to insert a long novel but something gone wrong", $_SESSION['username'], 'novels_upload.log');

                // If something gone wrong delete the uploaded file
                if (file_exists($targetFile)) {
                    unlink($targetFile);
                }

                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
                exit;
            }
        }

        logs_webapp('something gone wrong during novel uploading ', $_SESSION['username'], 'novels_upload.log');
        
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Something gone wrong during novel uploading!"]);
    }
?>