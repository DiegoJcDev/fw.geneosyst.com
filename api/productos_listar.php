<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function responder($success, $otros = []) {
  echo json_encode(array_merge(["success" => $success], $otros));
  exit;
}

try {
  $conexion = obtenerConexion();
  if ($conexion->connect_error) {
    responder(false, ["message" => "Error de conexión"]);
  }

  $input = json_decode(file_get_contents("php://input"), true);
  $IdUsuarioGs = intval($input["IdUsuarioGs"] ?? 0);
  $pagina = intval($input["pagina"] ?? 1);
  $busqueda = trim($input["busqueda"] ?? "");

  if ($IdUsuarioGs <= 0) {
    responder(false, ["message" => "Usuario no válido"]);
  }

  $limite = 20;
  $offset = ($pagina - 1) * $limite;

  // Filtro WHERE
  $where = "WHERE p.Estatus='ACTIVO' AND p.IdUsuarioGs = ?";
  $params = [$IdUsuarioGs];
  $tipos = "i";

  if ($busqueda !== "") {
    $where .= " AND (p.Codigo LIKE ? OR p.Descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $tipos .= "ss";
  }

  // Conteo total
  $sqlCount = "SELECT COUNT(*) FROM Frm_Ct_Productos p $where";
  $stmtCount = $conexion->prepare($sqlCount);
  if (!$stmtCount) responder(false, ["message" => "Error preparando COUNT: " . $conexion->error]);
  $stmtCount->bind_param($tipos, ...$params);
  $stmtCount->execute();
  $stmtCount->bind_result($totalRegistros);
  $stmtCount->fetch();
  $stmtCount->close();

  $totalPaginas = max(1, ceil($totalRegistros / $limite));

  // Consulta con JOINs
  $sql = "
    SELECT 
      p.Id, p.Codigo, p.Descripcion, p.PrecioVentaBase,
      t.Tipo, c.Clasificacion, u.Unidad
    FROM Frm_Ct_Productos p
    LEFT JOIN SysU_Ct_TiposProducto t ON t.Id = p.IdTipo
    LEFT JOIN Frm_Ct_Clasificaciones c ON c.Id = p.IdClasificacion
    LEFT JOIN Frm_Ct_Unidades u ON u.Id = p.IdUnidadBase
    $where
    ORDER BY p.Id DESC
    LIMIT $limite OFFSET $offset";

  $stmt = $conexion->prepare($sql);
  if (!$stmt) responder(false, ["message" => "Error preparando consulta: " . $conexion->error]);
  $stmt->bind_param($tipos, ...$params);
  $stmt->execute();
  $stmt->store_result();

  $stmt->bind_result($Id, $Codigo, $Descripcion, $PrecioVentaBase, $Tipo, $Clasificacion, $Unidad);
  $productos = [];

  while ($stmt->fetch()) {
    $productos[] = [
      "Id" => $Id,
      "Codigo" => $Codigo,
      "Descripcion" => $Descripcion,
      "PrecioVentaBase" => $PrecioVentaBase,
      "Tipo" => $Tipo,
      "Clasificacion" => $Clasificacion,
      "Unidad" => $Unidad
    ];
  }

  responder(true, [
    "productos" => $productos,
    "totalPaginas" => $totalPaginas
  ]);
} catch (Exception $e) {
  responder(false, ["message" => "Error: " . $e->getMessage()]);
}
