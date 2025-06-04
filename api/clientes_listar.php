<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['IdUsuarioGs'])) {
    echo json_encode(["success" => false, "message" => "Falta IdUsuarioGs"]);
    exit;
}

$IdUsuarioGs = intval($input['IdUsuarioGs']);
$IdTipoEmpresa = 4;
$buscar = trim($input['buscar'] ?? '');
$pagina = intval($input['pagina'] ?? 1);
$limite = intval($input['limite'] ?? 10);
$offset = ($pagina - 1) * $limite;

// Armar condiciones
$condiciones = "IdUsuarioGs = ? AND IdTipoEmpresa = ? AND (Estatus IS NULL OR Estatus = 'ACTIVO')";
$parametros = [$IdUsuarioGs, $IdTipoEmpresa];
$tipos = "ii";

if ($buscar !== '') {
    $condiciones .= " AND (
        NombreComercial LIKE ? OR 
        NombreOficial LIKE ? OR 
        RfcNoIdFiscal LIKE ? OR 
        Telefono1 LIKE ?
    )";
    $buscar_param = '%' . $buscar . '%';
    $parametros = array_merge($parametros, [$buscar_param, $buscar_param, $buscar_param, $buscar_param]);
    $tipos .= "ssss";
}

// Consulta total
$sql_total = "SELECT COUNT(*) FROM Frm_Ct_Empresas WHERE $condiciones";
$stmt_total = $conexion->prepare($sql_total);
$stmt_total->bind_param($tipos, ...$parametros);
$stmt_total->execute();
$stmt_total->bind_result($total_registros);
$stmt_total->fetch();
$stmt_total->close();

$total_paginas = ceil($total_registros / $limite);

// Consulta paginada
$sql = "
    SELECT Id, NombreComercial, NombreOficial, RfcNoIdFiscal, Telefono1
    FROM Frm_Ct_Empresas
    WHERE $condiciones
    ORDER BY NombreOficial ASC
    LIMIT ? OFFSET ?
";
$tipos_paginado = $tipos . "ii";
$parametros_paginado = array_merge($parametros, [$limite, $offset]);

$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos_paginado, ...$parametros_paginado);
$stmt->execute();
$stmt->bind_result($Id, $NombreComercial, $NombreOficial, $RfcNoIdFiscal, $Telefono1);

$clientes = [];
while ($stmt->fetch()) {
    $clientes[] = [
        "Id" => $Id,
        "NombreComercial" => $NombreComercial,
        "NombreOficial" => $NombreOficial,
        "RfcNoIdFiscal" => $RfcNoIdFiscal,
        "Telefono1" => $Telefono1
    ];
}

echo json_encode([
    "success" => true,
    "clientes" => $clientes,
    "pagina" => $pagina,
    "total_paginas" => $total_paginas,
    "total" => $total_registros
]);
