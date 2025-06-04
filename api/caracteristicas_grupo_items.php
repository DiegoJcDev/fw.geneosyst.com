<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("/home/adminsys/conexion.php");

try {
  $input = json_decode(file_get_contents("php://input"), true);
  $IdUsuarioGs = intval($input["IdUsuarioGs"] ?? 0);
  if ($IdUsuarioGs <= 0) throw new Exception("IdUsuarioGs inválido");

  $conexion = obtenerConexion();
  if (!$conexion) throw new Exception("No se pudo conectar a la base de datos.");

  $sql = "
    SELECT 
      M.Id AS IdGrupo,
      M.Descripcion,
      MI.Id AS IdItem,
      MI.Nombre AS Elemento
    FROM Frm_Ct_Caract M
    INNER JOIN Frm_Ct_Caract_Items MI ON MI.IdCaract = M.Id
    WHERE M.IdUsuarioGs = ?
    ORDER BY M.Id, MI.Nombre
  ";

  $stmt = $conexion->prepare($sql);
  if (!$stmt) throw new Exception("Error en prepare(): " . $conexion->error);
  $stmt->bind_param("i", $IdUsuarioGs);
  $stmt->execute();
  $stmt->bind_result($idGrupo, $descripcion, $idItem, $elemento);

  $grupos = [];
  while ($stmt->fetch()) {
    if (!isset($grupos[$idGrupo])) {
      $grupos[$idGrupo] = [
        "IdGrupo" => $idGrupo,
        "Descripcion" => $descripcion,
        "Items" => []
      ];
    }
    $grupos[$idGrupo]["Items"][] = [
      "Id" => $idItem,
      "Nombre" => $elemento
    ];
  }

  echo json_encode([
    "success" => true,
    "caracteristicas" => array_values($grupos)
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Error interno: " . $e->getMessage()]);
}
