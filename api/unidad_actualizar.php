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

  // Obtener todos los IDs de unidad existentes del usuario
  $result = $conexion->query("SELECT Id FROM Frm_Ct_Unidades WHERE IdUsuarioGs = $IdUsuarioGs");
  $idsExistentes = [];
  while ($fila = $result->fetch_assoc()) {
    $idsExistentes[] = intval($fila["Id"]);
  }

  // IDs que el usuario quiere conservar o actualizar
  $idsRecibidos = array_map(fn($i) => intval($i["Id"] ?? 0), $items);
  $idsEliminar = array_diff($idsExistentes, $idsRecibidos);

  // Procesar eliminaciones
  foreach ($idsEliminar as $id) {
    // Verificar si la unidad está en uso
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM Frm_Ct_Productos WHERE IdUnidadBase = ? AND IdUsuarioGs = ?");
    $stmt->bind_param("ii", $id, $IdUsuarioGs);
    $stmt->execute();
    $stmt->bind_result($enUso);
    $stmt->fetch();
    $stmt->close();

    if ($enUso == 0) {
      $stmt = $conexion->prepare("DELETE FROM Frm_Ct_Unidades WHERE Id = ? AND IdUsuarioGs = ?");
      $stmt->bind_param("ii", $id, $IdUsuarioGs);
      $stmt->execute();
      $stmt->close();
    }
    // Si está en uso, no se elimina (se omite)
  }

  // Procesar inserciones o actualizaciones
  foreach ($items as $item) {
    $id = intval($item["Id"] ?? 0);
    $nombre = trim($item["Nombre"] ?? "");

    if ($id === 0 && $nombre !== "") {
      // Insertar nueva unidad
      $stmt = $conexion->prepare("INSERT INTO Frm_Ct_Unidades (Unidad, IdUsuarioGs) VALUES (?, ?)");
      $stmt->bind_param("si", $nombre, $IdUsuarioGs);
      if (!$stmt->execute()) throw new Exception("Error al insertar: " . $stmt->error);
      $stmt->close();
    } elseif ($id > 0) {
      // Buscar unidad actual
      $stmt = $conexion->prepare("SELECT Unidad FROM Frm_Ct_Unidades WHERE Id = ? AND IdUsuarioGs = ?");
      $stmt->bind_param("ii", $id, $IdUsuarioGs);
      $stmt->execute();
      $stmt->bind_result($unidadActual);
      if ($stmt->fetch()) {
        $stmt->close();

        if ($nombre !== "" && $nombre !== $unidadActual) {
          // Verificar si está en uso
          $check = $conexion->prepare("SELECT COUNT(*) FROM Frm_Ct_Productos WHERE IdUnidadBase = ? AND IdUsuarioGs = ?");
          $check->bind_param("ii", $id, $IdUsuarioGs);
          $check->execute();
          $check->bind_result($enUso);
          $check->fetch();
          $check->close();

          if ($enUso > 0 && empty($item["forzarRenombre"])) {
            throw new Exception("No se puede renombrar la unidad '{$unidadActual}' porque está vinculada a productos.");
          }

          // Proceder con el renombramiento
          $stmtUpdate = $conexion->prepare("UPDATE Frm_Ct_Unidades SET Unidad = ? WHERE Id = ? AND IdUsuarioGs = ?");
          $stmtUpdate->bind_param("sii", $nombre, $id, $IdUsuarioGs);
          if (!$stmtUpdate->execute()) throw new Exception("Error al actualizar: " . $stmtUpdate->error);
          $stmtUpdate->close();
        }
      } else {
        throw new Exception("Unidad no encontrada o no pertenece al usuario.");
      }
    }
  }

  echo json_encode(["success" => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
