<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    class EmailService {
        private $mailer;
        
        public function __construct() {            
            $this->initializeMailer();
        }
        
        private function initializeMailer() {
            $this->mailer = new PHPMailer(true);
            
            try {
                // Server config
                // $this->mailer->SMTPDebug  = SMTP::DEBUG_SERVER;  // Debug (activate this during testing)
                $this->mailer->isSMTP();
                $this->mailer->Host       = trim(file_get_contents('/run/www-data_secrets/smtp_host'));
                $this->mailer->SMTPAuth   = true;
                $this->mailer->Username   = trim(file_get_contents('/run/www-data_secrets/smtp_username'));
                $this->mailer->Password   = trim(file_get_contents('/run/www-data_secrets/smtp_password'));
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // PHPMailer::ENCRYPTION_STARTTLS (to use tls and port 587)
                $this->mailer->Port       = trim(file_get_contents('/run/www-data_secrets/smtp_port'));
                
                // SSL/TLS cert config
                $this->mailer->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => true,
                        'verify_peer_name' => true,
                        'allow_self_signed' => false
                    ]
                ];
                
                // Default settings
                $this->mailer->setFrom($this->mailer->Username, 'Novelist Space');
                $this->mailer->CharSet = 'UTF-8';
                $this->mailer->isHTML(true);
                
            } catch (Exception $e) {
                throw new Exception("Mailer init failed: " . $e->getMessage());
            }
        }
        
        /**
         * Send an email
         * 
         * @param string|array $to Receiver(s)
         * @param string $subject Email subject
         * @param string $body Email body (HTML)
         * @param string|null $altBody Alternative body (plaintext)
         * @param array $attachments Array of file paths
         * @return bool
         * @throws Exception
         */
        public function sendEmail($to, $subject, $body, $altBody = null, $link = null) {
            
            try {
                $this->mailer->clearAddresses();
                $this->mailer->clearAttachments();
                
                // Add receivers
                if (is_array($to)) {
                    foreach ($to as $address) {
                        $this->mailer->addAddress($address);
                    }
                } else {
                    $this->mailer->addAddress($to);
                }
                
                // Set subject 
                $this->mailer->Subject = $subject;
                
                // Read the email file
                if (file_exists($body)) {
                    $html_content = file_get_contents($body);
                    if ($link != null) {
                        $html_content = str_replace('{LINK}', $link, $html_content);
                    }

                    // Configure HTMLPurifier
                    $config = HTMLPurifier_Config::createDefault();
                    $config->set('HTML.Allowed', 'div[style],h1[style],p[style],strong,table[style],tr,td,a[href|style],br');
                    $config->set('CSS.AllowedProperties', 'font-family,background-color,color,margin,padding,max-width,text-align,border-bottom,font-size,line-height,margin-bottom,width,text-decoration,font-weight,border-top,margin-top');
                    $purifier = new HTMLPurifier($config);
                    
                    // Sanitize the file content
                    $body = $purifier->purify($html_content);
                    $this->mailer->Body = $body;
                } else {
                    $this->mailer->AltBody = $altBody;
                }
                
                // Send data
                $result = $this->mailer->send();
                if (!$result) throw new Exception('Failed to send email');
                
            } catch (Exception $e) {
                throw new Exception("Error sending email: " . $e->getMessage());
            }
        }
        
        /**
         * SMTP connection check
         * 
         * @return bool
         * @throws Exception
         */
        public function testConnection() {
            
            try {
                if ($this->mailer->smtpConnect()) {
                    $this->mailer->smtpClose();
                    return true;
                }
                return false;
            } catch (Exception $e) {
                throw new Exception("Test connection failed: " . $e->getMessage());
            }
        }
    }
?>