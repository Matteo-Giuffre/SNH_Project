# System and Network Hacking Project

## Spiegazione files e directories
- **Dockerfile_db**: dockerfile che costruisce il db. Attualmente il database è settato in modo da richiedere la mutual authentication (quindi con verifica del certificato) da parte dell'utente "novelist_user" (vedere ./config/ssl_init.sql)
- **Dockerfile_web**: si occupa della costruzione di tutte le directory necessarie per il corretto funzionamento della web application. I files e directories che verranno create sono specificate qui dentro. In caso doveste creare altre directory, date i giusti permessi runnando un `chown www-data:www-data -R /path/nuova/cartella`
- **docker-compose**: il solito docker-compose. Da qui la web application viene lanciata con l'utente **www-data** ed ho settato la modalità **watch** in modo che ad ogni modifica il container viene ricostruito per evitare di rilanciare il comando (meglio verificare però che le modifiche vengano effettivamente fatte, in quanto a volte non succede)
- **config**: qui dentro ci sono i file di configurazione. **Importante**: per prevenire alcuni attacchi (es. clickjacking) bisogna settare alcuni header; anziché settarli in ogni file php è possibile inseririli dentro **apache-ssl.conf** in modo che il server ad ogni risposta inserisca questi header per prevenire gli attacchi.
- **config/tables_db.sql**: in questa tabella ho tolto il salt agli utenti dato che utilizzeremo bcrypt (il salt e la password hashata sono direttamente in un'unica stringa quindi non c'è bisogno di due colonne diverse ma ne basta una)
- **novelist_logs**: è la cartella in cui verranno salvati i logs. Non cambiate nome in quanto se inserite solo log, linux la rileva automaticamente come cartella appartenente al root e l'utente www-data (che runna tutta l'applicazione) non sarà in grado di scriverci sopra
- **public**: contiene tutti i file necessari per la web application
- **secrets**: questa cartella contiene tutte le password e informazioni sensibili per l'applicazione (i file non li carico ma leggete sotto per capire quali bisogna creare)
- **smtp_config**: ha un file php per connettersi direttamente al server SMTP. Ho pensato che è meglio avere un unico file da richiamare anziché scrivere tutta la procedura in ogni file php e incappare in errori (almeno essendo unico e funzionante sappiamo che funziona). Inoltre contiene tutti i file html che verranno inviati tramite email
- **logger.php**: questo file serve per scrivere i logs
- **sanitizer.php**: come per il php dell'SMTP ho creato questa classe che sanitifica diversi tipi di input. NB: per la password non abbiamo bisogno di sanificare ma solamente di validare in quanto modifiche ad essa potrebbero causare problemi con l'hashing e cose varie
- **ssl**: contiene tutti i certificati per la web application, per il db e la CA. Per abilitare la mutual authentication tra webapp e db, ho dovuto firmare i certificati del db e della webapp (per connettersi al db) con la stessa CA.
- **config_db.php**: file per collegarsi al container che contiene il db

## Secrets:
I secrets da creare sono:
- **mysql_password.txt**: la password per l'utente mysql (libera scelta)
- **mysql_root_password.txt**: la password per l'utente root mysql (libera scelta)
- **smtp_host.txt**: il server SMTP a cui collegarsi
- **smtp_port.txt**: la porta SMTP a cui collegarsi
- **smtp_username.txt**: l'indirizzo email a cui collegarsi
- **smtp_password.txt**: la password dell'indirizzo email da utilizzare per collegarsi al server SMTP (es. password Gmail)

## SMTP
Ho provato ad utilizzare l'account Gmail che aveva creato Matteo ma a volte funzionava altre no. Quando avevamo iniziato il progetto avevo preso un dominio a 0€ (novelistspace.it). Questo dominio include un file SMTP con 50 mail giornaliere disponibili. Evito di pubblicare host, port, username e password in quanto ci sono i miei dati come carta di credito ecc. 
**Se volete utilizzarlo contattatemi che vi giro le credenziali in privato.**

## ATTACCHI E VULNERABILITÀ
Gli attacchi da cui dobbiamo proteggere la nostra web application sono contenuti in **attacchi.txt**, e dentro quel file troverete anche le modalità spiegate dal prof al lab per fixarle. Il file **da_modificare.txt** lo utilizzo per tenere traccia delle cose da fare per ogni sezione come login, signup, password_recovery, ecc. Nell'ultimo file specificato non ho incluso ogni singolo file (ad esempio, signup per l'utente include il file be_registration.php e activation.php), ma è una roadmap generale per vedere cosa manca. Io ora ho fixato tutto nel signup e mancavano solo i log nel login e ho migliorato alcuni files a livello di leggibilità.

## SUMMARY
Credo di non aver dimenticato niente, nel caso aveste qualche dubbio contattatemi. Io questi ultimi giorni non ci sto lavorando perchè sto preparando Network Security per il 4 giugno, ma rinizierò presto.