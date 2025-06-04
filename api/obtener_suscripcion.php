<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? null;

if (!$email) {
  echo json_encode(["success" => false, "message" => "No autenticado"]);
  exit;
}

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();
if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error de conexión"]);
  exit;
}

$sql = "SELECT FechaInicioPrueba, EstaEnPrueba, FechaInicio, FechaTermino, TipoSuscripcion FROM Sys_Ct_Usuarios WHERE Email = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($fip, $prueba, $fechaInicio, $fechaTermino, $plan);

if ($stmt->fetch()) {
    $inicio = new DateTime($fip);
    $hoy = new DateTime();
    $diff = $inicio->diff($hoy);
    $dias = 10 - $diff->days;
  echo json_encode([
    "success" => true,
    "dias_prueba" => $dias,
    "prueba" => $prueba,
    "fecha_inicio" => $fechaInicio,
    "fecha_termino" => $fechaTermino,
    "plan" => $plan
  ]);
} else {
  echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
}

$stmt->close();
$conexion->close();
