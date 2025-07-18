<?php

class InputSanitizer {

    public function sanitizeString($input): ?string {
        if (!isset($input) || !is_string($input)) return null;

        // Remove tags, html char and string terminator
        $sanitezed = trim($input);
        $sanitezed = strip_tags($sanitezed);
        $sanitezed = str_replace("\0", "", $sanitezed);

        return $sanitezed;
    }
    
    public function sanitizeEmail($input): ?string {
        if (!isset($input) || !is_string($input)) return null;

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
        if (!isset($input) || !is_string($input)) return null;

        // Remove all tags and allow only safe char
        $username = trim($input);
        $username = strip_tags($username);
        $username = preg_replace('/[^a-zA-Z0-9_\.\-]/', '', $username);

        // Limit username length
        $username = substr($username, 0, 20);

        return $username;
    }

    public function validatePassword($password): bool {
        if (!isset($password) || !is_string($password)) return false;
        // Check if password is a string and its length
        if (strlen($password) < 8) return false;

        // Check if password contains only allowed characters
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{}\[\]|;:\'",.<>?\\/`~]).{8,64}$/', $password);
    }

    public function validateResetToken($token): bool {
        if (!isset($token) || !is_string($token)) {
            return false;
        }

        // Controlla che sia lungo 64 caratteri ed esadecimale
        return preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
    }

    public function sanitizeMultiLineString($input): ?string {
        if (!isset($input) || !is_string($input)) return null;

        // Remove tags, html char and string terminator
        $sanitezed = trim($input);
        $sanitezed = strip_tags($sanitezed);
        $sanitezed = str_replace("\0", "", $sanitezed);

        // Convert newline in unix format
        $sanitezed = str_replace(["\r\n", "\r"], "\n", $sanitezed);
        return $sanitezed;
    }
}
