<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("/home/adminsys/conexion.php");

try {
  $conexion = obtenerConexion();
  if (!$conexion) throw new Exception("Sin conexión");

  $input = json_decode(file_get_contents("php://input"), true);
  $descripcion = trim($input["Descripcion"] ?? '');
  $items = $input["Items"] ?? [];
  $idUsuario = intval($input["IdUsuarioGs"] ?? 0);

  if ($descripcion === '' || !is_array($items) || count($items) === 0) {
    throw new Exception("Nombre de grupo e ítems requeridos");
  }
  if ($idUsuario <= 0) {
    throw new Exception("Usuario no válido");
  }

  // Verificar existencia para este usuario
  $check = $conexion->prepare("SELECT Id FROM Frm_Ct_Caract WHERE Descripcion = ? AND IdUsuarioGs = ?");
  $check->bind_param("si", $descripcion, $idUsuario);
  $check->execute();
  $check->store_result();
  if ($check->num_rows > 0) throw new Exception("Este grupo ya existe");
  $check->close();

  // Insertar grupo con IdUsuarioGs
  $stmt = $conexion->prepare("INSERT INTO Frm_Ct_Caract (Descripcion, IdUsuarioGs) VALUES (?, ?)");
  $stmt->bind_param("si", $descripcion, $idUsuario);
  $stmt->execute();
  $idGrupo = $stmt->insert_id;
  $stmt->close();

  // Insertar ítems
  $stmt = $conexion->prepare("INSERT INTO Frm_Ct_Caract_Items (IdCaract, Nombre) VALUES (?, ?)");
  foreach ($items as $nombre) {
    $nombreLimpio = trim($nombre);
    if ($nombreLimpio !== "") {
      $stmt->bind_param("is", $idGrupo, $nombreLimpio);
      $stmt->execute();
    }
  }
  $stmt->close();

  echo json_encode(["success" => true, "IdGrupo" => $idGrupo]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
