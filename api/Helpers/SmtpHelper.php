<?php
namespace Helpers;

class SmtpHelper {
    public static function sendEmail($to, $subject, $message) {
        $smtpServer = getenv('SMTP_SERVER') ?: "smtp.gmail.com";
        $smtpPort = getenv('SMTP_PORT') ?: 587;
        $username = getenv('SENDER_EMAIL');
        $password = getenv('SENDER_PASSWORD');
        if (!$username || !$password) {
            error_log('SMTP sin configurar.');
            return false;
        }
        $fromName = "Seguridad App";

        $newLine = "\r\n";

        // Conectar al servidor SMTP (usamos tcp para port 587, luego STARTTLS)
        $socket = fsockopen($smtpServer, $smtpPort, $errno, $errstr, 15);
        if (!$socket) {
            error_log("Error SMTP: $errstr ($errno)");
            return false;
        }

        $res = fgets($socket, 256);

        fwrite($socket, "EHLO " . $smtpServer . $newLine);
        $res = self::getServerResponse($socket);

        // Iniciar STARTTLS
        fwrite($socket, "STARTTLS" . $newLine);
        $res = self::getServerResponse($socket);

        // Habilitar criptografía en el socket
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        // Volver a saludar por seguridad
        fwrite($socket, "EHLO " . $smtpServer . $newLine);
        $res = self::getServerResponse($socket);

        // Autenticación
        fwrite($socket, "AUTH LOGIN" . $newLine);
        $res = self::getServerResponse($socket);

        fwrite($socket, base64_encode($username) . $newLine);
        $res = self::getServerResponse($socket);

        fwrite($socket, base64_encode($password) . $newLine);
        $res = self::getServerResponse($socket);

        // Configurar remitente y destinatario
        fwrite($socket, "MAIL FROM: <$username>" . $newLine);
        $res = self::getServerResponse($socket);

        fwrite($socket, "RCPT TO: <$to>" . $newLine);
        $res = self::getServerResponse($socket);

        // Datos del mensaje
        fwrite($socket, "DATA" . $newLine);
        $res = self::getServerResponse($socket);

        $headers = "From: $fromName <$username>" . $newLine;
        $headers .= "To: $to" . $newLine;
        $headers .= "Subject: $subject" . $newLine;
        $headers .= "MIME-Version: 1.0" . $newLine;
        $headers .= "Content-Type: text/html; charset=UTF-8" . $newLine;

        $emailContent = $headers . $newLine . $message . $newLine . "." . $newLine;
        fwrite($socket, $emailContent);
        $res = self::getServerResponse($socket);

        // Cerrar conexión
        fwrite($socket, "QUIT" . $newLine);
        fclose($socket);

        return true;
    }

    private static function getServerResponse($socket) {
        $data = "";
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        return $data;
    }
}
