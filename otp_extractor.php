<?php
// Configuration
$mailServer = '{imap.hostinger.com:993/imap/ssl}INBOX'; // Mail Server
$username   = ''; // Email @
$password   = ''; // Email's Password
$domain     = ''; // Domain Name @xxxx.xxx

// Initialize
$code = 0;

try {
    // Connect with timeout
    $inbox = @imap_open($mailServer, $username, $password, OP_READONLY, 1);
   
    if (!$inbox) {
        throw new Exception('Connection failed: ' . imap_last_error());
    }
   
    // Search for emails from last 4 minutes
    $searchDate = date("d-M-Y", strtotime("-4 minutes"));
    $emails = imap_search($inbox, 'SINCE "' . $searchDate . '" FROM "@' . $domain . '"');
   
    if ($emails) {
        // Process emails (newest first)
        rsort($emails);
       
        foreach ($emails as $email_number) {
            // Get email body
            $body = imap_body($inbox, $email_number);
           
            // Auto-decode if encoded
            if (strpos($body, '=?UTF-8?B?') !== false ||
                strpos($body, '=?ISO-8859-1?Q?') !== false) {
                $body = imap_utf8($body);
            }
           
            // Try multiple patterns to find the code
            $patterns = [
                '/password:\s*(\d{6})/i',      // "password: 123456"
                '/password[:\s]+is\s*(\d{6})/i', // "password is: 123456"
                '/code[:\s]*(\d{6})/i',         // "code: 123456"
                '/OTP[:\s]*(\d{6})/i',          // "OTP: 123456"
                '/one.time.password[:\s]*(\d{6})/i', // "one time password: 123456"
                '/\b(\d{6})\b/',                // Any 6-digit standalone number
                '/\b\d{4,}\b/'                  // Any 4+ digit number (fallback)
            ];
           
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $body, $match)) {
                    $code = isset($match[1]) ? $match[1] : $match[0];
                    break 2; // Exit both loops when found
                }
            }
        }
    }
   
    // Clean up
    imap_close($inbox);
   
} catch (Exception $e) {
    // Log error if needed
    error_log("Email code extractor error: " . $e->getMessage());
    $code = 0;
}

// Output result
echo $code;
?>
