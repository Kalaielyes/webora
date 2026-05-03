<?php
/**
 * Webora WhatsApp Sender - Twilio API Integration
 */

// =============================================
//  YOUR TWILIO CREDENTIALS - EDIT THESE
// =============================================
// =============================================
require_once __DIR__ . 'config/twilioconfig.php';
/**
 * Send a WhatsApp message via Twilio REST API
 * 
 * @param string $to The recipient phone number (e.g., +21612345678)
 * @param string $message The message body
 * @return bool
 */
function sendWhatsApp($to, $message) {
    if (empty(TWILIO_SID) || TWILIO_SID === 'YOUR_TWILIO_ACCOUNT_SID') {
        error_log("Twilio SID not configured.");
        return false;
    }

    // Ensure phone number starts with whatsapp:
    if (strpos($to, 'whatsapp:') !== 0) {
        // If it's just a number, add + and whatsapp:
        if (strpos($to, '+') !== 0) {
            // Assume +216 if no country code provided and it's 8 digits
            if (strlen($to) === 8) {
                $to = '+216' . $to;
            } else {
                $to = '+' . $to;
            }
        }
        $to = 'whatsapp:' . $to;
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_SID . "/Messages.json";
    
    $data = [
        'From' => TWILIO_WHATSAPP_FROM,
        'To' => $to,
        'Body' => $message
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    } else {
        error_log("Twilio API Error: " . $response);
        return false;
    }
}
