<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la mise en tampon de sortie
ob_start();

// Importer les classes nécessaires
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Fonction de gestion des erreurs
function handleError($errno, $errstr, $errfile, $errline) {
    $error = [
        'success' => false,
        'message' => "Erreur PHP: $errstr dans $errfile à la ligne $errline"
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($error, JSON_UNESCAPED_UNICODE);
    exit;
}

// Définir le gestionnaire d'erreurs
set_error_handler('handleError');

try {
    require_once 'config.php';
    require_once 'translations.php';
    require 'vendor/autoload.php';

    // Fonction de journalisation
    function logError($message) {
        $logFile = 'contact_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0666);
        }
        
        error_log($logMessage, 3, $logFile);
    }

    // Fonction de validation d'email stricte
    function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

        // Séparer nom d'utilisateur et domaine
        [$user, $domain] = explode('@', $email, 2);

        // Nom d'utilisateur : 2 à 64 caractères, lettres, chiffres, points, tirets, underscores
        if (strlen($user) < 2 || strlen($user) > 64) return false;
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $user)) return false;
        if (preg_match('/(.)\\1{3,}/', $user)) return false; // 4 caractères identiques consécutifs

        // Domaines gratuits à bloquer (optionnel, à adapter)
        $blockedDomains = ['mailinator.com','yopmail.com','tempmail','10minutemail','guerrillamail','sharklasers','spamgourmet'];
        foreach ($blockedDomains as $bad) {
            if (stripos($domain, $bad) !== false) return false;
        }

        // Mots-clés suspects
        $badWords = ['test','spam','fake','demo','null','invalid'];
        foreach ($badWords as $bad) {
            if (stripos($user, $bad) !== false) return false;
        }

        // Vérification DNS
        if (!emailDomainExists($email)) return false;

        return true;
    }

    // Fonction de nettoyage des entrées améliorée
    function cleanInput($data) {
        // Suppression des espaces en début et fin
        $data = trim($data);
        
        // Conversion des caractères spéciaux en entités HTML
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        // Suppression des caractères de contrôle
        $data = preg_replace('/[\x00-\x1F\x7F]/u', '', $data);
        
        return $data;
    }

    // Vérification DNS du domaine email
    function emailDomainExists($email) {
        $domain = substr(strrchr($email, '@'), 1);
        
        // Si checkdnsrr n'est pas disponible, on utilise une vérification alternative
        if (!function_exists('checkdnsrr')) {
            // Vérification basique du format du domaine
            return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $domain);
        }
        
        return checkdnsrr($domain, 'MX');
    }

    // Validation avancée du nom
    function validateName($name) {
        if (strlen($name) < 2 || strlen($name) > 100) return false;
        if (!preg_match('/^[\p{L} .\'-]+$/u', $name)) return false; // Lettres, espaces, tirets, apostrophes
        if (preg_match('/(http|www\.|@|\.com|\.fr|\.net)/i', $name)) return false;
        if (preg_match('/(.)\1{3,}/', $name)) return false; // 4 caractères identiques consécutifs
        return true;
    }

    // Validation avancée du sujet
    function validateSubject($subject) {
        if (strlen($subject) < 3 || strlen($subject) > 200) return false;
        if (preg_match('/(http|www\.|@|\.com|\.fr|\.net)/i', $subject)) return false;
        if (preg_match('/(.)\1{4,}/', $subject)) return false;
        $spamWords = ['test', 'buy now', 'free', 'promo', 'viagra'];
        foreach ($spamWords as $word) {
            if (stripos($subject, $word) !== false) return false;
        }
        return true;
    }

    // Validation avancée du message
    function validateMessage($message) {
        if (strlen($message) < 10 || strlen($message) > 5000) return false;
        if (preg_match('/<script|javascript:|data:/i', $message)) return false;
        if (preg_match('/(http|www\.|@|\.com|\.fr|\.net)/i', $message)) return false;
        if (preg_match('/(.)\1{6,}/', $message)) return false;
        return true;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Nettoyer le buffer de sortie
        ob_clean();
        
        $response = ['success' => false, 'message' => ''];

        try {
            // Vérifier si PHPMailer est correctement installé
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                throw new Exception('PHPMailer n\'est pas correctement installé.');
            }

            // Charger la configuration SMTP
            $config = require 'config.env.php';
            if (!isset($config['smtp'])) {
                throw new Exception('Configuration SMTP manquante.');
            }
            $smtp = $config['smtp'];

            // Vérification honeypot anti-spam
            $honeypot = $_POST['website'] ?? '';
            if (!empty($honeypot)) {
                throw new Exception('Spam détecté.');
            }

            // Récupération et nettoyage des données du formulaire
            $name = cleanInput($_POST['name'] ?? '');
            $email = cleanInput($_POST['email'] ?? '');
            $subject = cleanInput($_POST['subject'] ?? '');
            $message = cleanInput($_POST['msg'] ?? '');

            // Validation des données
            if (!validateName($name)) {
                throw new Exception('Le nom est invalide ou suspect.');
            }
            if (!validateEmail($email)) {
                throw new Exception('Adresse email invalide.');
            }
            if (!validateSubject($subject)) {
                throw new Exception('Le sujet est invalide ou suspect.');
            }
            if (!validateMessage($message)) {
                throw new Exception('Le message est invalide ou suspect.');
            }

            // Journalisation des données reçues
            logError("Tentative d'envoi - Nom: " . substr($name, 0, 3) . "***, Email: " . substr($email, 0, 3) . "***, Sujet: $subject");

            // Configuration de PHPMailer
            $mail = new PHPMailer(true);
            
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->Debugoutput = function($str, $level) {
                logError("PHPMailer Debug: $str");
            };

            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->Port = $smtp['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 30;
            
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false
                )
            );

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($smtp['to_email'], $smtp['to_name']);
            $mail->addReplyTo($email, $name);

            $mail->XMailer = 'Portfolio Contact Form';
            $mail->Priority = 3;

            $mail->isHTML(true);
            $mail->Subject = "Nouveau message de contact : " . substr($subject, 0, 100);
            
            // Template d'email
            $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <style>
                        body {
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            line-height: 1.6;
                            color: #2c3e50;
                            max-width: 600px;
                            margin: 0 auto;
                            padding: 0;
                            background-color: #f8f9fa;
                        }
                        .email-container {
                            background-color: #ffffff;
                            border-radius: 8px;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                            margin: 20px;
                            overflow: hidden;
                        }
                        .header {
                            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
                            color: white;
                            padding: 30px 20px;
                            text-align: center;
                        }
                        .header h2 {
                            margin: 0;
                            font-size: 24px;
                            font-weight: 600;
                        }
                        .content {
                            padding: 30px 20px;
                            background-color: #ffffff;
                        }
                        .field {
                            margin-bottom: 20px;
                            padding: 15px;
                            background-color: #f8f9fa;
                            border-radius: 6px;
                            border-left: 4px solid #4CAF50;
                        }
                        .label {
                            font-weight: 600;
                            color: #4CAF50;
                            display: block;
                            margin-bottom: 5px;
                            font-size: 14px;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        }
                        .value {
                            color: #2c3e50;
                            font-size: 16px;
                        }
                        .message {
                            background-color: #f8f9fa;
                            padding: 20px;
                            border-radius: 6px;
                            margin-top: 10px;
                            border: 1px solid #e9ecef;
                        }
                        .message-content {
                            color: #2c3e50;
                            font-size: 16px;
                            line-height: 1.6;
                            white-space: pre-wrap;
                        }
                        .footer {
                            text-align: center;
                            padding: 20px;
                            background-color: #f8f9fa;
                            border-top: 1px solid #e9ecef;
                            color: #6c757d;
                            font-size: 14px;
                        }
                        .footer p {
                            margin: 0;
                        }
                        .timestamp {
                            font-size: 12px;
                            color: #6c757d;
                            text-align: right;
                            padding: 10px 20px;
                            background-color: #f8f9fa;
                            border-top: 1px solid #e9ecef;
                        }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                        <div class='header'>
                            <h2>Nouveau message de contact</h2>
                        </div>
                        <div class='content'>
                            <div class='field'>
                                <span class='label'>Nom</span>
                                <span class='value'>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</span>
                            </div>
                            <div class='field'>
                                <span class='label'>Email</span>
                                <span class='value'>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</span>
                            </div>
                            <div class='field'>
                                <span class='label'>Sujet</span>
                                <span class='value'>" . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . "</span>
                            </div>
                            <div class='field'>
                                <span class='label'>Message</span>
                                <div class='message'>
                                    <div class='message-content'>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</div>
                                </div>
                            </div>
                        </div>
                        <div class='timestamp'>
                            Message reçu le " . date('d/m/Y à H:i') . "
                        </div>
                        <div class='footer'>
                            <p>Ce message a été envoyé depuis le formulaire de contact de votre portfolio.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            // Tentative d'envoi
            logError("Tentative d'envoi de l'email...");
            if (!$mail->send()) {
                throw new Exception("Erreur d'envoi : " . $mail->ErrorInfo);
            }
            logError("Email envoyé avec succès!");

            $response['success'] = true;
            $response['message'] = 'Votre message a été envoyé avec succès.';

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            logError("Erreur d'envoi d'email : " . $errorMessage);
            $response['message'] = $errorMessage;
        }

        // Nettoyer le buffer de sortie
        ob_clean();
        
        // Envoyer les headers
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // Encoder la réponse en JSON
        $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Vérifier si l'encodage JSON a réussi
        if ($jsonResponse === false) {
            $response = [
                'success' => false,
                'message' => 'Erreur lors de la génération de la réponse JSON: ' . json_last_error_msg()
            ];
            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        // Envoyer la réponse
        echo $jsonResponse;
        
        // Terminer le script
        exit;
    }

    // Si ce n'est pas une requête POST, renvoyer une erreur
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('HTTP/1.1 405 Method Not Allowed');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Méthode non autorisée'
        ]);
        exit;
    }

} catch (Throwable $e) {
    // Gestion des erreurs fatales
    $error = [
        'success' => false,
        'message' => "Erreur fatale: " . $e->getMessage()
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($error, JSON_UNESCAPED_UNICODE);
    exit;
} 