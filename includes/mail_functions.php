<?php
// PHPMailer Pfad anpassen nach Composer-Installation
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Sendet eine E-Mail mit PHPMailer
 * @param string $to Empfänger-E-Mail
 * @param string $subject Betreff
 * @param string $body Nachrichtentext
 * @param bool $isHtml Ob der Body HTML-Inhalt enthält
 * @return bool True bei Erfolg, false bei Fehler
 */
function sendEmailWithPHPMailer($to, $subject, $body, $isHtml = false) {
    $settings = getEmailSettings();
    
    try {
        $mail = new PHPMailer(true);
        
        // Server-Einstellungen
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_username'];
        $mail->Password = $settings['smtp_password'];
        
        if (!empty($settings['smtp_encryption'])) {
            $mail->SMTPSecure = $settings['smtp_encryption'];
        }
        
        if (!empty($settings['smtp_port'])) {
            $mail->Port = $settings['smtp_port'];
        }
        
        // Empfänger
        $mail->setFrom($settings['from_email'], $settings['from_name']);
        $mail->addAddress($to);
        $mail->CharSet = 'UTF-8';
        
        // Inhalt
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Wenn HTML, dann auch Plain-Text-Version erstellen
        if ($isHtml) {
            $mail->AltBody = strip_tags($body);
        }
        
        return $mail->send();
    } catch (Exception $e) {
        // Bei Fehler Fallback auf einfache mail()-Funktion
        return sendEmailWithPHPMail($to, $subject, $body, $isHtml);
    }
}

/**
 * Sendet eine E-Mail mit PHP's mail()-Funktion
 * @param string $to Empfänger-E-Mail
 * @param string $subject Betreff
 * @param string $body Nachrichtentext
 * @param bool $isHtml Ob der Body HTML-Inhalt enthält
 * @return bool True bei Erfolg, false bei Fehler
 */
function sendEmailWithPHPMail($to, $subject, $body, $isHtml = false) {
    $settings = getEmailSettings();
    
    // E-Mail-Header erstellen
    $headers = '';
    $headers .= 'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>' . "\r\n";
    $headers .= 'Reply-To: ' . $settings['from_email'] . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    
    if ($isHtml) {
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
    } else {
        $headers .= 'Content-type: text/plain; charset=UTF-8' . "\r\n";
    }
    
    // E-Mail senden
    return mail($to, $subject, $body, $headers);
}

/**
 * Sendet eine E-Mail
 * @param string $to Empfänger-E-Mail
 * @param string $subject Betreff
 * @param string $body Nachrichtentext
 * @param bool $isHtml Ob der Body HTML-Inhalt enthält
 * @return bool True bei Erfolg, false bei Fehler
 */
function sendEmail($to, $subject, $body, $isHtml = false) {
    $settings = getEmailSettings();
    
    // Falls SMTP verwendet wird, PHPMailer verwenden
    if ($settings['use_smtp'] && !empty($settings['smtp_host'])) {
        return sendEmailWithPHPMailer($to, $subject, $body, $isHtml);
    } else {
        // Andernfalls einfaches mail() verwenden
        return sendEmailWithPHPMail($to, $subject, $body, $isHtml);
    }
}

/**
 * Formatiert E-Mail-Body in HTML-Format mit Styling für bessere Darstellung
 * @param string $content Inhalt der E-Mail
 * @param string $title Titel der E-Mail (optional)
 * @return string Formatierter HTML-Body
 */
function formatEmailHtml($content, $title = '') {
    $settings = getSiteSettings();
    $logoHtml = '';
    
    // Logo einbinden, falls vorhanden
    if (!empty($settings['logo_path'])) {
        $logoUrl = BASE_URL . '/' . $settings['logo_path'];
        $logoHtml = '<div style="text-align: center; margin-bottom: 20px;"><img src="' . $logoUrl . '" alt="' . h($settings['company_name']) . '" style="max-width: 200px; max-height: 80px;"></div>';
    }
    
    // Firmeninformationen für Footer
    $companyInfo = '';
    if (!empty($settings['company_name'])) {
        $companyInfo .= $settings['company_name'];
        
        if (!empty($settings['company_address'])) {
            $companyInfo .= ' | ' . $settings['company_address'];
        }
        
        if (!empty($settings['company_email'])) {
            $companyInfo .= ' | ' . $settings['company_email'];
        }
        
        if (!empty($settings['company_phone'])) {
            $companyInfo .= ' | ' . $settings['company_phone'];
        }
    }
    
    // Primärfarbe aus Einstellungen holen oder Standard verwenden
    $primaryColor = $settings['primary_color'] ?? '#3498db';
    
    // HTML-Template mit Styling
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . h($title) . '</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    ' . $logoHtml . '
    <div style="background-color: #f8f9fa; border-radius: 5px; padding: 20px; margin-bottom: 20px; border-left: 5px solid ' . $primaryColor . ';">
        ' . ($title ? '<h2 style="margin-top: 0; color: #444;">' . h($title) . '</h2>' : '') . '
        <div style="color: #555;">
            ' . nl2br(h($content)) . '
        </div>
    </div>
    <div style="font-size: 0.9em; color: #777; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
        ' . $companyInfo . '
    </div>
</body>
</html>';

    return $html;
}