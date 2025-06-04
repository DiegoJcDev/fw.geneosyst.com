<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("/home/adminsys/conexion.php");

try {
  $conexion = obtenerConexion();
  $input = json_decode(file_get_contents("php://input"), true);

  if (!isset($input["items"], $input["IdUsuarioGs"])) {
    throw new Exception("Parámetros incompletos.");
  }

  $IdUsuarioGs = intval($input["IdUsuarioGs"]);
  $items = $input["items"];

  // Mapear clasificaciones actuales del usuario (sin get_result)
  $clasifActuales = [];
  $stmt = $conexion->prepare("SELECT Id, Clasificacion FROM Frm_Ct_Clasificaciones WHERE IdUsuarioGs = ?");
  $stmt->bind_param("i", $IdUsuarioGs);
  $stmt->execute();
  $stmt->bind_result($idClasif, $nombreClasif);
  while ($stmt->fetch()) {
    $clasifActuales[$idClasif] = $nombreClasif;
  }
  $stmt->close();

  $idsRecibidos = [];

  foreach ($items as $item) {
    $id = intval($item["Id"] ?? 0);
    $nombre = trim($item["Nombre"] ?? "");

    if ($id === 0 && $nombre !== "") {
      // Insertar nueva clasificación
      $stmt = $conexion->prepare("INSERT INTO Frm_Ct_Clasificaciones (Clasificacion, IdUsuarioGs) VALUES (?, ?)");
      $stmt->bind_param("si", $nombre, $IdUsuarioGs);
      if (!$stmt->execute()) throw new Exception("Error al insertar: " . $stmt->error);
      $stmt->close();
    } elseif ($id > 0 && isset($clasifActuales[$id])) {
      $idsRecibidos[] = $id;

      $nombreActual = $clasifActuales[$id];
      if ($nombre !== "" && $nombre !== $nombreActual) {
        // Verificar si está en uso
        $check = $conexion->prepare("SELECT COUNT(*) FROM Frm_Ct_Productos WHERE IdClasificacion = ? AND IdUsuarioGs = ?");
        $check->bind_param("ii", $id, $IdUsuarioGs);
        $check->execute();
        $check->bind_result($enUso);
        $check->fetch();
        $check->close();

        if ($enUso > 0 && empty($item["forzarRenombre"])) {
          throw new Exception("No se puede renombrar el ítem '{$nombreActual}' porque está vinculado a productos.");
        }

        // Proceder a actualizar
        $update = $conexion->prepare("UPDATE Frm_Ct_Clasificaciones SET Clasificacion = ? WHERE Id = ? AND IdUsuarioGs = ?");
        $update->bind_param("sii", $nombre, $id, $IdUsuarioGs);
        if (!$update->execute()) throw new Exception("Error al actualizar: " . $update->error);
        $update->close();
      }
    }
  }

  // Determinar clasificaciones eliminadas
  foreach ($clasifActuales as $id => $nombre) {
    if (!in_array($id, $idsRecibidos)) {
      // Validar que no esté en uso
      $check = $conexion->prepare("SELECT COUNT(*) FROM Frm_Ct_Productos WHERE IdClasificacion = ? AND IdUsuarioGs = ?");
      $check->bind_param("ii", $id, $IdUsuarioGs);
      $check->execute();
      $check->bind_result($enUso);
      $check->fetch();
      $check->close();

      if ($enUso == 0) {
        $del = $conexion->prepare("DELETE FROM Frm_Ct_Clasificaciones WHERE Id = ? AND IdUsuarioGs = ?");
        $del->bind_param("ii", $id, $IdUsuarioGs);
        if (!$del->execute()) throw new Exception("Error al eliminar: " . $del->error);
        $del->close();
      }
    }
  }

  echo json_encode(["success" => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
