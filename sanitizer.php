<?php

class InputSanitizer {

    public function sanitizeString($input): ?string {
        if (!is_string($input)) return null;

        // Remove tags, html char and string terminator
        $sanitezed = trim($input);
        $sanitezed = strip_tags($input);
        $sanitezed = htmlspecialchars($sanitezed, ENT_QUOTES, 'UTF-8');
        $sanitezed = str_replace("\0", "", $sanitezed);

        return $sanitezed;
    }
    
    public function sanitizeEmail($input): ?string {
        if (!is_string($input)) return null;

        // Remove all string terminator and sanitize email
        $email = trim($input);
        $email = str_replace(["\r", "\n", "%0a", "%0d"], '', $email); // prevent header injection
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return false;
    }

    public function sanitizeUsername($input): ?string {
        if (!is_string($input)) return null;

        // Remove all tags and allow only safe char
        $username = trim($input);
        $username = strip_tags($username);
        $username = preg_replace('/[^a-zA-Z0-9_\.\-]/', '', $username);

        // Limit username length
        $username = substr($username, 0, 20);

        return $username;
    }

    public function validatePassword($password): bool {
        // Check if password is a string and its length
        if (!is_string($password) || strlen($password) < 8) return false;

        // Check if password contains only allowed characters
        return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&_-])[A-Za-z\d@$!%*?&_-]{8,64}$/', $password);
    }

    /**
     * Genera un token anti-CSRF da salvare in sessione
     */
    public function generateCSRFToken(): string {
        return bin2hex(random_bytes(32));
    }

    /**
     * Valida un token CSRF fornito rispetto a quello salvato
     */
    public function validateCSRFToken(string $token, string $sessionToken): bool {
        return hash_equals($sessionToken, $token);
    }
}
