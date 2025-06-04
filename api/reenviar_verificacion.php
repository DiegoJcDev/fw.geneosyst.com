<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

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

$result = $conexion->query("SELECT id, verificado, token_verificacion FROM Sys_Ct_Usuarios WHERE email = '$email'");
if ($result->num_rows === 0) {
  echo json_encode(["success" => true, "message" => "Si el correo está registrado, se ha enviado un nuevo enlace."]);
  exit;
}

$usuario = $result->fetch_assoc();
if ($usuario['verificado']) {
  echo json_encode(["success" => false, "message" => "Este usuario ya está verificado."]);
  exit;
}

// Si no hay token, generar uno
$token = $usuario['token_verificacion'] ?: bin2hex(random_bytes(32));
if (!$usuario['token_verificacion']) {
  $conexion->query("UPDATE Sys_Ct_Usuarios SET token_verificacion = '$token' WHERE email = '$email'");
}

// Enviar correo
require_once 'correo.php';
$enlace = "https://fw.geneosyst.com/verificar?token=$token";
$mensaje = "Hola. Reenviamos tu enlace para verificar tu cuenta:\n\n$enlace";

if (enviarCorreo($email, "Reenvío de verificación", $mensaje)) {
  echo json_encode(["success" => true, "message" => "Enlace de verificación reenviado. Revisa tu correo."]);
} else {
  echo json_encode(["success" => false, "message" => "No se pudo enviar el correo."]);
}
