CREATE TABLE IF NOT EXISTS us3rs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(20) NOT NULL UNIQUE,
    password CHAR(255) NOT NULL, -- bcrypt spawn a 60 byte digest but in case default alg changes, we can leave space for each future algorithm
    ispremium BOOLEAN DEFAULT FALSE,
    complete BOOLEAN DEFAULT FALSE,
    access_attemp INT DEFAULT 0,
    locked BOOLEAN DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS us3r_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password CHAR(255) NOT NULL, -- bcrypt spawn a 60 byte digest but in case default alg changes, we can leave space for each future algorithm
    access_attempt INT DEFAULT 0,
    locked BOOLEAN DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS novels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uploader_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    author_name VARCHAR(255) NOT NULL, -- Author's name
    genre VARCHAR(255) NOT NULL,
    free BOOLEAN NOT NULL DEFAULT 0, -- 0 = Free, 1 = Premium
    novel_type BOOLEAN NOT NULL DEFAULT 0, -- 0 = Short (TXT), 1 = Long (PDF)
    file_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (uploader_id) REFERENCES us3rs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expiry INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES us3rs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS registration_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expiry TIMESTAMP NOT NULL,   -- Data di scadenza del token
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Data di creazione del token
    FOREIGN KEY (user_id) REFERENCES us3rs(id) ON DELETE CASCADE
);


