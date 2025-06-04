<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// ⚠️ Responder al preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['email'])) {
  echo json_encode(["success" => false, "message" => "Falta el correo."]);
  exit;
}

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();
if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error al conectar."]);
  exit;
}

$email = $conexion->real_escape_string($input['email']);

// Verificar si existe
$result = $conexion->query("SELECT id FROM Sys_Ct_Usuarios WHERE email = '$email'");
if ($result->num_rows === 0) {
  echo json_encode(["success" => true, "message" => "Si el correo está registrado, se ha enviado un enlace de recuperación."]);
  exit; // Mensaje genérico para evitar revelar si existe
}

// Generar token
$token = bin2hex(random_bytes(32));
$expira = date("Y-m-d H:i:s", strtotime("+30 minutes"));

$conexion->query("UPDATE Sys_Ct_Usuarios SET token_recuperacion = '$token', token_expiracion = '$expira' WHERE email = '$email'");

// Enviar correo
require_once 'correo.php';
$link = "https://fw.geneosyst.com/restablecer?token=$token";
$mensaje = "Recibimos una solicitud para restablecer tu contraseña. Si fuiste vos, hacé clic aquí:\n\n$link\n\nEste enlace caduca en 30 minutos.";

if (enviarCorreo($email, "Restablecer contraseña", $mensaje)) {
  echo json_encode(["success" => true, "message" => "Correo enviado. Revisa tu bandeja de entrada."]);
} else {
  echo json_encode(["success" => false, "message" => "No se pudo enviar el correo."]);
}
