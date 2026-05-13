<?php
/**
 * WhatsApp Service Wrapper - webora Integration
 * Handles sending WhatsApp messages via Twilio API.
 */

require_once __DIR__ . '/../controller/src/Twilio/autoload.php'; // Correct path to Twilio SDK
use Twilio\Rest\Client;

/**
 * Send a WhatsApp message.
 * 
 * @param string $phone   Phone number (with or without country code)
 * @param string $message The message content
 * @return bool True if success, false otherwise
 */
function sendWhatsApp(string $phone, string $message): bool {
    // 1. Load credentials from environment
    $sid      = $_ENV['TWILIO_WHATSAPP_SID'] ?? $_ENV['TWILIO_SID'] ?? '';
    $token    = $_ENV['TWILIO_WHATSAPP_TOKEN'] ?? $_ENV['TWILIO_TOKEN'] ?? '';
    $fromNum  = $_ENV['TWILIO_WHATSAPP_FROM'] ?? $_ENV['TWILIO_FROM'] ?? '';

    if (empty($sid) || empty($token) || empty($fromNum)) {
        error_log('[LegalFin] WhatsApp credentials manquants dans .env');
        return false;
    }

    // Ensure fromNum starts with 'whatsapp:' prefix
    if (strpos($fromNum, 'whatsapp:') !== 0) {
        $fromNum = 'whatsapp:' . (strpos($fromNum, '+') === 0 ? '' : '+') . $fromNum;
    }

    // 2. Format phone number (Twilio requires whatsapp:+COUNTRY_CODE_NUMBER)
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) === 8) {
        $cleanPhone = '216' . $cleanPhone; // Default to Tunisia if 8 digits
    }
    $toNum = 'whatsapp:+' . $cleanPhone;

    // 3. Send via Twilio
    try {
        $client = new Client($sid, $token);
        $client->messages->create($toNum, [
            'from' => $fromNum,
            'body' => $message,
        ]);
        error_log("[LegalFin] WhatsApp envoyé avec succès à $toNum");
        return true;
    } catch (Exception $e) {
        error_log('[LegalFin] Twilio Error (WhatsApp): ' . $e->getMessage());
        return false;
    }
}
