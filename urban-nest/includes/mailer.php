<?php
require_once __DIR__ . '/mail_config.php';

function smtp_read($socket): string {
    $data = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $data .= $line;
        if (preg_match('/^\d{3} /', $line)) {
            break;
        }
    }
    return $data;
}

function smtp_expect($socket, array $codes): string {
    $response = smtp_read($socket);
    $code = (int) substr(trim($response), 0, 3);
    if (!in_array($code, $codes, true)) {
        throw new RuntimeException(smtp_friendly_error($code, $response));
    }
    return $response;
}


function smtp_friendly_error(int $code, string $response): string {
    $clean = trim($response);
    if ($code === 535) {
        return 'Gmail rejected the SMTP username/password (535). Use the full Gmail address as SMTP_USERNAME and a Google App Password (not your normal Gmail password). Make sure 2-Step Verification is enabled on that Google account.';
    }
    if ($code === 534) {
        return 'Gmail requires an App Password for this SMTP connection (534). Create an App Password after enabling 2-Step Verification.';
    }
    return 'SMTP server rejected the request (' . $code . '): ' . $clean;
}

function smtp_command($socket, string $command, array $codes): string {
    if (fwrite($socket, $command . "\r\n") === false) {
        throw new RuntimeException('Failed to write to the SMTP server.');
    }
    return smtp_expect($socket, $codes);
}

function smtp_encode_header(string $value): string {
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function send_smtp_email(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody = '',
    ?string &$errorMessage = null
): bool {
    $errorMessage = null;

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'The destination email address is invalid.';
        return false;
    }

    $username = trim(SMTP_USERNAME);
    $password = preg_replace('/\s+/', '', SMTP_PASSWORD);

    if (
        $username === '' ||
        $password === '' ||
        $username === 'YOUR_GMAIL_ADDRESS@gmail.com' ||
        $password === 'YOUR_16_DIGIT_APP_PASSWORD'
    ) {
        $errorMessage = 'SMTP is not configured. Open includes/mail_config.php and enter your Gmail address and App Password.';
        error_log('Apartment Rental SMTP: ' . $errorMessage);
        return false;
    }

    $socket = null;

    try {
        $ssl = [
            'verify_peer' => SMTP_VERIFY_CERT,
            'verify_peer_name' => SMTP_VERIFY_CERT,
            'allow_self_signed' => !SMTP_VERIFY_CERT,
        ];
        if (defined('openssl.cafile') && openssl_get_cert_locations()['default_cert_file']) {
            $ssl['cafile'] = openssl_get_cert_locations()['default_cert_file'];
        }

        $context = stream_context_create(['ssl' => $ssl]);
        $transport = strtolower(SMTP_ENCRYPTION) === 'ssl' ? 'ssl://' : 'tcp://';

        $socket = stream_socket_client(
            $transport . SMTP_HOST . ':' . SMTP_PORT,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            throw new RuntimeException('Could not connect to ' . SMTP_HOST . ':' . SMTP_PORT . ' - ' . ($errstr ?: 'unknown connection error'));
        }

        stream_set_timeout($socket, 30);
        smtp_expect($socket, [220]);

        smtp_command($socket, 'EHLO localhost', [250]);

        if (strtolower(SMTP_ENCRYPTION) === 'tls') {
            smtp_command($socket, 'STARTTLS', [220]);
            $cryptoMethod = defined('STREAM_CRYPTO_METHOD_TLS_CLIENT') ? STREAM_CRYPTO_METHOD_TLS_CLIENT : STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                throw new RuntimeException('Unable to start TLS encryption.');
            }
            smtp_command($socket, 'EHLO localhost', [250]);
        }

        smtp_command($socket, 'AUTH LOGIN', [334]);
        smtp_command($socket, base64_encode($username), [334]);
        smtp_command($socket, base64_encode($password), [235]);

        smtp_command($socket, 'MAIL FROM:<' . SMTP_FROM_EMAIL . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);

        $safeName = trim((string)preg_replace('/[\r\n]+/', ' ', $toName));
        $boundary = '=_ApartmentRental_' . bin2hex(random_bytes(12));

        $headers = [];
        $headers[] = 'From: ' . smtp_encode_header(SMTP_FROM_NAME) . ' <' . SMTP_FROM_EMAIL . '>';
        $headers[] = 'To: ' . ($safeName !== '' ? smtp_encode_header($safeName) . ' ' : '') . '<' . $toEmail . '>';
        $headers[] = 'Subject: ' . smtp_encode_header($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Date: ' . date(DATE_RFC2822);
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $plain = $textBody !== '' ? $textBody : strip_tags($htmlBody);
        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($plain)) . "\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $message .= '--' . $boundary . "--\r\n";

        $message = preg_replace('/^\./m', '..', $message);
        if (fwrite($socket, $message . "\r\n.\r\n") === false) {
            throw new RuntimeException('Failed to send the email contents.');
        }
        smtp_expect($socket, [250]);

        @fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
        error_log('Apartment Rental SMTP error: ' . $errorMessage);
        if (is_resource($socket)) {
            fclose($socket);
        }
        return false;
    }
}
