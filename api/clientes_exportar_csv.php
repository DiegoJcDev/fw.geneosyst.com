<?php
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=clientes_exportacion.csv");

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

// Soportar application/x-www-form-urlencoded y application/json
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
  $input = json_decode(file_get_contents("php://input"), true);
  $IdUsuarioGs = isset($input['IdUsuarioGs']) ? intval($input['IdUsuarioGs']) : 0;
  $buscar = isset($input['buscar']) ? trim($input['buscar']) : '';
} else {
  $IdUsuarioGs = isset($_POST['IdUsuarioGs']) ? intval($_POST['IdUsuarioGs']) : 0;
  $buscar = isset($_POST['buscar']) ? trim($_POST['buscar']) : '';
}

$IdTipoEmpresa = 4;

if ($IdUsuarioGs <= 0) {
  echo "IdUsuarioGs es requerido";
  exit;
}

$sql = "SELECT NombreComercial, NombreOficial, RfcNoIdFiscal, Telefono1, Email
        FROM Frm_Ct_Empresas
        WHERE IdUsuarioGs = ? AND IdTipoEmpresa = ? AND (Estatus IS NULL OR Estatus = 'ACTIVO')";
$paramTypes = "ii";
$params = [$IdUsuarioGs, $IdTipoEmpresa];

if (!empty($buscar)) {
  $sql .= " AND (NombreComercial LIKE ? OR NombreOficial LIKE ? OR RfcNoIdFiscal LIKE ?)";
  $paramTypes .= "sss";
  $likeBuscar = "%$buscar%";
  array_push($params, $likeBuscar, $likeBuscar, $likeBuscar);
}

$sql .= " ORDER BY NombreOficial ASC";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => "Error en prepare()",
    "error" => $conexion->error,
    "sql" => $sql,
    "paramTypes" => $paramTypes,
    "params" => $params
  ]);
  exit;
}

try {
  $stmt->bind_param($paramTypes, ...$params);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => "Error en bind_param()",
    "error" => $e->getMessage(),
    "paramTypes" => $paramTypes,
    "params" => $params
  ]);
  exit;
}

$stmt->execute();
$resultado = $stmt->get_result();

$output = fopen("php://output", "w");
fputcsv($output, ["Nombre Comercial", "Nombre Oficial", "RFC", "Teléfono", "Email"]);

while ($fila = $resultado->fetch_assoc()) {
  fputcsv($output, [
    $fila['NombreComercial'],
    $fila['NombreOficial'],
    $fila['RfcNoIdFiscal'],
    $fila['Telefono1'],
    $fila['Email']
  ]);
}

fclose($output);
?>
