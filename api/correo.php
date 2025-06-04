<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

function enviarCorreo($para, $asunto, $cuerpo) {
  $mail = new PHPMailer(true);

  try {
    // 🔒 Configuración SMTP segura
    $mail->isSMTP();
    $mail->Host = 'mail.geneosyst.com';  // ⚠️ Cambiar por tu servidor SMTP
    $mail->SMTPAuth = true;
    $mail->Username = 'cuentafw@geneosyst.com'; // ⚠️ Cambiar
    $mail->Password = 'Axt_1175338811';         // ⚠️ Cambiar
    $mail->SMTPSecure = 'ssl';                 // o 'tls' según tu servidor
    $mail->Port = 465;                         // 587 para TLS, 465 para SSL

    $mail->setFrom('cuentafw@geneosyst.com', 'Geneosyst Factura Web');
    $mail->addAddress($para);

    $mail->isHTML(false);
    $mail->Subject = $asunto;
    $mail->Body    = $cuerpo;

    $mail->send();
    return true;
  } catch (Exception $e) {
    return false;
  }
}
