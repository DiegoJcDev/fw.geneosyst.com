<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['email'], $input['password'], $input['rfc'])) {
  echo json_encode(["success" => false, "message" => "Faltan datos: correo, contraseña o RFC."]);
  exit;
}

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error al conectar a la base de datos"]);
  exit;
}

$email = $conexion->real_escape_string($input['email']);
$rfc = strtoupper(trim($conexion->real_escape_string($input['rfc'])));
$passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);

// Validar duplicidad de correo
$verificaCorreo = $conexion->query("SELECT id FROM Sys_Ct_Usuarios WHERE email = '$email'");
if ($verificaCorreo->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "El correo ya está registrado"]);
  exit;
}

// Validar duplicidad de RFC
$verificaRFC = $conexion->query("SELECT id FROM Sys_Ct_Usuarios WHERE rfc = '$rfc'");
if ($verificaRFC->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "Este RFC ya fue registrado anteriormente"]);
  exit;
}

// Generar token de verificación
$token = bin2hex(random_bytes(32));

// Establecer periodo de prueba
$fechaInicio = date("Y-m-d");
$fechaFin = date("Y-m-d", strtotime("+10 days"));
$estaEnPrueba = 1;

// Registrar usuario
$insert = $conexion->query("
  INSERT INTO Sys_Ct_Usuarios (
    Email, Password, RFC, verificado, token_verificacion,
    FechaInicioPrueba, FechaFinPrueba, EstaEnPrueba
  ) VALUES (
    '$email', '$passwordHash', '$rfc', 0, '$token',
    '$fechaInicio', '$fechaFin', $estaEnPrueba
  )
");

if (!$insert) {
  echo json_encode(["success" => false, "message" => "Error al registrar usuario"]);
  exit;
}

// Enviar correo de verificación
require_once 'correo.php';

$enlace = "https://fw.geneosyst.com/api/verificar.php?token=$token";
$mensaje = "Gracias por registrarte. Para activar tu cuenta, haz clic en el siguiente enlace:\n\n$enlace";

if (enviarCorreo($email, "Verifica tu cuenta", $mensaje)) {
  echo json_encode(["success" => true, "message" => "Registro exitoso. Revisa tu correo para activar la cuenta."]);
} else {
  echo json_encode(["success" => false, "message" => "No se pudo enviar el correo de verificación"]);
}
