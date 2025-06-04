<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

if (!$conexion) {
  echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
  exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['userId']) || !isset($input['newStep'])) {
        echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
        exit;
    }

    $userId = intval($input['userId']);
    $newStep = intval($input['newStep']);

    $query = "UPDATE Sys_Ct_Usuarios SET OnboardingStep = ? WHERE Id = ?";
    $stmt = $conexion->prepare($query);

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . $conexion->error]);
        exit;
    }

    $stmt->bind_param("ii", $newStep, $userId);
    $stmt->execute();

    echo json_encode(['success' => $stmt->affected_rows > 0]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
