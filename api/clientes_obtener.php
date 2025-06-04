<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['Id']) || !isset($input['IdUsuarioGs'])) {
    echo json_encode(["success" => false, "message" => "Faltan parámetros obligatorios"]);
    exit;
}

$Id = intval($input['Id']);
$IdUsuarioGs = intval($input['IdUsuarioGs']);
$IdTipoEmpresa = 4;

try {
    $stmt = $conexion->prepare("
        SELECT 
            Id, NombreComercial, NombreOficial, RfcNoIdFiscal,
            IdPersonaJuridicaSAT, IdRegimenSAT,
            Telefono1, Telefono2, Movil, Email, EmailCc,
            NombreVialidad, NumExt, NumInt, ColoniaLocalidad, CodigoPostal,
            CiudadEntidad, EstadoProvincia, Pais
        FROM Frm_Ct_Empresas
        WHERE Id = ? AND IdUsuarioGs = ? AND IdTipoEmpresa = ? AND (Estatus IS NULL OR Estatus != 'BAJA')
        LIMIT 1
    ");
    $stmt->bind_param("iii", $Id, $IdUsuarioGs, $IdTipoEmpresa);
    $stmt->execute();
    $stmt->bind_result(
        $Id, $NombreComercial, $NombreOficial, $RfcNoIdFiscal,
        $IdPersonaJuridicaSAT, $IdRegimenSAT,
        $Telefono1, $Telefono2, $Movil, $Email, $EmailCc,
        $NombreVialidad, $NumExt, $NumInt, $ColoniaLocalidad, $CodigoPostal,
        $CiudadEntidad, $EstadoProvincia, $Pais
    );

    if ($stmt->fetch()) {
        $cliente = [
            "Id" => $Id,
            "NombreComercial" => $NombreComercial,
            "NombreOficial" => $NombreOficial,
            "RfcNoIdFiscal" => $RfcNoIdFiscal,
            "IdPersonaJuridicaSAT" => $IdPersonaJuridicaSAT,
            "IdRegimenSAT" => $IdRegimenSAT,
            "Telefono1" => $Telefono1,
            "Telefono2" => $Telefono2,
            "Movil" => $Movil,
            "Email" => $Email,
            "EmailCc" => $EmailCc,
            "NombreVialidad" => $NombreVialidad,
            "NumExt" => $NumExt,
            "NumInt" => $NumInt,
            "ColoniaLocalidad" => $ColoniaLocalidad,
            "CodigoPostal" => $CodigoPostal,
            "CiudadEntidad" => $CiudadEntidad,
            "EstadoProvincia" => $EstadoProvincia,
            "Pais" => $Pais
        ];
        echo json_encode(["success" => true, "cliente" => $cliente]);
    } else {
        echo json_encode(["success" => false, "message" => "Cliente no encontrado"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error al obtener cliente", "error" => $e->getMessage()]);
}
?>
