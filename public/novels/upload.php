<?php
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'cookie_secure' => true, // Assicurati che il sito usi HTTPS
        'cookie_samesite' => 'Strict'
    ]);


    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: /login/");
        exit;
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

    $username = $_SESSION['username']; // Ottieni lo username dalla sessione
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Novelist Space - Upload Your Novel</title>
        <link rel="stylesheet" href="/Styles/upload_style.css">
    </head>
    <body>
        <header class="header">
            <a href="" class="logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                    </path>
                </svg>
                <span class="logo-text">Novelist Space - Upload</span>
            </a>
            <div class="nav-links">
                <a href="index.php" id="back-home">Back Home</a>
            </div>
        </header>

        <div class="main-content">
            <div class="upload-form">
                <h2>Upload Your Novel</h2>
                <form>
                    <div class="form-section">
                        <label for="title">Novel Title</label>
                        <input type="text" id="title" name="title" placeholder="Insert Title...">
                    </div>

                    <div class="form-section">
                        <label for="author">Author Name</label>
                        <input type="text" id="author" name="author" placeholder="Insert Author...">
                    </div>

                    <div class="form-section">
                        <label for="genre">Genre</label>
                        <select id="genre" name="genre">
                            <option value="" selected disabled hidden>Choose a genre</option>
                            <option value="Fantasy">Fantasy</option>
                            <option value="Science Fiction">Science Fiction</option>
                            <option value="Mystery">Mystery</option>
                            <option value="Romance">Romance</option>
                            <option value="Dystopian">Dystopian</option>
                            <option value="Cyberpunk">Cyberpunk</option>
                            <option value="Steampunk">Steampunk</option>
                            <option value="Thriller">Thriller</option>
                            <option value="Crime">Crime</option>
                            <option value="Detective">Detective</option>
                            <option value="Horror">Horror</option>
                            <option value="Paranormal">Paranormal</option>
                            <option value="Supernatural">Supernatural</option>
                            <option value="Historical Fiction">Historical Fiction</option>
                            <option value="Dark Romance">Dark Romance</option>
                            <option value="Contemporary Romance">Contemporary Romance</option>
                            <option value="Children's Fiction">Children's Fiction</option>
                            <option value="Adventure">Adventure</option>
                            <option value="Action">Action</option>
                            <option value="War Fiction">War Fiction</option>
                            <option value="Western">Western</option>
                            <option value="Magical Realism">Magical Realism</option>
                            <option value="Gothic Fiction">Gothic Fiction</option>
                            <option value="Mythology">Mythology</option>
                            <option value="Fable">Fable</option>
                            <option value="Satire">Satire</option>
                            <option value="Comedy">Comedy</option>
                            <option value="Drama">Drama</option>
                            <option value="Psychological Fiction">Psychological Fiction</option>
                            <option value="Espionage">Espionage</option>
                            <option value="Post-Apocalyptic">Post-Apocalyptic</option>
                            <option value="Time Travel">Time Travel</option>
                            <option value="Alternate History">Alternate History</option>
                            <option value="Epic Fantasy">Epic Fantasy</option>
                            <option value="Space Opera">Space Opera</option>
                            <option value="Biography">Biography</option>
                            <option value="Autobiography">Autobiography</option>
                            <option value="Memoir">Memoir</option>
                            <option value="History">History</option>
                            <option value="Self-Help">Self-Help</option>
                            <option value="Science">Science</option>
                            <option value="Philosophy">Philosophy</option>
                            <option value="Poetry">Poetry</option>
                            <option value="Spirituality">Spirituality</option>
                            <option value="Anthology">Anthology</option>
                            <option value="Graphic Novel">Graphic Novel</option>
                            <option value="Manga">Manga</option>
                            <option value="Comic Book">Comic Book</option>
                            <option value="Other">Other...</option>
                        </select>
                    </div>
                    
                    <div class="form-section">
                        <label>Select Option</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="free" name="option" value="free" checked>
                                <label for="free">Free</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="premium" name="option" value="premium">
                                <label for="premium">Premium</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <label>Select Novel Type</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="short" name="novel-type" value="short" checked>
                                <label for="short">Short</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="long" name="novel-type" value="long">
                                <label for="long">Long</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <label>Upload File</label>
                        <div id="short-insert">
                            <textarea id="short-novel-text" class="responsive-textarea" placeholder="Write your novel..."></textarea>
                        </div>
                        <div id="long-insert" class="custom-file-upload">
                            <input type="file" id="novel-file" name="novel-file" accept=".pdf" hidden>
                            <div class="file-display">
                                <input type="text2" id="file-name" placeholder="Choose file..." readonly>
                                <button type="button" id="clear-file" class="clear-button">✖</button>
                            </div>
                            <button type="button" id="upload-btn">Browse</button>
                        </div>
                    </div>

                    <button type="submit" id="submit-novel" class="submit-btn">Upload Novel</button>
                    <p id="error-message" class="message-error"></p>
                    <p id="success-message" class="message-ok"></p>
                </form>
            </div>

            <div class="preview-section">
                <h2>Preview</h2>
                <div class="preview-info">
                    <p><span class="preview-label">Novel Title:</span> <span id="preview-title"></span></p>
                    <p><span class="preview-label">Author:</span> <span id="preview-author"></span></p>
                    <p><span class="preview-label">Genre:</span> <span id="preview-genre">Not set</span></p>
                    <p><span class="preview-label">Option:</span> <span id="preview-option">Free</span></p>
                    <p><span class="preview-label">Novel Type:</span> <span id="preview-type">Short</span></p>
                    <p><span class="preview-label">Uploaded by:</span> <span id="preview-username"><?= htmlspecialchars($username, ENT_QUOTES) ?></span></p>
                </div>
                <div id="preview-container">
                    <pre id="text-preview" class="preview-text"></pre>
                    <div id="pdf-preview" class="preview-pdf">
                        <img src="/Resources/pdf_icon.png" alt="PDF Preview" class="preview-image">
                        <p id="pdf-preview-name"></p>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer">
            <p id="p_footer"></p>
        </footer>

        <script src="/Scripts/copyright.js"></script>
        <script src="/Scripts/upload_index.js"></script>

    </body>
</html>