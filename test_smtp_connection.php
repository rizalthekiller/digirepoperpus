<?php
require_once 'includes/config.php';

echo "<h2>Testing SMTP Connection...</h2>";
echo "Server: " . SMTP_HOST . ":" . SMTP_PORT . "<br>";
echo "User: " . SMTP_USER . "<br><br>";

$error_msg = "";
$errno = 0;
$errstr = "";

// 1. Test Socket Connection
$socket = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);

if (!$socket) {
    echo "<p style='color: red;'>[FAILED] Could not connect to host: $errstr ($errno)</p>";
} else {
    echo "<p style='color: green;'>[SUCCESS] Connected to host.</p>";
    
    // Read greeting
    $response = fgets($socket, 512);
    echo "Server Response: $response<br>";

    // 2. Start Conversation (HELO)
    fwrite($socket, "HELO " . $_SERVER['HTTP_HOST'] . "\r\n");
    $response = fgets($socket, 512);
    echo "HELO Response: $response<br>";

    // 3. Request TLS
    fwrite($socket, "STARTTLS\r\n");
    $response = fgets($socket, 512);
    echo "STARTTLS Response: $response<br>";

    if (strpos($response, '220') !== false) {
        // Switch to encrypted mode
        if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            echo "<p style='color: green;'>[SUCCESS] TLS Encryption Enabled.</p>";
            
            // Send HELO again over TLS
            fwrite($socket, "HELO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $response = fgets($socket, 512);
            
            // 4. Test Authentication
            fwrite($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 512);
            
            if (strpos($response, '334') !== false) {
                // Send Base64 Username
                fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
                $response = fgets($socket, 512);
                
                // Send Base64 Password
                fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
                $response = fgets($socket, 512);
                
                if (strpos($response, '235') !== false) {
                    echo "<p style='color: green; font-weight: bold;'>[SUCCESS] SMTP Authentication Successful!</p>";
                    echo "Akun Anda sudah siap digunakan untuk mengirim email.";
                } else {
                    echo "<p style='color: red;'>[FAILED] Authentication Failed: $response</p>";
                    echo "Pastikan App Password Gmail sudah benar.";
                }
            } else {
                echo "<p style='color: red;'>[FAILED] Server rejected AUTH LOGIN: $response</p>";
            }
        } else {
            echo "<p style='color: red;'>[FAILED] Could not enable TLS encryption.</p>";
        }
    } else {
        echo "<p style='color: orange;'>[WARNING] STARTTLS not supported or failed: $response</p>";
    }

    // Close
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
}

echo "<br><hr><a href='index.php'>Kembali ke Beranda</a>";
?>
