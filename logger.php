<?php
    function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ipList[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    function logs_webapp(string $message, string $username, string $file) {
        $logs_dir = "/var/www/novelist_logs/";

        if (!is_dir($logs_dir) || !is_writable($logs_dir)) {
            error_log("Log dir not accessible: $logs_dir");
            return;
        }

        $full_path = $logs_dir . $file;
        
        $log = fopen($full_path, "a");
        if ($log) {
            $timestamp = date("d-m-Y H:i:s");
            $log_mes = "[$timestamp] $username: $message\n";
            fwrite($log, $log_mes);
            fclose($log);
        } else {
            error_log("Cannot write on $full_path");
        }
    }
?>