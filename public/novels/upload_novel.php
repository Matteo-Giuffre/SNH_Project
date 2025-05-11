<?php
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'cookie_secure' => true, // Assicurati che il sito usi HTTPS
        'cookie_samesite' => 'Strict'
    ]);


    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        die(json_encode(["error" => "Unauthorized"]));
    }

    if ($_SESSION['IP'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['User-Agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        header("Location: ../login/index.html");
        exit;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {//15 minuti
        session_unset();
        session_destroy();
        header("Location: ../login/index.html");
        exit;
    }
    $_SESSION['last_activity'] = time(); // Aggiorna il timer

    $user_id = $_SESSION['id']; // Ottieni l'id dalla sessione

    // Configurazione del database
    require_once '/var/www/mysql_client/config_db.php';


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $title = $_POST['title'];
        $author = $_POST['author'];
        $genre = $_POST['genre'];
        $free = $_POST['free'];
        $novel_type = $_POST['novel-type'];

        $Directory = 'uploaded_novels/';

        if (!isset($_FILES['novel-file'])) {
            die(json_encode(["error" => "Upload Error!"]));
        }

        // Controllo l'estensione del file
        $fileExtension = strtolower(pathinfo($_FILES['novel-file']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['pdf', 'txt'])) {
            die(json_encode(["error" => "Invalid file extension! Only PDF and TXT files are allowed."]));
        }
        
        //genero il nome del file
        $timestamp = date("Y-m-d-H-i-s");
        $string_id = strval($user_id);
        $filename = "$timestamp-$string_id.$fileExtension"; 
        $targetFile = $Directory . $filename;

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
                $stmt->bindParam(':free', $free, PDO::PARAM_BOOL);
                $stmt->bindParam(':novel_type', $novel_type, PDO::PARAM_BOOL);
                $stmt->bindParam(':file_path', $targetFile, PDO::PARAM_STR);
                
                // Esegui l'inserimento
                $stmt->execute();
                echo json_encode(["status" => "success", "message" => "Novel uploaded successfully"]);
            } catch (PDOException $e) {
                die(json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]));
            }        
        } 
        else{
            die(json_encode(["error" => "File upload failed"]));
        }

    } 
    else{
        die(json_encode(["error" => "Invalid request"]));
    }
?>