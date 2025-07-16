<?php
/**
 * ===== GESTIONNAIRE D'EMAILS - SITE REMMAILLEUSE =====
 * Système centralisé pour l'envoi d'emails avec notifications admin
 * Compatible Infomaniak/PHP 8+
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class EmailManager {
    private $settings;
    private $logFile;
    
    public function __construct($settingsPath = '../data/settings.json') {
        $this->loadSettings($settingsPath);
        $this->logFile = '../logs/email.log';
        $this->createLogDirectory();
    }
    
    /**
     * Charge les paramètres depuis settings.json
     */
    private function loadSettings($settingsPath) {
        if (!file_exists($settingsPath)) {
            throw new Exception("Fichier de configuration non trouvé: $settingsPath");
        }
        
        $content = file_get_contents($settingsPath);
        $this->settings = json_decode($content, true);
        
        if (!$this->settings) {
            throw new Exception("Erreur lors du décodage du fichier de configuration");
        }
    }
    
    /**
     * Crée le répertoire de logs s'il n'existe pas
     */
    private function createLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Enregistre un événement dans les logs (déprécié, utilise le système centralisé)
     */
    private function log($message, $level = 'INFO') {
        // Utiliser le système de logging centralisé
        if (function_exists('logAPI')) {
            logAPI($message, [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ], $level);
        } else {
            // Fallback vers l'ancien système
            $timestamp = date('Y-m-d H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $logEntry = "[$timestamp] [$level] IP:$ip - $message\n";
            file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Envoie un email de contact standard
     */
    public function sendContactEmail($data, $uploadedFiles = []) {
        try {
            $config = $this->settings['email']['contact'];
            
            $to = $config['to'];
            $from = $config['from'];
            $subject = $config['subject_prefix'] . 'Nouvelle demande de devis';
            
            $message = $this->buildContactMessage($data, $uploadedFiles);
            $headers = $this->buildHeaders($from, $data['email']);
            
            $emailSent = mail($to, $subject, $message, $headers);
            
            if ($emailSent) {
                $this->log("Email de contact envoyé avec succès à $to");
                
                // Log avec le système centralisé
                if (function_exists('logAPI')) {
                    logAPI('Email de contact envoyé avec succès', [
                        'to' => $to,
                        'from' => $from,
                        'client_name' => $data['firstname'] . ' ' . $data['lastname'],
                        'client_email' => $data['email'],
                        'files_count' => count($uploadedFiles),
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ], 'INFO');
                }
                
                // Envoie la confirmation au client
                $this->sendContactConfirmation($data);
                
                // Envoie la notification admin si activée
                if ($this->settings['email']['admin']['notify_on_contact']) {
                    $this->sendAdminNotification('new_contact', $data, $uploadedFiles);
                }
                
                return true;
            } else {
                $this->log("Échec envoi email de contact à $to", 'ERROR');
                
                // Log avec le système centralisé
                if (function_exists('logError')) {
                    logError('Échec envoi email de contact', [
                        'to' => $to,
                        'from' => $from,
                        'client_name' => $data['firstname'] . ' ' . $data['lastname'],
                        'client_email' => $data['email'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ], 'email');
                }
                
                return false;
            }
            
        } catch (Exception $e) {
            $this->log("Erreur sendContactEmail: " . $e->getMessage(), 'ERROR');
            
            // Log avec le système centralisé
            if (function_exists('logError')) {
                logError('Erreur sendContactEmail', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ], 'email');
            }
            
            return false;
        }
    }
    
    /**
     * Envoie un email de confirmation au client
     */
    private function sendContactConfirmation($data) {
        try {
            $config = $this->settings['email']['contact'];
            
            $to = $data['email'];
            $from = $config['from'];
            $subject = 'Confirmation de votre demande - Remmailleuse';
            
            $message = $this->buildConfirmationMessage($data);
            $headers = $this->buildHeaders($from);
            
            $emailSent = mail($to, $subject, $message, $headers);
            
            if ($emailSent) {
                $this->log("Email de confirmation envoyé à $to");
            } else {
                $this->log("Échec envoi confirmation à $to", 'WARNING');
            }
            
        } catch (Exception $e) {
            $this->log("Erreur sendContactConfirmation: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Envoie une notification à l'admin
     */
    public function sendAdminNotification($type, $data, $uploadedFiles = []) {
        try {
            $adminConfig = $this->settings['email']['admin'];
            
            if (!$adminConfig['enabled']) {
                return false;
            }
            
            $to = $adminConfig['to'];
            $from = $adminConfig['from'];
            $subject = $adminConfig['subject_prefix'] . $this->getNotificationSubject($type);
            
            $message = $this->buildAdminMessage($type, $data, $uploadedFiles);
            $headers = $this->buildHeaders($from);
            
            // Ajouter le format HTML si configuré
            if ($adminConfig['format'] === 'html') {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $message = $this->formatHtmlMessage($message);
            }
            
            $emailSent = mail($to, $subject, $message, $headers);
            
            if ($emailSent) {
                $this->log("Notification admin envoyée: $type");
                
                // Log avec le système centralisé
                if (function_exists('logAPI')) {
                    logAPI('Notification admin envoyée', [
                        'type' => $type,
                        'to' => $to,
                        'from' => $from,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ], 'INFO');
                }
                
                return true;
            } else {
                $this->log("Échec notification admin: $type", 'ERROR');
                
                // Log avec le système centralisé
                if (function_exists('logError')) {
                    logError('Échec notification admin', [
                        'type' => $type,
                        'to' => $to,
                        'from' => $from,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ], 'email');
                }
                
                return false;
            }
            
        } catch (Exception $e) {
            $this->log("Erreur sendAdminNotification: " . $e->getMessage(), 'ERROR');
            
            // Log avec le système centralisé
            if (function_exists('logError')) {
                logError('Erreur sendAdminNotification', [
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ], 'email');
            }
            
            return false;
        }
    }
    
    /**
     * Construit le message de contact
     */
    private function buildContactMessage($data, $uploadedFiles) {
        $message = "=== NOUVELLE DEMANDE DE DEVIS ===\n\n";
        $message .= "Prénom: {$data['firstname']}\n";
        $message .= "Nom: {$data['lastname']}\n";
        $message .= "Email: {$data['email']}\n";
        $message .= "Téléphone: " . ($data['phone'] ?: 'Non renseigné') . "\n\n";
        $message .= "Message:\n" . wordwrap($data['message'], 70) . "\n\n";
        
        if (!empty($uploadedFiles)) {
            $message .= "=== PHOTOS JOINTES ===\n";
            foreach ($uploadedFiles as $file) {
                $message .= "- {$file['original_name']} (" . round($file['size']/1024) . " Ko)\n";
            }
            $message .= "\nLes photos sont disponibles dans le dossier uploads/contact/\n\n";
        }
        
        $message .= "=== INFORMATIONS TECHNIQUES ===\n";
        $message .= "Date: " . date('d/m/Y à H:i:s') . "\n";
        $message .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "\n";
        $message .= "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu') . "\n";
        
        return $message;
    }
    
    /**
     * Construit le message de confirmation
     */
    private function buildConfirmationMessage($data) {
        $message = "Bonjour {$data['firstname']},\n\n";
        $message .= "Merci pour votre demande de devis pour mes services de remaillage.\n\n";
        $message .= "Votre message a bien été reçu et je vous répondrai dans les plus brefs délais (généralement sous 24h).\n\n";
        $message .= "À bientôt,\n";
        $message .= "Mme Monod\n";
        $message .= "Artisane Remmailleuse\n\n";
        $message .= "---\n";
        $message .= "Rappel de votre demande:\n";
        $message .= wordwrap($data['message'], 70) . "\n";
        
        return $message;
    }
    
    /**
     * Construit le message de notification admin
     */
    private function buildAdminMessage($type, $data, $uploadedFiles) {
        $message = "";
        
        switch ($type) {
            case 'new_contact':
                $message .= "🔔 NOUVELLE DEMANDE DE CONTACT\n\n";
                $message .= "Client: {$data['firstname']} {$data['lastname']}\n";
                $message .= "Email: {$data['email']}\n";
                $message .= "Téléphone: " . ($data['phone'] ?: 'Non renseigné') . "\n\n";
                $message .= "Message:\n" . $data['message'] . "\n\n";
                
                if (!empty($uploadedFiles)) {
                    $message .= "📎 Fichiers joints: " . count($uploadedFiles) . "\n";
                    foreach ($uploadedFiles as $file) {
                        $message .= "- {$file['original_name']}\n";
                    }
                    $message .= "\n";
                }
                
                $message .= "🕐 Reçu le: " . date('d/m/Y à H:i:s') . "\n";
                $message .= "🌐 IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "\n";
                break;
                
            case 'upload_error':
                $message .= "⚠️ ERREUR D'UPLOAD\n\n";
                $message .= "Détails: " . ($data['error'] ?? 'Erreur inconnue') . "\n";
                $message .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "\n";
                $message .= "Date: " . date('d/m/Y à H:i:s') . "\n";
                break;
                
            case 'security_alert':
                $message .= "🚨 ALERTE SÉCURITÉ\n\n";
                $message .= "Type: " . ($data['type'] ?? 'Alerte générale') . "\n";
                $message .= "Détails: " . ($data['details'] ?? 'Aucun détail') . "\n";
                $message .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "\n";
                $message .= "Date: " . date('d/m/Y à H:i:s') . "\n";
                break;
                
            default:
                $message .= "📧 NOTIFICATION ADMIN\n\n";
                $message .= "Type: $type\n";
                $message .= "Date: " . date('d/m/Y à H:i:s') . "\n";
        }
        
        return $message;
    }
    
    /**
     * Retourne le sujet de la notification selon le type
     */
    private function getNotificationSubject($type) {
        switch ($type) {
            case 'new_contact':
                return 'Nouvelle demande de contact';
            case 'upload_error':
                return 'Erreur d\'upload';
            case 'security_alert':
                return 'Alerte sécurité';
            default:
                return 'Notification';
        }
    }
    
    /**
     * Construit les headers d'email
     */
    private function buildHeaders($from, $replyTo = null) {
        $headers = "From: $from\r\n";
        
        if ($replyTo) {
            $headers .= "Reply-To: $replyTo\r\n";
        }
        
        $headers .= "X-Mailer: Site Remmailleuse\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return $headers;
    }
    
    /**
     * Formate le message en HTML
     */
    private function formatHtmlMessage($message) {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<meta charset='UTF-8'>\n";
        $html .= "<style>\n";
        $html .= "body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }\n";
        $html .= "h1 { color: #673ab7; }\n";
        $html .= ".info { background: #f5f5f5; padding: 10px; border-radius: 5px; }\n";
        $html .= ".alert { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; }\n";
        $html .= ".error { background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; }\n";
        $html .= "</style>\n</head>\n<body>\n";
        
        // Convertir le texte en HTML
        $html .= "<pre>" . htmlspecialchars($message) . "</pre>\n";
        
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Envoie une alerte de sécurité
     */
    public function sendSecurityAlert($type, $details) {
        if (!$this->settings['email']['admin']['notify_on_errors']) {
            return false;
        }
        
        $data = [
            'type' => $type,
            'details' => $details
        ];
        
        return $this->sendAdminNotification('security_alert', $data);
    }
    
    /**
     * Envoie une notification d'erreur d'upload
     */
    public function sendUploadError($error) {
        if (!$this->settings['email']['admin']['notify_on_upload']) {
            return false;
        }
        
        $data = [
            'error' => $error
        ];
        
        return $this->sendAdminNotification('upload_error', $data);
    }
    
    /**
     * Teste l'envoi d'email
     */
    public function testEmail($to = null) {
        try {
            $adminConfig = $this->settings['email']['admin'];
            
            $to = $to ?: $adminConfig['to'];
            $from = $adminConfig['from'];
            $subject = 'Test Email - Remmailleuse';
            
            $message = "Test d'envoi d'email effectué avec succès.\n\n";
            $message .= "Date: " . date('d/m/Y à H:i:s') . "\n";
            $message .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "\n";
            
            $headers = $this->buildHeaders($from);
            
            $emailSent = mail($to, $subject, $message, $headers);
            
            if ($emailSent) {
                $this->log("Email de test envoyé avec succès à $to");
                return true;
            } else {
                $this->log("Échec envoi email de test à $to", 'ERROR');
                return false;
            }
            
        } catch (Exception $e) {
            $this->log("Erreur testEmail: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}