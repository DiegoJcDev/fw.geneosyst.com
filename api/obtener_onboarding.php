<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['userId'])) {
  echo json_encode(['success' => false, 'message' => 'Falta parámetro userId']);
  exit;
}

$userId = intval($input['userId']);
$stmt = $conexion->prepare("SELECT OnboardingStep FROM Sys_Ct_Usuarios WHERE Id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($step);
$stmt->fetch();
$stmt->close();

echo json_encode(['success' => true, 'step' => intval($step)]);
