# System and Network Hacking Project

## Getting started
### Inserimento secrets
Per abilitare l'invio delle emails, bisogna fornire le credenziali del server SMTP a cui connettersi. Queste info sono caricate tramite i seguenti secrets:
- `secrets/smtp_host.txt`: hostname del server SMTP per l'autenticazione (es. *smtp.gmail.com*).
- `secrets/smtp_port.txt`
- `secrets/smtp_username.txt`: indirizzo email utilizzato per inviare i messaggi.
- `secrets/smtp_password.txt`

### Inserimento indirizzo email dell'admin
Per registrare correttamente l'admin della web application, è necessario inserire un indirizzo email valido nel file `config/03_insert_admin.sql`.

### Build containers
Per costruire il database e l'applicazione, eseguire il comando:
```bash
   docker compose up --build
```

## Minacce mitigate e Strategie di sicurezza adottate
Le principali vulnerabilità trattate a lezione e le relative contromisure implementate nell'applicazione web:
1. **Directory bruteforcing**: cartelle e file sensibili sono stati posizionati al di fuori della directory pubblica (`/var/www/html`).
2. **Handling user input**: uso della validazione tramite white-listing nel frontend (JS) e sanificazione/validazione nel backend; controllo del tipo di input ricevuto (stringa, intero, ecc.) e dell’estensione dei file caricati (***solo PDF***).
3. **User enumeration**: messaggi di errore generici per evitare la divulgazione di informazioni (es. "Email o password errati").
4. **Login bruteforcing**: richiesta obbligatoria di password complesse alla registrazione, assenza di messaggi informativi dettagliati, blocco dell’account dopo un numero stabilito di tentativi (sblocco tramite cambio password) e avviso all’utente in caso di tentativo di registrazione con il proprio indirizzo email.

   **NB**: per l’admin le contromisure sono identiche, ma lo sblocco dell’account è possibile solo contattando l’amministratore di sistema (sblocco manuale).
5. **Secure credential storage**: utilizzo dell’algoritmo **bcrypt** per la creazione di hash con salt. Le funzioni `password_hash()` e `password_verify()` vengono utilizzate rispettivamente per la generazione e la verifica.
6. **Token/Cookie**: trasmissione via HTTPS grazie all'attributo `cookie_secure => true` e protezione CSRF tramite `cookie_samesite => 'Strict'`.
7. **Access control**: utilizzo delle sessioni per la verifica dei privilegi utente, ad esempio:
   - `$_SESSION['loggedin']`: verifica dell'autenticazione
   - `$_SESSION['user-type']`: determina l’accesso alle novelle (utente "free" o "premium")
   - `$_SESSION['isadmin']`: verifica se l’utente è un amministratore
8. **SQL Injection**: prevenzione mediante prepared statement.
9. **OS Command Injection**: evitato l’uso di funzioni che eseguono chiamate al sistema.
10. **Path traversal**: utilizzo di `realpath()` per ottenere i percorsi assoluti ed eliminare quelli relativi.
11. **Type juggling**: uso esclusivo di strict comparison (===, !==).
12. **Cross-Site Scripting** (**XSS**): mitigazione tramite `htmlspecialchars()`, sanificazione con `sanitizer.php` e uso degli header di risposta con **Content-Security-Policy** (definita in `config/apache-ssl.conf`). La **Same Origin Policy** fornisce ulteriore isolamento tra domini (*è implementata automaticamente dal browser*).
13. **On-Site Request Forgery**: validazione degli input.
14. **Cross-Site Request Forgery** (**CSRF**): protezione tramite `cookie_samesite => 'Strict'`.
15. **Clickjacking**: mitigazione tramite la direttiva CSP frame-ancestors (in `config/apache-ssl.conf`).
16. **EXTRA**:
   - **Mutual authentication**: per garantire che solo la webapp autorizzata possa connettersi al database, è stata implementata l'autenticazione reciproca tramite certificati TLS firmati da una CA comune (vedere `config/01_ssl_init.sql` e `config/mysql.cnf`).

## File non pubblici
Alcuni file non sono stati inseriti nella directory pubblica (`/var/www/html`), rendendoli inaccessibili direttamente via web. Questo evita l'esposizione accidentale di codice o configurazioni sensibili. I file sono:
- `logger.php`: definisce le funzioni per il logging degli eventi interni (es. login falliti, errori di validazione, registrazioni, ecc.).
- `sanitizer.php`: contiene una classe per la validazione/sanificazione di input (email, username, stringhe generiche, ecc.).
- `config_db.php`: inizializza la connessione al database e restituisce un **oggetto PDO** per eseguire query in modo sicuro.
- `smtp_config/smtp_connection.php`: implementa una classe per la connessione sicura al server SMTP tramite **ENCRYPTION_SMTPS**, con funzionalità di test della connessione e invio email.

## Logging
La web application registra i log relativi alle seguenti categorie:
1. Errori di validazione e sanificazione dei dati inseriti.
2. Errori durante l'esecuzione delle operazioni, come ad esempio:
   - Tentativo di riutilizzo di una password già utilizzata;
   - Utente non trovato;
   - Altri errori di sistema o applicativi.
3. Messaggi di successo, tra cui:
   - Login effettuato con successo;
   - L’utente X ha caricato la novella Y;
   - L’admin X ha modificato i privilegi dell’utente Z.

I log sono uno strumento fondamentale per l’analisi e la risoluzione di eventuali incidenti. Alcuni esempi di scenari investigabili attraverso i log includono:
- Un utente ha inserito un formato non valido nel campo email;
- Un utente ha tentato di accedere a una risorsa senza disporre dei permessi necessari;
- Un utente non autenticato ha provato a effettuare il login utilizzando tutte e cinque le possibilità disponibili.