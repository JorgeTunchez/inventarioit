<?php
require_once("core/core.php");
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
if (isset($_SESSION['user_id'])) {
    $strRolUserSession = getRolUserSession($_SESSION['user_id']);
    $strPuestoUserSession = getPuestoUserSession($_SESSION['user_id']);
    $intIDUserSession = getIDUserSession($_SESSION['user_id']);

    if ($strRolUserSession != '') {
        $arrRolUser["ID"] = $intIDUserSession;
        $arrRolUser["NAME"] = $_SESSION['user_id'];
        $arrRolUser["PUESTO"] = $strPuestoUserSession;

        if ($strRolUserSession == "master") {
            $arrRolUser["MASTER"] = true;
        } elseif ($strRolUserSession == "normal") {
            $arrRolUser["NORMAL"] = true;
        }
    }
} else {
    header("Location: index.php");
}

$objController = new expediente_controller($arrRolUser);
$objController->process();
$objController->runAjax();
$objController->drawContentController();

class expediente_controller
{

    private $objModel;
    private $objView;
    private $arrRolUser;

    public function __construct($arrRolUser)
    {
        $this->objModel = new expediente_model();
        $this->objView = new expediente_view($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContentController()
    {
        $this->objView->drawContent();
    }

    public function runAjax()
    {
        $this->exportPDF();
        $this->ajaxDestroySession();
        $this->ajaxloadColaboradores();
        $this->ajaxLoadExpediente();
    }

    public function ajaxloadColaboradores()
    {
        if (isset($_POST["loadColaboradores"]) && $_POST["loadColaboradores"] == "true") {
            $intArea = isset($_POST["intArea"]) ? intval($_POST["intArea"]) : 0;
            print $this->objView->drawSelectColaborador($intArea);
            exit();
        }
    }

    public function ajaxLoadExpediente()
    {
        if (isset($_POST["loadExpediente"]) && $_POST["loadExpediente"] == "true") {
            $intColaborador = isset($_POST["intColaborador"]) ? intval($_POST["intColaborador"]) : 0;
            print $this->objView->drawContentExpediente($intColaborador);
            exit();
        }
    }

    public function ajaxDestroySession()
    {
        if (isset($_POST["destroSession"])) {
            header("Content-Type: application/json;");
            session_destroy();
            $arrReturn["Correcto"] = "Y";
            print json_encode($arrReturn);
            exit();
        }
    }

    public function process()
    {
        reset($_POST);
        while ($arrTMP = each($_POST)) {
            $arrExplode = explode("_", $arrTMP['key']);
            $intColaborador = isset($_POST["hdnColaborador"]) ? $_POST["hdnColaborador"] : "";
            $intIDExpediente = $this->objModel->getIDExpedienteColaborador($intColaborador);

            //Comentarios
            $strComentarios = isset($_POST["txtComentarios"]) ? trim($_POST["txtComentarios"]) : "";
            $this->objModel->updateComentarioExp($intIDExpediente, $strComentarios);

            //DatosPersonales
            $strNombreEquipo = isset($_POST["txtNombrePC"]) ? trim($_POST["txtNombrePC"]) : "";
            $strDireccionIP = isset($_POST["txtIPPC"]) ? trim($_POST["txtIPPC"]) : "";
            $strDominio = isset($_POST["txtDominicioPC"]) ? trim($_POST["txtDominicioPC"]) : "";
            $strUsuario = isset($_POST["txtUsuarioPC"]) ? trim($_POST["txtUsuarioPC"]) : "";
            $this->objModel->updateDatosEquipo($intIDExpediente, $strNombreEquipo, $strDireccionIP, $strDominio, $strUsuario);

            //Process de Hardware
            if ($arrExplode[0] == "hdnIdHW") {
                $intHardware = $arrExplode[1];
                $strAccionH = isset($_POST["hdnIdHW_{$intHardware}"]) ? trim($_POST["hdnIdHW_{$intHardware}"]) : '';
                $intComponenteH = isset($_POST["selectComponenteHD_{$intHardware}"]) ? intval($_POST["selectComponenteHD_{$intHardware}"]) : 0;
                $strMarcaH = isset($_POST["txtHDMarca_{$intHardware}"]) ? trim($_POST["txtHDMarca_{$intHardware}"]) : '';
                $strValorH = isset($_POST["txtHDValor_{$intHardware}"]) ? trim($_POST["txtHDValor_{$intHardware}"]) : '';
                $strSerieH = isset($_POST["txtHDSerie_{$intHardware}"]) ? trim($_POST["txtHDSerie_{$intHardware}"]) : '';
                $strModeloH = isset($_POST["txtHDModelo_{$intHardware}"]) ? trim($_POST["txtHDModelo_{$intHardware}"]) : '';
                $strLineaH = isset($_POST["txtHDLinea_{$intHardware}"]) ? trim($_POST["txtHDLinea_{$intHardware}"]) : '';
                $intAnioH = isset($_POST["txtHDAnio_{$intHardware}"]) ? intval($_POST["txtHDAnio_{$intHardware}"]) : 0;

                if ($strAccionH == "A") {
                    $this->objModel->insertExpDetail($intIDExpediente, $intComponenteH, $strMarcaH, $strValorH, 0, 0, $strSerieH, $strModeloH, $strLineaH, "", "", $intAnioH, $this->arrRolUser["ID"]);
                } elseif ($strAccionH == "D") {
                    $this->objModel->deleteExpDetail($intIDExpediente, $intHardware);
                } elseif ($strAccionH == "E") {
                    $this->objModel->updateExpDetail($intHardware, $intIDExpediente, $intComponenteH, $strMarcaH, $strValorH, 0, 0, $strSerieH, $strModeloH, $strLineaH, $intAnioH, "", "", $this->arrRolUser["ID"]);
                }
            }

            //Process de Plataforma
            if ($arrExplode[0] == "hdnIdPL") {
                $intPlataforma = $arrExplode[1];
                $strAccionP = isset($_POST["hdnIdPL_{$intPlataforma}"]) ? trim($_POST["hdnIdPL_{$intPlataforma}"]) : '';
                $intComponenteP = isset($_POST["selectComponentePL_{$intPlataforma}"]) ? intval($_POST["selectComponentePL_{$intPlataforma}"]) : 0;
                $strUsuario = isset($_POST["txtPLUsuario_{$intPlataforma}"]) ? trim($_POST["txtPLUsuario_{$intPlataforma}"]) : "";
                $strOperador = isset($_POST["txtPLOperador_{$intPlataforma}"]) ? trim($_POST["txtPLOperador_{$intPlataforma}"]) : "";

                if ($strAccionP == "A") {
                    $this->objModel->insertExpDetail($intIDExpediente, $intComponenteP, "", "", 0, 0, "", "", "", $strUsuario, $strOperador, 0, $this->arrRolUser["ID"]);
                } elseif ($strAccionP == "D") {
                    $this->objModel->deleteExpDetail($intIDExpediente, $intPlataforma);
                } elseif ($strAccionP == "E") {
                    $this->objModel->updateExpDetail($intPlataforma, $intIDExpediente, $intComponenteP, "", "", 0, 0, "", "", "", 0, $strUsuario, $strOperador, $this->arrRolUser["ID"]);
                }
            }

            //Process de Software
            if ($arrExplode[0] == "hdnIdSW") {
                $intSoftware = $arrExplode[1];
                $strAccionS = isset($_POST["hdnIdSW_{$intSoftware}"]) ? trim($_POST["hdnIdSW_{$intSoftware}"]) : '';
                $intComponenteS = isset($_POST["selectComponenteSW_{$intSoftware}"]) ? intval($_POST["selectComponenteSW_{$intSoftware}"]) : 0;
                $intPagoS = isset($_POST["selectPago_{$intSoftware}"]) ? intval($_POST["selectPago_{$intSoftware}"]) : 0;
                $intVersionS = isset($_POST["txtSWVersion_{$intSoftware}"]) ? trim($_POST["txtSWVersion_{$intSoftware}"]) : "";

                if ($strAccionS == "A") {
                    $this->objModel->insertExpDetail($intIDExpediente, $intComponenteS, "", "",  $intPagoS, $intVersionS, "", "", "", "", "", 0, $this->arrRolUser["ID"]);
                } elseif ($strAccionS == "D") {
                    $this->objModel->deleteExpDetail($intIDExpediente, $intSoftware);
                } elseif ($strAccionS == "E") {
                    $this->objModel->updateExpDetail($intSoftware, $intIDExpediente, $intComponenteS, "", "", $intPagoS, $intVersionS, "", "", "", 0, "", "", $this->arrRolUser["ID"]);
                }
            }
        }
    }

    //Funcion que permite exportar a formato PDF o EXCEL el contenido del reporte
    public function exportPDF()
    {
        if (isset($_POST['hdnExportar'])) {
            header('Content-Type: text/html; charset=utf-8');
            require_once("tcpdf/tcpdf.php");
            $intColaborador = isset($_POST["intColaborador"]) ? $_POST["intColaborador"] : '';
            $strNombreColaborador = getNombreColaborador($intColaborador);

            $strTipoExportar = isset($_POST["TipoExport"]) ? $_POST["TipoExport"] : '';
            $arrColaborador = $this->objModel->getInfoColaborador($intColaborador);
            $strNombreArchivo = "ReporteIT_" . $strNombreColaborador;

            if ($strTipoExportar == 'PDF') {
                $strHTML = $this->objView->drawExportReport($arrColaborador);
                //print $strHTML;

                ob_start();
                $pdf = new TCPDF("P", "mm", "LETTER", false, 'UTF-8', false);
                $pdf->AddPage('P');
                $pdf->SetFont('helvetica', '', 7);
                $pdf->writeHTML($strHTML, true, false, true, false, "C");
                ob_end_clean();
                $pdf->Output($strNombreArchivo . '.pdf', 'D');
            }

            exit();
        }
    }
}

class expediente_model
{

    public function getListadoColaboradoresArea($intArea = 0)
    {

        $arrColaboradores = array();
        if ($intArea > 0) {
            $conn = getConexion();
            $strQuery = "SELECT colaborador.id,
                                colaborador.cif,
                                CONCAT(colaborador.nombres,' ', colaborador.apellidos) nombrecompleto
                           FROM colaborador
                                INNER JOIN area
                                        ON colaborador.area = area.id
                          WHERE area.id = {$intArea} 
                          ORDER BY colaborador.nombres";
            $result = mysqli_query($conn, $strQuery);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $arrColaboradores[$row["id"]]["CIF"] = $row["cif"];
                    $arrColaboradores[$row["id"]]["NOMBRECOMPLETO"] = $row["nombrecompleto"];
                }
            }

            mysqli_close($conn);
        }

        return $arrColaboradores;
    }

    public function getIDExpedienteColaborador($intColaborador)
    {
        if ($intColaborador > 0) {
            $conn = getConexion();
            $intIDExpediente = 0;
            $strQueryExp = "SELECT id FROM expedientes WHERE colaborador = {$intColaborador}";
            $result = mysqli_query($conn, $strQueryExp);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $intIDExpediente = $row["id"];
                }
            }

            mysqli_close($conn);
            return $intIDExpediente;
        }
    }


    public function getInfoColaborador($intColaborador)
    {

        $arrColaborador = array();
        if ($intColaborador > 0) {
            $conn = getConexion();

            //Obtener el ID del expediente
            $intIDExpediente = 0;
            $strQueryExp = "SELECT id FROM expedientes WHERE colaborador = {$intColaborador}";
            $result = mysqli_query($conn, $strQueryExp);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $intIDExpediente = $row["id"];
                }
            }

            //Obtener el comentario del expediente
            $strQueryComment = "SELECT expedientes.comentario
                                  FROM expedientes 
                                 WHERE expedientes.colaborador = {$intColaborador}";
            $result = mysqli_query($conn, $strQueryComment);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $arrColaborador[$intColaborador]["COMENTARIO"][$intColaborador]["COMENTARIO"] = $row["comentario"];
                }
            }

            //Obtener el detalle de los datos personales
            $strQuery = "SELECT colaborador.id,
                                colaborador.nombres,
                                colaborador.apellidos,
                                colaborador.cif,
                                colaborador.puesto,
                                area.nombre nombrearea,
                                colaborador.activo
                           FROM colaborador
                                INNER JOIN area 
                                        ON colaborador.area = area.id
                          WHERE colaborador.id = {$intColaborador}";
            $result = mysqli_query($conn, $strQuery);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $arrColaborador[$intColaborador]["DATOS_PERSONALES"][$intColaborador]["NOMBRES"] = $row["nombres"];
                    $arrColaborador[$intColaborador]["DATOS_PERSONALES"][$intColaborador]["APELLIDOS"] = $row["apellidos"];
                    $arrColaborador[$intColaborador]["DATOS_PERSONALES"][$intColaborador]["CIF"] = $row["cif"];
                    $arrColaborador[$intColaborador]["DATOS_PERSONALES"][$intColaborador]["PUESTO"] = $row["puesto"];
                    $arrColaborador[$intColaborador]["DATOS_PERSONALES"][$intColaborador]["NOMBREAREA"] = $row["nombrearea"];
                    $arrColaborador[$intColaborador]["DATOS_PERSONALES"][$intColaborador]["ACTIVO"] = ($row["activo"] == 1) ? "Si" : "No";
                }
            }

            //Obtener el detalle de los datos del equipo del expediente
            $strQueryComment = "SELECT expedientes.nombreequipo, 
                                       expedientes.direccionip,
                                       expedientes.dominio,
                                       expedientes.usuario
                                  FROM expedientes 
                                 WHERE expedientes.colaborador = {$intColaborador}";
            $result = mysqli_query($conn, $strQueryComment);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $arrColaborador[$intColaborador]["DATOSEQUIPO"][$intColaborador]["NOMBREEQUIPO"] = $row["nombreequipo"];
                    $arrColaborador[$intColaborador]["DATOSEQUIPO"][$intColaborador]["DIRECCIONIP"] = $row["direccionip"];
                    $arrColaborador[$intColaborador]["DATOSEQUIPO"][$intColaborador]["DOMINIO"] = $row["dominio"];
                    $arrColaborador[$intColaborador]["DATOSEQUIPO"][$intColaborador]["USUARIO"] = $row["usuario"];
                    $arrColaborador[$intColaborador]["DATOS_PERSONALES"][$intColaborador]["USUARIO"] = $row["usuario"];
                }
            }

            //Obtener el detalle de hardware del expediente
            $strQuery = "SELECT expediente_detail.id iddetail,
                                componente.id idcomponente, 
                                componente.nombre nombrecomponente, 
                                expediente_detail.marca, 
                                expediente_detail.valor,
                                expediente_detail.serie,
                                expediente_detail.modelo,
                                expediente_detail.linea,
                                expediente_detail.anio  
                           FROM expediente_detail 
                                INNER JOIN componente 
                                        ON expediente_detail.componente = componente.id
                                INNER JOIN categoria 
                                        ON componente.categoria = categoria.id
                          WHERE expediente_detail.expediente = {$intIDExpediente}
                            AND categoria.id = 1";
            $result = mysqli_query($conn, $strQuery);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $arrColaborador[$intColaborador]["DETALLE_HARDWARE"][$row["iddetail"]]["ID_COMPONENTE"] = $row["idcomponente"];
                    $arrColaborador[$intColaborador]["DETALLE_HARDWARE"][$row["iddetail"]]["NOMBRE_COMPONENTE"] = $row["nombrecomponente"];
                    $arrColaborador[$intColaborador]["DETALLE_HARDWARE"][$row["iddetail"]]["MARCA"] = $row["marca"];
                    $arrColaborador[$intColaborador]["DETALLE_HARDWARE"][$row["iddetail"]]["VALOR"] = $row["valor"];
                    $arrColaborador[$intColaborador]["DETALLE_HARDWARE"][$row["iddetail"]]["SERIE"] = $row["serie"];
                    $arrColaborador[$intColaborador]["DETALLE_HARDWARE"][$row["iddetail"]]["MODELO"] = $row["modelo"];
                    $arrColaborador[$intColaborador]["DETALLE_HARDWARE"][$row["iddetail"]]["LINEA"] = $row["linea"];
                    $arrColaborador[$intColaborador]["DETALLE_HARDWARE"][$row["iddetail"]]["ANIO"] = $row["anio"];
                }
            }

            //Obtener el detalle de plataformas del expediente
            $strQuery = "SELECT expediente_detail.id iddetail,
                                componente.id idcomponente, 
                                componente.nombre nombrecomponente,
                                expediente_detail.usuario,
                                expediente_detail.operador
                           FROM expediente_detail 
                                INNER JOIN componente 
                                        ON expediente_detail.componente = componente.id
                                INNER JOIN categoria 
                                        ON componente.categoria = categoria.id
                          WHERE expediente_detail.expediente = {$intIDExpediente}
                            AND categoria.id = 5";
            $result = mysqli_query($conn, $strQuery);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $arrColaborador[$intColaborador]["PLATAFORMAS"][$row["iddetail"]]["ID_COMPONENTE"] = $row["idcomponente"];
                    $arrColaborador[$intColaborador]["PLATAFORMAS"][$row["iddetail"]]["NOMBRE_COMPONENTE"] = $row["nombrecomponente"];
                    $arrColaborador[$intColaborador]["PLATAFORMAS"][$row["iddetail"]]["USUARIO"] = $row["usuario"];
                    $arrColaborador[$intColaborador]["PLATAFORMAS"][$row["iddetail"]]["OPERADOR"] = $row["operador"];
                }
            }

            //Obtener el detalle de software del expediente
            $strQuery = "SELECT expediente_detail.id iddetail,
                                componente.id idcomponente, 
                                componente.nombre nombrecomponente,
                                expediente_detail.pago,
                                expediente_detail.version
                           FROM expediente_detail 
                                INNER JOIN componente 
                                        ON expediente_detail.componente = componente.id
                                INNER JOIN categoria 
                                        ON componente.categoria = categoria.id
                          WHERE expediente_detail.expediente = {$intIDExpediente}
                            AND categoria.id = 2";
            $result = mysqli_query($conn, $strQuery);
            if (!empty($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $arrColaborador[$intColaborador]["DETALLE_SOFTWARE"][$row["iddetail"]]["ID_COMPONENTE"] = $row["idcomponente"];
                    $arrColaborador[$intColaborador]["DETALLE_SOFTWARE"][$row["iddetail"]]["NOMBRE_COMPONENTE"] = $row["nombrecomponente"];
                    $arrColaborador[$intColaborador]["DETALLE_SOFTWARE"][$row["iddetail"]]["PAGO"] = $row["pago"];
                    $arrColaborador[$intColaborador]["DETALLE_SOFTWARE"][$row["iddetail"]]["VERSION"] = $row["version"];
                }
            }

            mysqli_close($conn);
        }
        return $arrColaborador;
    }

    public function getArea()
    {
        $conn = getConexion();
        $arrArea = array();
        $strQuery = "SELECT DISTINCT area.id, 
                            area.nombre 
                       FROM expedientes 
                            INNER JOIN colaborador 
                                    ON expedientes.colaborador = colaborador.id
                            INNER JOIN area 
                                    ON colaborador.area = area.id
                      ORDER BY area.nombre";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrArea[$row["id"]]["NOMBRE"] = $row["nombre"];
            }
        }

        mysqli_close($conn);
        return $arrArea;
    }

    public function getTipoEquipo()
    {
        $conn = getConexion();
        $arrTipoEquipo = array();
        $strQuery = "SELECT id, nombre FROM tipoequipo ORDER BY nombre";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrTipoEquipo[$row["id"]]["NOMBRE"] = $row["nombre"];
            }
        }

        mysqli_close($conn);
        return $arrTipoEquipo;
    }

    public function getComponenteHD()
    {
        $conn = getConexion();
        $arrComponenteHD = array();
        $strQuery = "SELECT id, nombre FROM componente WHERE categoria = 1 ORDER BY nombre";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrComponenteHD[$row["id"]]["NOMBRE"] = $row["nombre"];
            }
        }

        mysqli_close($conn);
        return $arrComponenteHD;
    }

    public function getComponenteSW()
    {
        $conn = getConexion();
        $arrComponenteSW = array();
        $strQuery = "SELECT id, nombre FROM componente WHERE categoria = 2 ORDER BY nombre";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrComponenteSW[$row["id"]]["NOMBRE"] = $row["nombre"];
            }
        }

        mysqli_close($conn);
        return $arrComponenteSW;
    }

    public function getComponentePL()
    {
        $conn = getConexion();
        $arrComponentePL = array();
        $strQuery = "SELECT id, nombre FROM componente WHERE categoria = 5 ORDER BY nombre";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrComponentePL[$row["id"]]["NOMBRE"] = $row["nombre"];
            }
        }

        mysqli_close($conn);
        return $arrComponentePL;
    }

    public function insertExpDetail($intIDExpediente, $intComponente, $strMarca = "", $strValor = "", $intPago = 0, $intVersion = 0, $strSerie = "", $strModelo = "", $strLinea = "", $strUsuario = "", $strOperador = "", $intAnio = 0, $intUser)
    {
        if ($intIDExpediente > 0 && $intComponente > 0  && $intUser > 0) {
            $conn = getConexion();
            $strQuery = "INSERT INTO expediente_detail (expediente, componente, marca, valor, pago, version, serie, modelo, linea, anio, usuario, operador, add_fecha, add_user) VALUES ({$intIDExpediente},{$intComponente},'{$strMarca}','{$strValor}',{$intPago},'{$intVersion}','{$strSerie}','{$strModelo}','{$strLinea}',{$intAnio},'{$strUsuario}','{$strOperador}',now(), {$intUser})";
            mysqli_query($conn, $strQuery);
        }
    }

    public function deleteExpDetail($intIDExpediente, $intIDExpDetail)
    {
        if ($intIDExpediente > 0 && $intIDExpDetail) {
            $conn = getConexion();
            $strQuery = "DELETE FROM expediente_detail WHERE id = {$intIDExpDetail} AND expediente = {$intIDExpediente}";
            mysqli_query($conn, $strQuery);
        }
    }

    public function updateExpDetail($intIDExpDetail, $intIDExpediente, $intComponente, $strMarca = "", $strValor = "", $intPago = 0, $intVersion = 0, $strSerie = "", $strModelo = "", $strLinea = "", $intAnio = 0, $strUsuario = "", $strOperador = "", $intUser)
    {
        if ($intIDExpDetail > 0 && $intIDExpediente > 0 && $intComponente > 0  && $intUser > 0) {
            $conn = getConexion();
            $strMarcaLabel = ($strMarca != '') ? "marca= '{$strMarca}'," : "";
            $strQuery = "UPDATE expediente_detail 
                            SET componente = {$intComponente}, 
                                {$strMarcaLabel} 
                                valor = '{$strValor}',
                                pago = {$intPago}, 
                                version = '{$intVersion}', 
                                serie = '{$strSerie}', 
                                modelo = '{$strModelo}', 
                                linea = '{$strLinea}', 
                                anio = {$intAnio},
                                usuario = '{$strUsuario}',
                                operador = '{$strOperador}',
                                mod_user = {$intUser},
                                mod_fecha = now()
                          WHERE expediente = {$intIDExpediente} 
                            AND id = {$intIDExpDetail}";
            mysqli_query($conn, $strQuery);
        }
    }

    public function updateComentarioExp($intExpediente, $strComentario)
    {
        if ($intExpediente > 0) {
            $conn = getConexion();
            $strQuery = "UPDATE expedientes SET comentario = '{$strComentario}' WHERE id = {$intExpediente}";
            mysqli_query($conn, $strQuery);
        }
    }

    public function updateDatosEquipo($intExpediente, $strNombreEquipo = "", $strDireccionIP = "", $strDominio = "", $strUsuario = "")
    {
        if ($intExpediente > 0) {
            $conn = getConexion();
            $strQuery = "UPDATE expedientes 
                            SET nombreequipo = '{$strNombreEquipo}',
                                direccionip = '{$strDireccionIP}',
                                dominio = '{$strDominio}',
                                usuario = '{$strUsuario}'
                          WHERE id = {$intExpediente}";
            mysqli_query($conn, $strQuery);
        }
    }
}

class expediente_view
{

    private $objModel;
    private $arrRolUser;

    public function __construct($arrRolUser)
    {
        $this->objModel = new expediente_model();
        $this->arrRolUser = $arrRolUser;
    }

    public function drawSelectArea()
    {
        $arrArea = $this->objModel->getArea();
?>
        <label>
            <h4>1. Seleccione un área:</h4>
        </label>
        <select id="selectArea" name="selectArea" style="text-align:center; width:100%;" class="form-control select2" onchange="loadColaboradores()">
            <?php
            reset($arrArea);
            while ($rTMP = each($arrArea)) {
            ?>
                <option value="<?php print $rTMP["key"]; ?>"><?php print $rTMP["value"]["NOMBRE"]; ?></option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    public function drawSelectTipoEquipo($intTipo = 0)
    {
        $arrTipoEquipo = $this->objModel->getTipoEquipo();
    ?>
        <select id="selectTipoEquipo" name="selectTipoEquipo" style="text-align: center;" class="form-control">
            <?php
            reset($arrTipoEquipo);
            while ($rTMP = each($arrTipoEquipo)) {
                $strSelected = (($rTMP["key"] == $intTipo)) ? 'selected' : '';
            ?>
                <option value="<?php print $rTMP["key"]; ?>" <?php print $strSelected; ?>><?php print $rTMP["value"]["NOMBRE"]; ?></option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    public function drawSelectColaborador($intArea = 0)
    {
        $arrColaboradores = $this->objModel->getListadoColaboradoresArea($intArea);
    ?>
        <label>
            <h4>2. Seleccione un colaborador:</h4>
        </label>
        <select id="selectColaboradores" name="selectColaboradores" style="text-align:center; width:100%;" class="form-control select2">
            <option value="0">-- Seleccione un colaborador --</option>
            <?php
            reset($arrColaboradores);
            while ($rTMP = each($arrColaboradores)) {
                $intID = $rTMP["key"];
                $strLabel = $rTMP["value"]["CIF"] . ' - ' . $rTMP["value"]["NOMBRECOMPLETO"]
            ?>
                <option value="<?php print $intID; ?>"><?php print $strLabel; ?></option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    public function drawSelectActivo($intId = 0, $intActivo = 0)
    {
    ?>
        <select id="selectActivo_<?php print $intId; ?>" name="selectActivo_<?php print $intId; ?>" style="text-align: center;" class="form-control">
            <option value="0" <?php print ($intActivo == 0) ? "selected" : ""; ?>>No</option>
            <option value="1" <?php print ($intActivo == 1) ? "selected" : ""; ?>>Si</option>
        </select>
        <?php
    }

    public function drawExportReport($arrColaborador)
    {
        if (count($arrColaborador) > 0) {
            $strHTML = '';
            $strHTML .= '<table>';
            $strHTML .= '<tr>';
            $strHTML .= '<td></td>';
            $strHTML .= '<td></td>';
            $strHTML .= '<td></td>';
            $strHTML .= '<td></td>';
            $strHTML .= '<td></td>';
            $strHTML .= '<td></td>';
            $strHTML .= '<td></td>';
            $strHTML .= '<td></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="4" style="text-align:left;"><h1>Reporte Inventario IT</h1></td>';
            $strHTML .= '</tr>';
            $hoy = date("d-m-Y");
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="8" style="text-align:left;">Fecha de impresion: ' . $hoy . '</td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="8"></td>';
            $strHTML .= '</tr>';

            reset($arrColaborador);
            while ($rTMP = each($arrColaborador)) {
                $strHTML .= '<tr bgcolor="#b4e3ef">';
                $strHTML .= '<td colspan="4" style="text-align:center;border: 0.5px solid black;"><h2>Datos Personales</h2></td>';
                $strHTML .= '<td colspan="4" style="text-align:center;border: 0.5px solid black;"><h2>Datos del Equipo</h2></td>';
                $strHTML .= '</tr>';
                $strHTML .= '<tr>';

                //DATOS PERSONALES
                $strHTML .= '<td colspan="4" style="padding:10px; border: 0.5px solid black;">';
                if (isset($rTMP["value"]["DATOS_PERSONALES"])) {
                    $strHTML .= '<table>';
                    $strHTML .= '<tr>';
                    $strHTML .= '<td></td>';
                    $strHTML .= '</tr>';
                    reset($rTMP["value"]["DATOS_PERSONALES"]);
                    while ($rTMP2 = each($rTMP["value"]["DATOS_PERSONALES"])) {
                        $strNombres = utf8_decode($rTMP2["value"]["NOMBRES"]);
                        $strApellidos = utf8_decode($rTMP2["value"]["APELLIDOS"]);
                        $strCIF = $rTMP2["value"]["CIF"];
                        $strPuesto = utf8_decode($rTMP2["value"]["PUESTO"]);
                        $strArea = utf8_decode($rTMP2["value"]["NOMBREAREA"]);
                        $strUsuario = $rTMP2["value"]["USUARIO"];
                        $strActivo = $rTMP2["value"]["ACTIVO"];
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="4" style="text-align:left;"><b>Nombres:</b> ' . $strNombres . ' ' . $strApellidos . '</td>';
                        $strHTML .= '</tr>';
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="4" style="text-align:left;"><b>CIF:</b> ' . $strCIF . '</td>';
                        $strHTML .= '</tr>';
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="4" style="text-align:left;"><b>Puesto:</b> ' . $strPuesto . '</td>';
                        $strHTML .= '</tr>';
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="4" style="text-align:left;"><b>Area:</b> ' . $strArea . '</td>';
                        $strHTML .= '</tr>';
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="4" style="text-align:left;"><b>En uso:</b> ' . $strActivo . '</td>';
                        $strHTML .= '</tr>';
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="4" style="text-align:left;"><b>Usuario:</b> ' . $strUsuario . '</td>';
                        $strHTML .= '</tr>';
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="8"></td>';
                        $strHTML .= '</tr>';
                    }
                    $strHTML .= '</table>';
                }
                $strHTML .= '</td>';

                //DATOS DEL EQUIPO
                $strHTML .= '<td colspan="4" style="padding:10px;  border: 0.5px solid black;">';
                if (isset($rTMP["value"]["DATOSEQUIPO"])) {
                    $strHTML .= '<table>';
                    $strHTML .= '<tr>';
                    $strHTML .= '<td></td>';
                    $strHTML .= '</tr>';
                    reset($rTMP["value"]["DATOSEQUIPO"]);
                    while ($rTMP5 = each($rTMP["value"]["DATOSEQUIPO"])) {
                        $strNombreEquipo = $rTMP5["value"]["NOMBREEQUIPO"];
                        $strDireccionIP = $rTMP5["value"]["DIRECCIONIP"];
                        $strDominio = ($rTMP5["value"]["DOMINIO"] != "") ? $rTMP5["value"]["DOMINIO"] : "No";
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="4" style="text-align:left;"><b>Nombre del Equipo:</b> ' . $strNombreEquipo . '</td>';
                        $strHTML .= '</tr>';
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="4" style="text-align:left;"><b>Direccion IP:</b> ' . $strDireccionIP . '</td>';
                        $strHTML .= '</tr>';
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="4" style="text-align:left;"><b>Dominio:</b> ' . $strDominio . '</td>';
                        $strHTML .= '</tr>';
                    }
                    $strHTML .= '</table>';
                }
                $strHTML .= '</td>';
                $strHTML .= '</tr>';
                $strHTML .= '<tr>';
                $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
                $strHTML .= '</tr>';

                //HARDWARE
                if (isset($rTMP["value"]["DETALLE_HARDWARE"])) {
                    $strHTML .= '<tr bgcolor="#b4e3ef">';
                    $strHTML .= '<td colspan="8" style="text-align:center; border: 0.5px solid black;"><h2>Hardware</h2></td>';
                    $strHTML .= '</tr>';
                    $strHTML .= '<tr>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="5%"><b>No.</b></td>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="15%"><b>Nombre</b></td>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="12%"><b>Marca</b></td>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="13%"><b>No. Activo</b></td>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="19%"><b>Serie</b></td>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="13%"><b>Modelo</b></td>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="13%"><b>Linea</b></td>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="10%"><b>' . utf8_decode("Año") . '</b></td>';
                    $strHTML .= '</tr>';
                    $intCountHD = 0;
                    reset($rTMP["value"]["DETALLE_HARDWARE"]);
                    while ($rTMP2 = each($rTMP["value"]["DETALLE_HARDWARE"])) {
                        $intCountHD++;
                        $intID = $rTMP2["key"];
                        $intIDComponente = intval($rTMP2["value"]["ID_COMPONENTE"]);
                        $strNombreHD = utf8_decode($rTMP2["value"]["NOMBRE_COMPONENTE"]);
                        $strMarcaHD = utf8_decode($rTMP2["value"]["MARCA"]);
                        $strValorHD = $rTMP2["value"]["VALOR"];
                        $strSerieHD = $rTMP2["value"]["SERIE"];
                        $strModeloHD = $rTMP2["value"]["MODELO"];
                        $strLineaD = $rTMP2["value"]["LINEA"];
                        $intAnioHD = (intval($rTMP2["value"]["ANIO"]) == 0) ? "" : intval($rTMP2["value"]["ANIO"]);
                        $strHTML .= '<tr>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $intCountHD . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strNombreHD . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strMarcaHD . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strValorHD . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strSerieHD . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strModeloHD . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strLineaD . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $intAnioHD . '</td>';
                        $strHTML .= '</tr>';
                    }
                    $strHTML .= '<tr>';
                    $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
                    $strHTML .= '</tr>';
                }

                //SOFTWARE
                if (isset($rTMP["value"]["DETALLE_SOFTWARE"])) {
                    $strHTML .= '<tr bgcolor="#b4e3ef">';
                    $strHTML .= '<td colspan="8" style="text-align:center; border: 1px solid black;"><h2>Software</h2></td>';
                    $strHTML .= '</tr>';
                    $strHTML .= '<tr>';
                    $strHTML .= '<td width="10%" style="text-align:center;border: 0.5px solid black;"><b>No.</b></td>';
                    $strHTML .= '<td width="40%" style="text-align:center;border: 0.5px solid black;"><b>Nombre</b></td>';
                    $strHTML .= '<td width="15%" style="text-align:center;border: 0.5px solid black;"><b>Licencia</b></td>';
                    $strHTML .= '<td width="35%" style="text-align:center;border: 0.5px solid black;"><b>Version</b></td>';
                    $strHTML .= '</tr>';
                    $intCountSW = 0;
                    reset($rTMP["value"]["DETALLE_SOFTWARE"]);
                    while ($rTMP3 = each($rTMP["value"]["DETALLE_SOFTWARE"])) {
                        $intCountSW++;
                        $intID = $rTMP3["key"];
                        $intIDComponente = intval($rTMP3["value"]["ID_COMPONENTE"]);
                        $strNombreSW = utf8_decode($rTMP3["value"]["NOMBRE_COMPONENTE"]);
                        $intPago = $rTMP3["value"]["PAGO"];
                        $strPagoSW = ($intPago == 1) ? "Si" : "No";
                        $strVersionSW = $rTMP3["value"]["VERSION"];
                        $strHTML .= '<tr>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $intCountSW . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strNombreSW . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strPagoSW . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strVersionSW . '</td>';
                        $strHTML .= '</tr>';
                    }
                    $strHTML .= '<tr>';
                    $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
                    $strHTML .= '</tr>';
                }

                //PLATAFORMAS
                if (isset($rTMP["value"]["PLATAFORMAS"])) {
                    $strHTML .= '<tr bgcolor="#b4e3ef">';
                    $strHTML .= '<td colspan="4" style="text-align:center; border: 0.5px solid black;"><h2>Plataformas</h2></td>';
                    $strHTML .= '</tr>';
                    $strHTML .= '<tr>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="10%"><b>No.</b></td>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="40%"><b>Nombre</b></td>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="25%"><b>Usuario</b></td>';
                    $strHTML .= '<td style="text-align:center;border: 0.5px solid black;" width="25%"><b>Operador</b></td>';
                    $strHTML .= '</tr>';
                    $intCountPl = 0;
                    reset($rTMP["value"]["PLATAFORMAS"]);
                    while ($rTMP3 = each($rTMP["value"]["PLATAFORMAS"])) {
                        $intCountPl++;
                        $intID = $rTMP3["key"];
                        $strNombrePl = utf8_decode($rTMP3["value"]["NOMBRE_COMPONENTE"]);
                        $strUsuarioPl = $rTMP3["value"]["USUARIO"];
                        $strOperadorPl = $rTMP3["value"]["OPERADOR"];
                        $strHTML .= '<tr>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $intCountPl . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strNombrePl . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strUsuarioPl . '</td>';
                        $strHTML .= '<td style="text-align:center;border: 0.5px solid black;">' . $strOperadorPl . '</td>';
                        $strHTML .= '</tr>';
                    }
                    $strHTML .= '<tr>';
                    $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
                    $strHTML .= '</tr>';
                }



                //COMENTARIO
                if (isset($rTMP["value"]["COMENTARIO"])) {
                    $strHTML .= '<tr>';
                    $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
                    $strHTML .= '</tr>';
                    reset($rTMP["value"]["COMENTARIO"]);
                    while ($rTMP4 = each($rTMP["value"]["COMENTARIO"])) {
                        $strComentario = $rTMP4["value"]["COMENTARIO"];
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="8" style="text-align:left;"><h2>Observaciones:</h2></td>';
                        $strHTML .= '</tr>';
                        $strHTML .= '<tr>';
                        $strHTML .= '<td colspan="8" style="text-align:left;"><div>' . $strComentario . '</div></td>';
                        $strHTML .= '</tr>';
                    }
                }
            }

            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="2" style="text-align:center;">_________________________________________</td>';
            $strHTML .= '<td colspan="2" style="text-align:center;">_________________________________________</td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="2" style="text-align:center;">' . $strNombres . ' ' . $strApellidos . '</td>';
            $strHTML .= '<td colspan="2" style="text-align:center;">' . $this->arrRolUser["NAME"] . '</td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="2" style="text-align:center;">' . $strPuesto . '</td>';
            $strHTML .= '<td colspan="2" style="text-align:center;">' . $this->arrRolUser["PUESTO"] . '</td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="8" style="text-align:left;"></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="2" style="text-align:center;">_________________________________________</td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="2" style="text-align:center;">Jefe de Area</td>';
            $strHTML .= '</tr>';
            $strHTML .= '</table>';
            return $strHTML;
        }
    }

    public function drawContentExpediente($intColaborador = 0)
    {
        $arrColaborador = $this->objModel->getInfoColaborador($intColaborador);
        if (count($arrColaborador) > 0) {
            reset($arrColaborador);
            while ($rTMP = each($arrColaborador)) {
            ?>
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <br><br><br>
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="nav-icon fa fa-book"></i> Datos del colaborador</h3>
                        </div>
                        <div class="card-body" id="divDatosColaborador">
                            <table class="table" id="tblDatosPersonales">
                                <tr style="display:none;">
                                    <td colspan="6"><input type="hidden" id="hdnColaborador" name="hdnColaborador" value="<?php print $intColaborador; ?>"></td>
                                </tr>
                                <?php
                                reset($rTMP["value"]["DATOS_PERSONALES"]);
                                while ($rTMP2 = each($rTMP["value"]["DATOS_PERSONALES"])) {
                                    $strNombres = $rTMP2["value"]["NOMBRES"];
                                    $strApellidos = $rTMP2["value"]["APELLIDOS"];
                                    $strCIF = $rTMP2["value"]["CIF"];
                                    $strPuesto = $rTMP2["value"]["PUESTO"];
                                    $strArea = $rTMP2["value"]["NOMBREAREA"];
                                    $strActivo = $rTMP2["value"]["ACTIVO"];
                                ?>
                                    <tr>
                                        <td style="text-align:center;"><b>Nombres:</b></td>
                                        <td><?php print $strNombres; ?></td>
                                        <td style="text-align:center;"><b>Apellidos:</b> </td>
                                        <td><?php print $strApellidos; ?></td>
                                        <td style="text-align:center;"><b>CIF:</b> </td>
                                        <td><?php print $strCIF; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:center;"><b>Puesto:</b> </td>
                                        <td><?php print $strPuesto; ?></td>
                                        <td style="text-align:center;"><b>Area:</b> </td>
                                        <td><?php print $strArea; ?></td>
                                        <td style="text-align:center;"><b>En uso:</b> </td>
                                        <td><?php print $strActivo; ?></td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <br><br><br>
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="nav-icon fa fa-book"></i> Datos del Equipo</h3>
                        </div>
                        <div class="card-body">
                            <table class="table" id="tblDatosEquipo">
                                <?php
                                if (isset($rTMP["value"]["DATOSEQUIPO"])) {
                                    $intCountHD = 0;
                                    reset($rTMP["value"]["DATOSEQUIPO"]);
                                    while ($rTMP5 = each($rTMP["value"]["DATOSEQUIPO"])) {
                                        $strNombreEquipo = $rTMP5["value"]["NOMBREEQUIPO"];
                                        $strDireccionIP = $rTMP5["value"]["DIRECCIONIP"];
                                        $strDominio = $rTMP5["value"]["DOMINIO"];
                                        $strUsuario = $rTMP5["value"]["USUARIO"];
                                    ?>
                                        <tr>
                                            <td style="text-align:center; vertical-align:middle;"><b>Nombre PC</b></td>
                                            <td><input class="form-control" type="text" id="txtNombrePC" name="txtNombrePC" value="<?php print $strNombreEquipo; ?>"></td>
                                            <td>&nbsp;</td>
                                            <td style="text-align:center; vertical-align:middle;"><b>Dirección IP</b></td>
                                            <td><input class="form-control" type="text" id="txtIPPC" name="txtIPPC" value="<?php print $strDireccionIP; ?>"></td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:center; vertical-align:middle;"><b>Dominio</b></td>
                                            <td>
                                                <select class="form-control" id="txtDominicioPC" name="txtDominicioPC">
                                                    <option value="No" <?php print ( $strDominio =="No" )?"selected":""; ?>>No</option>
                                                    <option value="Si" <?php print ( $strDominio =="Si" )?"selected":""; ?>>Si</option>
                                                </select>
                                            <td>&nbsp;</td>
                                            <td style="text-align:center; vertical-align:middle;"><b>Usuario</b></td>
                                            <td><input class="form-control" type="text" id="txtUsuarioPC" name="txtUsuarioPC" value="<?php print $strUsuario; ?>"></td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    <?php
                                    }
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <br><br><br>
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="nav-icon fa fa-book"></i> Registro de Hardware</h3>
                        </div>
                        <div class="card-body">
                            <div id="no-more-tables">
                                <table class="table table-sm table-hover table-condensed" id="tblHardware">
                                    <thead class="cf">
                                        <tr style="background-color: #dc3545; color:white;">
                                            <th style="text-align:center;">No.</th>
                                            <th style="text-align:center;">Nombre</th>
                                            <th style="text-align:center;">Marca</th>
                                            <th style="text-align:center;">No. Activo</th>
                                            <th style="text-align:center;">Serie</th>
                                            <th style="text-align:center;">Modelo</th>
                                            <th style="text-align:center;">Linea</th>
                                            <th style="text-align:center;">Año</th>
                                            <th colspan="2">&nbsp;</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (isset($rTMP["value"]["DETALLE_HARDWARE"])) {
                                            $intCountHD = 0;
                                            reset($rTMP["value"]["DETALLE_HARDWARE"]);
                                            while ($rTMP3 = each($rTMP["value"]["DETALLE_HARDWARE"])) {
                                                $intCountHD++;
                                                $intID = $rTMP3["key"];
                                                $intIDComponente = intval($rTMP3["value"]["ID_COMPONENTE"]);
                                                $strNombreHD = isset($rTMP3["value"]["NOMBRE_COMPONENTE"]) ? $rTMP3["value"]["NOMBRE_COMPONENTE"] : "N/A";
                                                $strMarcaHD = isset($rTMP3["value"]["MARCA"]) ? $rTMP3["value"]["MARCA"] : "N/A";
                                                $strValorHD = isset($rTMP3["value"]["VALOR"]) ? $rTMP3["value"]["VALOR"] : "N/A";
                                                $strSerieHD = isset($rTMP3["value"]["SERIE"]) ? $rTMP3["value"]["SERIE"] : "N/A";
                                                $strModeloHD = isset($rTMP3["value"]["MODELO"]) ? $rTMP3["value"]["MODELO"] : "N/A";
                                                $strLineaD = isset($rTMP3["value"]["LINEA"]) ? $rTMP3["value"]["LINEA"] : "N/A";
                                                $intAnioHD = isset($rTMP3["value"]["ANIO"]) ? $rTMP3["value"]["ANIO"] : "N/A";
                                        ?>
                                                <tr id="trHardware_<?php print $intID; ?>">
                                                    <td data-title="No." style="text-align:center; vertical-align:middle;">
                                                        <h3><span class="badge badge-danger"><?php print $intCountHD; ?></span></h3>
                                                        <input id="hdnIdHW_<?php print $intID; ?>" name="hdnIdHW_<?php print $intID; ?>" type="hidden" value="N">
                                                    </td>
                                                    <td data-title="Nombre" style="text-align:center; vertical-align:middle;">
                                                        <div id="divShowHDNombre_<?php print $intID; ?>">
                                                            <?php print $strNombreHD; ?>
                                                        </div>
                                                        <div id="divEditHDNombre_<?php print $intID; ?>" style="display:none;">
                                                            <?php $this->drawSelectComponenteHD($intID, $intIDComponente); ?>
                                                        </div>
                                                    </td>
                                                    <td data-title="Marca" style="text-align:center; vertical-align:middle;">
                                                        <div id="divShowHDMarca_<?php print $intID; ?>">
                                                            <?php print $strMarcaHD; ?>
                                                        </div>
                                                        <div id="divEditHDMarca_<?php print $intID; ?>" style="display:none;">
                                                            <input class="form-control" type="text" id="txtHDMarca_<?php print $intID; ?>" name="txtHDMarca_<?php print $intID; ?>" value="<?php print $strMarcaHD; ?>">
                                                        </div>
                                                    </td>
                                                    <td data-title="Valor" style="text-align:center; vertical-align:middle;">
                                                        <div id="divShowHDValor_<?php print $intID; ?>">
                                                            <?php print $strValorHD; ?>
                                                        </div>
                                                        <div id="divEditHDValor_<?php print $intID; ?>" style="display:none;">
                                                            <input class="form-control" type="text" id="txtHDValor_<?php print $intID; ?>" name="txtHDValor_<?php print $intID; ?>" value="<?php print $strValorHD; ?>">
                                                        </div>
                                                    </td>
                                                    <td data-title="Serie" style="text-align:center; vertical-align:middle;">
                                                        <div id="divShowHDSerie_<?php print $intID; ?>">
                                                            <?php print $strSerieHD; ?>
                                                        </div>
                                                        <div id="divEditHDSerie_<?php print $intID; ?>" style="display:none;">
                                                            <input class="form-control" type="text" id="txtHDSerie_<?php print $intID; ?>" name="txtHDSerie_<?php print $intID; ?>" value="<?php print $strSerieHD; ?>">
                                                        </div>
                                                    </td>
                                                    <td data-title="Modelo" style="text-align:center; vertical-align:middle;">
                                                        <div id="divShowHDModelo_<?php print $intID; ?>">
                                                            <?php print $strModeloHD; ?>
                                                        </div>
                                                        <div id="divEditHDModelo_<?php print $intID; ?>" style="display:none;">
                                                            <input class="form-control" type="text" id="txtHDModelo_<?php print $intID; ?>" name="txtHDModelo_<?php print $intID; ?>" value="<?php print $strModeloHD; ?>">
                                                        </div>
                                                    </td>
                                                    <td data-title="Linea" style="text-align:center; vertical-align:middle;">
                                                        <div id="divShowHDLinea_<?php print $intID; ?>">
                                                            <?php print $strLineaD; ?>
                                                        </div>
                                                        <div id="divEditHDLinea_<?php print $intID; ?>" style="display:none;">
                                                            <input class="form-control" type="text" id="txtHDLinea_<?php print $intID; ?>" name="txtHDLinea_<?php print $intID; ?>" value="<?php print $strLineaD; ?>">
                                                        </div>
                                                    </td>
                                                    <td data-title="Año" style="text-align:center; vertical-align:middle;">
                                                        <div id="divShowHDAnio_<?php print $intID; ?>">
                                                            <?php print $intAnioHD; ?>
                                                        </div>
                                                        <div id="divEditHDAnio_<?php print $intID; ?>" style="display:none;">
                                                            <input class="form-control" type="number" id="txtHDAnio_<?php print $intID; ?>" name="txtHDAnio_<?php print $intID; ?>" value="<?php print $intAnioHD; ?>">
                                                        </div>
                                                    </td>
                                                    <td style="text-align:center;">
                                                        <button class="btn btn-secondary btn-block" onclick="editHD('<?php print $intID; ?>')"><i class="fa fa-pencil"></i> Editar</button>
                                                        <button class="btn btn-secondary btn-block" onclick="deleteHD('<?php print $intID; ?>')"><i class="fa fa-trash"></i> Eliminar</button>
                                                    </td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                    </tbody>
                                </table>
                                <table class="table table-sm table-hover table-condensed">
                                    <tr>
                                        <td style="text-align:left;"><button class="btn btn-danger" onclick="agregarHD()"><i class="fa fa-plus"></i> Agregar</button></td>
                                    </tr>
                                </table>
                            <?php
                                        } else {
                            ?>
                                </tbody>
                                </table>
                                <table class="table table-sm table-hover table-condensed">
                                    <tr>
                                        <td style="text-align:left;"><button class="btn btn-danger" onclick="agregarHD()"><i class="fa fa-plus"></i> Agregar</button></td>
                                    </tr>
                                </table>
                            <?php
                                        }
                            ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-12 col-lg-12">
                        <br><br><br>
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="nav-icon fa fa-book"></i> Registro de Plataformas</h3>
                            </div>
                            <div class="card-body">
                                <div id="no-more-tables">
                                    <table class="table table-sm table-hover table-condensed" id="tblPlataforma">
                                        <thead class="cf">
                                            <tr style="background-color: #007bff; color:white;">
                                                <th style="text-align:center; vertical-align:middle;">No.</th>
                                                <th style="text-align:center; vertical-align:middle;">Nombre</th>
                                                <th style="text-align:center; vertical-align:middle;">Usuario</th>
                                                <th style="text-align:center; vertical-align:middle;">Operador</th>
                                                <th colspan="2">&nbsp;</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (isset($rTMP["value"]["PLATAFORMAS"])) {
                                                $intCountPl = 0;
                                                reset($rTMP["value"]["PLATAFORMAS"]);
                                                while ($rTMP4 = each($rTMP["value"]["PLATAFORMAS"])) {
                                                    $intCountPl++;
                                                    $intID = $rTMP4["key"];
                                                    $intIDComponente = intval($rTMP4["value"]["ID_COMPONENTE"]);
                                                    $strNombrePl = isset($rTMP4["value"]["NOMBRE_COMPONENTE"]) ? $rTMP4["value"]["NOMBRE_COMPONENTE"] : "N/A";
                                                    $strUsuarioPl = isset($rTMP4["value"]["USUARIO"]) ? $rTMP4["value"]["USUARIO"] : "N/A";
                                                    $strOperadorPl = isset($rTMP4["value"]["OPERADOR"]) ? $rTMP4["value"]["OPERADOR"] : "N/A";
                                            ?>
                                                    <tr id="trPlataforma_<?php print $intID; ?>">
                                                        <td data-title="No." style="text-align:center; vertical-align:middle;">
                                                            <h3><span class="badge badge-primary"><?php print $intCountPl; ?></span></h3>
                                                            <input id="hdnIdPL_<?php print $intID; ?>" name="hdnIdPL_<?php print $intID; ?>" type="hidden" value="N">
                                                        </td>
                                                        <td data-title="Nombre" style="text-align:center; vertical-align:middle;">
                                                            <div id="divShowPLNombre_<?php print $intID; ?>">
                                                                <?php print $strNombrePl; ?>
                                                            </div>
                                                            <div id="divEditPLNombre_<?php print $intID; ?>" style="display:none;">
                                                                <?php $this->drawSelectComponentePL($intID, $intIDComponente); ?>
                                                            </div>
                                                        </td>
                                                        <td data-title="Usuario" style="text-align:center; vertical-align:middle;">
                                                            <div id="divShowPLUsuario_<?php print $intID; ?>">
                                                                <?php print $strUsuarioPl; ?>
                                                            </div>
                                                            <div id="divEditPLUsuario_<?php print $intID; ?>" style="display:none;">
                                                                <input class="form-control" type="text" id="txtPLUsuario_<?php print $intID; ?>" name="txtPLUsuario_<?php print $intID; ?>" value="<?php print $strUsuarioPl; ?>">
                                                            </div>
                                                        </td>
                                                        <td data-title="Operador" style="text-align:center; vertical-align:middle;">
                                                            <div id="divShowPLOperador_<?php print $intID; ?>">
                                                                <?php print $strOperadorPl; ?>
                                                            </div>
                                                            <div id="divEditPLOperador_<?php print $intID; ?>" style="display:none;">
                                                                <input class="form-control" type="text" id="txtPLOperador_<?php print $intID; ?>" name="txtPLOperador_<?php print $intID; ?>" value="<?php print $strOperadorPl; ?>">
                                                            </div>
                                                        </td>
                                                        <td style="text-align:center;">
                                                            <button class="btn btn-secondary btn-block" onclick="editPL('<?php print $intID; ?>')"><i class="fa fa-pencil"></i> Editar</button>
                                                            <button class="btn btn-secondary btn-block" onclick="deletePL('<?php print $intID; ?>')"><i class="fa fa-trash"></i> Eliminar</button>
                                                        </td>
                                                    </tr>
                                                <?php
                                                }
                                                ?>
                                        </tbody>
                                    </table>
                                    <table class="table table-sm table-hover table-condensed">
                                        <tr>
                                            <td style="text-align:left;"><button class="btn btn-primary" onclick="agregarPL()"><i class="fa fa-plus"></i> Agregar</button></td>
                                        </tr>
                                    </table>
                                <?php
                                            } else {
                                ?>
                                    </tbody>
                                    </table>
                                    <table class="table table-sm table-hover table-condensed">
                                        <tr>
                                            <td style="text-align:left;"><button class="btn btn-primary" onclick="agregarPL()"><i class="fa fa-plus"></i> Agregar</button></td>
                                        </tr>
                                    </table>
                                </div>
                            <?php
                                            }
                            ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-12 col-lg-12">
                        <br><br><br>
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title"><i class="nav-icon fa fa-book"></i> Registro de Software</h3>
                            </div>
                            <div class="card-body">
                                <div id="no-more-tables">
                                    <table class="table table-sm table-hover table-condensed" id="tblSoftware">
                                        <thead class="cf">
                                            <tr style="background-color: #17a2b8; color:white;">
                                                <th style="text-align:center; vertical-align:middle;">No.</th>
                                                <th style="text-align:center; vertical-align:middle;">Nombre</th>
                                                <th style="text-align:center; vertical-align:middle;">Con licencia</th>
                                                <th style="text-align:center; vertical-align:middle;">Version</th>
                                                <th colspan="2">&nbsp;</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (isset($rTMP["value"]["DETALLE_SOFTWARE"])) {
                                                $intCountSW = 0;
                                                reset($rTMP["value"]["DETALLE_SOFTWARE"]);
                                                while ($rTMP3 = each($rTMP["value"]["DETALLE_SOFTWARE"])) {
                                                    $intCountSW++;
                                                    $intID = $rTMP3["key"];
                                                    $intIDComponente = intval($rTMP3["value"]["ID_COMPONENTE"]);
                                                    $strNombreSW = $rTMP3["value"]["NOMBRE_COMPONENTE"];
                                                    $intPago = $rTMP3["value"]["PAGO"];
                                                    $strPago = ($intPago == 1) ? "Si" : "No";
                                                    $strVersion = $rTMP3["value"]["VERSION"];
                                            ?>
                                                    <tr id="trSoftware_<?php print $intID; ?>">
                                                        <td data-title="No." style="text-align:center; vertical-align:middle;">
                                                            <h3><span class="badge badge-info"><?php print $intCountSW; ?></span></h3>
                                                            <input id="hdnIdSW_<?php print $intID; ?>" name="hdnIdSW_<?php print $intID; ?>" type="hidden" value="N">
                                                        </td>
                                                        <td data-title="Nombre" style="text-align:center; vertical-align:middle;">
                                                            <div id="divShowSWNombre_<?php print $intID; ?>">
                                                                <?php print $strNombreSW; ?>
                                                            </div>
                                                            <div id="divEditSWNombre_<?php print $intID; ?>" style="display:none;">
                                                                <?php $this->drawSelectComponenteSW($intID, $intIDComponente); ?>
                                                            </div>
                                                        </td>
                                                        <td data-title="Con licencia" style="text-align:center;vertical-align:middle; ">
                                                            <div id="divShowSWPago_<?php print $intID; ?>">
                                                                <?php print $strPago; ?>
                                                            </div>
                                                            <div id="divEditSWPago_<?php print $intID; ?>" style="display:none;">
                                                                <?php $this->drawSelectSwPago($intID, $intPago); ?>
                                                            </div>
                                                        </td>
                                                        <td data-title="Versión" style="text-align:center;vertical-align:middle;">
                                                            <div id="divShowSWVersion_<?php print $intID; ?>">
                                                                <?php print $strVersion; ?>
                                                            </div>
                                                            <div id="divEditSWVersion_<?php print $intID; ?>" style="display:none;">
                                                                <input class="form-control" type="text" id="txtSWVersion_<?php print $intID; ?>" name="txtSWVersion_<?php print $intID; ?>" value="<?php print $strVersion; ?>">
                                                            </div>
                                                        </td>
                                                        <td style="text-align:center; vertical-align:middle;">
                                                            <button class="btn btn-secondary btn-block" onclick="editSW('<?php print $intID; ?>')"><i class="fa fa-pencil"></i> Editar</button>
                                                            <button class="btn btn-secondary btn-block" onclick="deleteSW('<?php print $intID; ?>')"><i class="fa fa-trash"></i> Eliminar</button>
                                                        </td>
                                                    </tr>
                                                <?php
                                                }
                                                ?>
                                        </tbody>
                                    </table>
                                    <table class="table table-sm table-hover table-condensed">
                                        <tr>
                                            <td style="text-align:left;">
                                                <button class="btn btn-info" onclick="agregarSW()"><i class="fa fa-plus"></i> Agregar</button>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            <?php
                                            } else {
                            ?>
                                </tbody>
                                </table>
                                <table class="table table-sm table-hover table-condensed">
                                    <tr>
                                        <td style="text-align:left;"><button class="btn btn-info" onclick="agregarSW()">(+) Agregar</button></td>
                                    </tr>
                                </table>
                            </div>
                        <?php
                                            }
                        ?>
                        <br><br><br>
                        <table class="table" id="tblComentarios">
                            <tr>
                                <td>
                                    <h2>Observaciones:</h2>
                                </td>
                            </tr>
                            <tr>
                                <?php
                                if (isset($rTMP["value"]["COMENTARIO"])) {
                                    reset($rTMP["value"]["COMENTARIO"]);
                                    while ($rTMP6 = each($rTMP["value"]["COMENTARIO"])) {
                                        $strComentario = $rTMP6["value"]["COMENTARIO"];
                                ?>
                                        <td><textarea class="form-control" name="txtComentarios"><?php print $strComentario; ?></textarea></td>
                                    <?php
                                    }
                                } else {
                                    ?>
                                    <td><textarea class="form-control" name="txtComentarios"></textarea></td>
                                <?php
                                }
                                ?>
                            </tr>
                        </table>

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <button class="btn btn-info btn-block" onclick="checkForm()"><i class="fa fa-save"></i> Guardar Cambios</button>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <button class="btn btn-info btn-block" onclick="fntExportarData('PDF')"><i class="fa fa-print"></i> Imprimir Expediente</button>
                        </div>
                    </div>
            <?php
            }
        }
    }

    public function drawSelectComponenteHD($intId = 0, $intHD = 0)
    {
        $arrComponenteHD = $this->objModel->getComponenteHD();
            ?>
            <select id="selectComponenteHD_<?php print $intId; ?>" name="selectComponenteHD_<?php print $intId; ?>" style="text-align: center;" class="form-control">
                <?php
                reset($arrComponenteHD);
                while ($rTMP = each($arrComponenteHD)) {
                    $strSelected = (($rTMP["key"] == $intHD)) ? 'selected' : '';
                ?>
                    <option value="<?php print $rTMP["key"]; ?>" <?php print $strSelected; ?>><?php print $rTMP["value"]["NOMBRE"]; ?></option>
                <?php
                }
                ?>
            </select>
        <?php
    }

    public function drawSelectComponenteSW($intId = 0, $intSW = 0)
    {
        $arrComponenteSW = $this->objModel->getComponenteSW();
        ?>
            <select id="selectComponenteSW_<?php print $intId; ?>" name="selectComponenteSW_<?php print $intId; ?>" style="text-align: center;" class="form-control">
                <?php
                reset($arrComponenteSW);
                while ($rTMP = each($arrComponenteSW)) {
                    $strSelected = (($rTMP["key"] == $intSW)) ? 'selected' : '';
                ?>
                    <option value="<?php print $rTMP["key"]; ?>" <?php print $strSelected; ?>><?php print $rTMP["value"]["NOMBRE"]; ?></option>
                <?php
                }
                ?>
            </select>
        <?php
    }

    public function drawSelectComponentePL($intId = 0, $intPL = 0)
    {
        $arrComponentePL = $this->objModel->getComponentePL();
        ?>
            <select id="selectComponentePL_<?php print $intId; ?>" name="selectComponentePL_<?php print $intId; ?>" style="text-align: center;" class="form-control">
                <?php
                reset($arrComponentePL);
                while ($rTMP = each($arrComponentePL)) {
                    $strSelected = (($rTMP["key"] == $intPL)) ? 'selected' : '';
                ?>
                    <option value="<?php print $rTMP["key"]; ?>" <?php print $strSelected; ?>><?php print $rTMP["value"]["NOMBRE"]; ?></option>
                <?php
                }
                ?>
            </select>
        <?php
    }

    public function drawSelectSwPago($intId = 0, $intActivo = 0)
    {
        ?>
            <select id="selectPago_<?php print $intId; ?>" name="selectPago_<?php print $intId; ?>" style="text-align: center;" class="form-control">
                <option value="0" <?php print ($intActivo == 0) ? "selected" : ""; ?>>No</option>
                <option value="1" <?php print ($intActivo == 1) ? "selected" : ""; ?>>Si</option>
            </select>
        <?php
    }

    public function drawContent()
    {
        ?>
            <!DOCTYPE html>
            <html>

            <head>
                <meta charset="utf-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <title>Inventario IT</title>
                <!-- Tell the browser to be responsive to screen width -->
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <!-- Theme style -->
                <link rel="stylesheet" href="dist/css/adminlte.min.css">
                <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
                <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
                <!-- iCheck -->
                <link rel="stylesheet" href="plugins/iCheck/flat/blue.css">
                <!-- Morris chart -->
                <link rel="stylesheet" href="plugins/morris/morris.css">
                <!-- jvectormap -->
                <link rel="stylesheet" href="plugins/jvectormap/jquery-jvectormap-1.2.2.css">
                <!-- Date Picker -->
                <link rel="stylesheet" href="plugins/datepicker/datepicker3.css">
                <!-- Daterange picker -->
                <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker-bs3.css">
                <!-- bootstrap wysihtml5 - text editor -->
                <link rel="stylesheet" href="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
                <!-- Google Font: Source Sans Pro -->
                <link href="dist/css/fontgoogleapiscss.css" rel="stylesheet">
                <style>
                    .centrar {
                        position: absolute;
                        /*nos posicionamos en el centro del navegador*/
                        top: 50%;
                        left: 50%;
                        float: none;
                        /*determinamos una anchura*/
                        width: 400px;
                        /*indicamos que el margen izquierdo, es la mitad de la anchura*/
                        margin-left: -130px;
                        /*determinamos una altura*/
                        height: 300px;
                        /*indicamos que el margen superior, es la mitad de la altura*/
                        margin-top: -150px;
                        padding: 5px;
                        z-index: 1;
                    }

                    @media only screen and (max-width: 800px) {

                        /* Force table to not be like tables anymore */
                        #no-more-tables table,
                        #no-more-tables thead,
                        #no-more-tables tbody,
                        #no-more-tables th,
                        #no-more-tables td,
                        #no-more-tables tr {
                            display: block;
                        }

                        /* Hide table headers (but not display: none;, for accessibility) */
                        #no-more-tables thead tr {
                            position: absolute;
                            top: -9999px;
                            left: -9999px;
                        }

                        #no-more-tables tr {
                            border: 1px solid #ccc;
                        }

                        #no-more-tables td {
                            /* Behave like a "row" */
                            border: none;
                            border-bottom: 1px solid #eee;
                            position: relative;
                            padding-left: 50%;
                            white-space: normal;
                            text-align: left;
                        }

                        #no-more-tables td:before {
                            /* Now like a table header */
                            position: absolute;
                            /* Top/left values mimic padding */
                            top: 6px;
                            left: 6px;
                            width: 45%;
                            padding-right: 10px;
                            white-space: nowrap;
                            text-align: left;
                            font-weight: bold;
                        }

                        /*
                    Label the data
                    */
                        #no-more-tables td:before {
                            content: attr(data-title);
                        }
                    }
                </style>
            </head>

            <body class="hold-transition sidebar-mini">
                <div class="wrapper">

                    <!-- Navbar -->
                    <nav class="main-header navbar navbar-expand bg-white navbar-light border-bottom">

                        <!-- Right navbar links -->
                        <ul class="navbar-nav">
                            <!-- Notifications Dropdown Menu -->
                            <li class="nav-item">
                                <a class="nav-link" data-widget="pushmenu" href="#"><i class="fa fa-bars"></i></a>
                            </li>
                        </ul>
                    </nav>
                    <!-- /.navbar -->

                    <!-- Main Sidebar Container -->
                    <aside class="main-sidebar sidebar-dark-primary elevation-4">
                        <!-- Brand Logo -->
                        <a href="menu.php" class="brand-link">
                            <span class="brand-text font-weight-light">Inventario IT</span>
                        </a>

                        <!-- Sidebar -->
                        <div class="sidebar">
                            <!-- Sidebar user panel (optional) -->
                            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                                <div class="image">
                                    <img src="images/user.png" class="img-circle elevation-2">
                                </div>
                                <div class="info">
                                    <a href="#" class="d-block"><b><?php print $this->arrRolUser["NAME"]; ?></b></a>
                                </div>
                            </div>

                            <?php draMenu("expediente.php", 1); ?>
                        </div>
                        <!-- /.sidebar -->
                    </aside>

                    <!-- Content Wrapper. Contains page content -->
                    <div class="content-wrapper">
                        <!-- Content Header (Page header) -->
                        <div class="content-header">
                            <div class="container-fluid">
                                <div class="row mb-2">
                                    <div class="col-sm-6">
                                    </div><!-- /.col -->
                                </div><!-- /.row -->
                            </div><!-- /.container-fluid -->
                        </div>
                        <!-- /.content-header -->

                        <!-- Main content -->
                        <section class="content">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-xs-12 col-md-12 col-sm-12 col-lg-12">
                                        <form id="frmFiltros" method="post"></form>
                                        <div class="card">
                                            <div class="card-header" style="text-align:center; padding: 10px;">
                                                <h2>Gestión de Expedientes</h2>
                                            </div>
                                            <!-- /.card-header -->
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <?php $this->drawSelectArea(); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group" id="divSelectColaborador">
                                                            <?php $this->drawSelectColaborador(); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <br>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <button class="btn btn-info btn-block" id="btnSearchExp" onclick="getExpediente()"><i class="fa fa-search"></i> Buscar Expediente</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- /.card-body -->
                                            <div class="card-footer clearfix"></div>
                                        </div>
                                        <div id="divShowLoadingGeneralBig" style="display:none;" class='centrar'><img src="images/load.gif" height="250px" width="250px"></div>
                                        <div class="row" id="divContentExpediente">
                                            <?php $this->drawContentExpediente(); ?>
                                        </div>
                                        <br><br><br>
                                        <!-- /.card -->
                                    </div>
                                </div>
                            </div>
                        </section>
                        <!-- /.content -->
                    </div>
                    <!-- /.content-wrapper -->
                    <footer class="main-footer">
                        <strong>Copyright <?php print date("Y"); ?></strong>
                        <div class="float-right d-none d-sm-inline-block">
                            <b>Version</b> 1.0
                        </div>
                    </footer>

                    <!-- Control Sidebar -->
                    <aside class="control-sidebar control-sidebar-dark">
                        <!-- Control sidebar content goes here -->
                    </aside>
                    <!-- /.control-sidebar -->
                </div>
                <!-- ./wrapper -->

                <!-- jQuery -->
                <script src="plugins/jquery/jquery.min.js"></script>
                <!-- jQuery UI 1.11.4 -->
                <script src="dist/js/jquery-ui.min.js"></script>
                <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
                <script>
                    $.widget.bridge('uibutton', $.ui.button)
                </script>
                <!-- Bootstrap 4 -->
                <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
                <!-- Morris.js charts -->
                <script src="dist/js/raphael-min.js"></script>
                <script src="plugins/morris/morris.min.js"></script>
                <!-- Sparkline -->
                <script src="plugins/sparkline/jquery.sparkline.min.js"></script>
                <!-- jvectormap -->
                <script src="plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
                <script src="plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
                <!-- jQuery Knob Chart -->
                <script src="plugins/knob/jquery.knob.js"></script>
                <!-- daterangepicker -->
                <script src="dist/js/moment.min.js"></script>
                <script src="plugins/daterangepicker/daterangepicker.js"></script>
                <!-- datepicker -->
                <script src="plugins/datepicker/bootstrap-datepicker.js"></script>
                <!-- Bootstrap WYSIHTML5 -->
                <script src="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
                <!-- Slimscroll -->
                <script src="plugins/slimScroll/jquery.slimscroll.min.js"></script>
                <!-- FastClick -->
                <script src="plugins/fastclick/fastclick.js"></script>
                <!-- AdminLTE App -->
                <script src="dist/js/adminlte.js"></script>
                <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
                <script src="dist/js/pages/dashboard.js"></script>
                <!-- AdminLTE for demo purposes -->
                <script src="dist/js/demo.js"></script>
                <script>
                    function destroSession() {
                        if (confirm("¿Desea salir de la aplicación?")) {
                            $.ajax({
                                url: "colaborador.php",
                                data: {
                                    destroSession: true
                                },
                                type: "post",
                                dataType: "json",
                                success: function(data) {
                                    if (data.Correcto == "Y") {
                                        alert("Usted ha cerrado sesión");
                                        location.href = "index.php";
                                    }
                                }
                            });
                        }
                    }

                    function getExpediente() {
                        var intColaborador = $("#selectColaboradores").val();
                        if (intColaborador > 0) {
                            $("#selectColaboradores").css('background-color', '');
                            $.ajax({
                                url: "expediente.php",
                                data: {
                                    loadExpediente: true,
                                    intColaborador: intColaborador
                                },
                                type: "post",
                                dataType: "html",
                                beforeSend: function() {
                                    $("#btnSearchExp").prop('disabled', true);
                                    $("#divShowLoadingGeneralBig").css("z-index", 1050);
                                    $("#divShowLoadingGeneralBig").show();
                                },
                                success: function(data) {
                                    $("#divShowLoadingGeneralBig").hide();
                                    $("#divContentExpediente").html(data);
                                    $("#btnSearchExp").prop('disabled', false);
                                }
                            });
                        } else {
                            $("#selectColaboradores").css('background-color', '#88d7e3');
                        }

                    }

                    function loadColaboradores() {
                        var intArea = $("#selectArea").val();
                        if (intArea > 0) {
                            $.ajax({
                                url: "expediente.php",
                                data: {
                                    loadColaboradores: true,
                                    intArea: intArea
                                },
                                type: "post",
                                dataType: "html",
                                beforeSend: function() {
                                    $("#divShowLoadingGeneralBig").show();
                                },
                                success: function(data) {
                                    $("#divShowLoadingGeneralBig").hide();
                                    $("#divSelectColaborador").html(data);
                                    $("#divContentExpediente").html('');
                                }
                            });
                        }
                    }

                    /* HARDWARE */

                    function editHD(id) {
                        $("#divEditHDNombre_" + id).show();
                        $("#divShowHDNombre_" + id).hide();

                        $("#divEditHDMarca_" + id).show();
                        $("#divShowHDMarca_" + id).hide();

                        $("#divEditHDValor_" + id).show();
                        $("#divShowHDValor_" + id).hide();

                        $("#divEditHDSerie_" + id).show();
                        $("#divShowHDSerie_" + id).hide();

                        $("#divEditHDModelo_" + id).show();
                        $("#divShowHDModelo_" + id).hide();

                        $("#divEditHDLinea_" + id).show();
                        $("#divShowHDLinea_" + id).hide();

                        $("#divEditHDAnio_" + id).show();
                        $("#divShowHDAnio_" + id).hide();

                        $("#hdnIdHW_" + id).val("E");
                    }

                    function deleteHD(id) {
                        $("#trHardware_" + id).css('background-color', '#f4d0de');
                        $("#hdnIdHW_" + id).val("D");
                    }

                    function fntGetCountHD() {
                        var intCount = 0;
                        $("input[name*='txtHDMarca_']").each(function() {
                            intCount++;
                        });
                        return intCount;
                    }

                    function fntGetCountMaxHD() {
                        var valores = [];
                        var intCount = 0;
                        $("input[name*='txtHDMarca_']").each(function() {
                            var arrSplit = $(this).attr("id").split("_");
                            valores.push(arrSplit[1]);
                        });
                        var max = parseInt(Math.max.apply(null, valores));
                        if (isNaN(max)) {
                            max = fntGetCountHD();
                        }
                        return max + 1;
                    }

                    var intFilasHD = 0;

                    function agregarHD() {
                        intFilasHD = fntGetCountHD();
                        intFilasHD++;

                        max = fntGetCountMaxHD();

                        var $tabla = $("#tblHardware");
                        var $tr = $("<tr></tr>");
                        // creamos la columna o td
                        var $td = $("<td data-title='No.' style='text-align:center;'><b>" + intFilasHD + "<b><input class='form-control' type='hidden' id='hdnIdHW_" + max + "' name='hdnIdHW_" + max + "' value='A'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Nombre' style='text-align:center;'><select class='form-control' id='selectComponenteHD_" + max + "' name='selectComponenteHD_" + max + "' style='text-align: center;'><?php $arrComponenteHD = $this->objModel->getComponenteHD();
                                                                                                                                                                                                                            reset($arrComponenteHD);
                                                                                                                                                                                                                            while ($rTMP = each($arrComponenteHD)) { ?><option value='<?php print $rTMP["key"]; ?>'><?php print $rTMP["value"]["NOMBRE"]; ?></option><?php } ?></select></td>");
                        $tr.append($td);

                        var $td = $("<td data-title='Marca' style='text-align:center;'><input class='form-control' type='text' id='txtHDMarca_" + max + "' name='txtHDMarca_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='No. Activo' style='text-align:center;'><input class='form-control' type='text' id='txtHDValor_" + max + "' name='txtHDValor_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Serie' style='text-align:center;'><input class='form-control' type='text' id='txtHDSerie_" + max + "' name='txtHDSerie_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Modelo' style='text-align:center;'><input class='form-control' type='text' id='txtHDModelo_" + max + "' name='txtHDModelo_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Linea' style='text-align:center;'><input class='form-control' type='text' id='txtHDLinea_" + max + "' name='txtHDLinea_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Año' style='text-align:center;'><input class='form-control' type='number' id='txtHDAnio_" + max + "' name='txtHDAnio_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td style='text-align:center; display:none;'></td>");
                        $tr.append($td);
                        var $td = $("<td style='text-align:center; display:none;'></td>");
                        $tr.append($td);

                        $tabla.append($tr);
                    }

                    /* PLATAFORMA */
                    function editPL(id) {
                        $("#divEditPLNombre_" + id).show();
                        $("#divShowPLNombre_" + id).hide();

                        $("#divEditPLUsuario_" + id).show();
                        $("#divShowPLUsuario_" + id).hide();

                        $("#divEditPLOperador_" + id).show();
                        $("#divShowPLOperador_" + id).hide();

                        $("#hdnIdPL_" + id).val("E");
                    }

                    function deletePL(id) {
                        $("#trPlataforma_" + id).css('background-color', '#f4d0de');
                        $("#hdnIdPL_" + id).val("D");
                    }


                    function fntGetCountPL() {
                        var intCount = 0;
                        $("input[name*='txtPLUsuario_']").each(function() {
                            intCount++;
                        });
                        return intCount;
                    }

                    function fntGetCountMaxPL() {
                        var valores = [];
                        var intCount = 0;
                        $("input[name*='txtPLUsuario_']").each(function() {
                            var arrSplit = $(this).attr("id").split("_");
                            valores.push(arrSplit[1]);
                        });
                        var max = parseInt(Math.max.apply(null, valores));
                        if (isNaN(max)) {
                            max = fntGetCountPL();
                        }
                        return max + 1;
                    }

                    var intFilasPL = 0;

                    function agregarPL() {
                        intFilasPL = fntGetCountPL();
                        intFilasPL++;

                        max = fntGetCountMaxPL();

                        var $tabla = $("#tblPlataforma");
                        var $tr = $("<tr></tr>");
                        // creamos la columna o td
                        var $td = $("<td data-title='No.' style='text-align:center;'><b>" + intFilasPL + "<b><input class='form-control' type='hidden' id='hdnIdPL_" + max + "' name='hdnIdPL_" + max + "' value='A'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Nombre' style='text-align:center;'><select class='form-control' id='selectComponentePL_" + max + "' name='selectComponentePL_" + max + "' style='text-align: center;'><?php $arrComponentePL = $this->objModel->getComponentePL();
                                                                                                                                                                                                                            reset($arrComponentePL);
                                                                                                                                                                                                                            while ($rTMP = each($arrComponentePL)) { ?><option value='<?php print $rTMP["key"]; ?>'><?php print $rTMP["value"]["NOMBRE"]; ?></option><?php } ?></select></td>");
                        $tr.append($td);

                        var $td = $("<td data-title='Usuario' style='text-align:center;'><input class='form-control' type='text' id='txtPLUsuario_" + max + "' name='txtPLUsuario_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Operador' style='text-align:center;'><input class='form-control' type='text' id='txtPLOperador_" + max + "' name='txtPLOperador_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td style='text-align:center; display:none;'></td>");
                        $tr.append($td);
                        var $td = $("<td style='text-align:center; display:none;'></td>");
                        $tr.append($td);

                        $tabla.append($tr);
                    }

                    /* SOFTWARE */

                    function editSW(id) {
                        $("#divEditSWNombre_" + id).show();
                        $("#divShowSWNombre_" + id).hide();

                        $("#divEditSWPago_" + id).show();
                        $("#divShowSWPago_" + id).hide();

                        $("#divEditSWVersion_" + id).show();
                        $("#divShowSWVersion_" + id).hide();

                        $("#hdnIdSW_" + id).val("E");
                    }

                    function deleteSW(id) {
                        $("#trSoftware_" + id).css('background-color', '#f4d0de');
                        $("#hdnIdSW_" + id).val("D");
                    }


                    function fntGetCountSW() {
                        var intCount = 0;
                        $("input[name*='txtSWVersion_']").each(function() {
                            intCount++;
                        });
                        return intCount;
                    }

                    function fntGetCountMaxSW() {
                        var valores = [];
                        var intCount = 0;
                        $("input[name*='txtSWVersion_']").each(function() {
                            var arrSplit = $(this).attr("id").split("_");
                            valores.push(arrSplit[1]);
                        });
                        var max = parseInt(Math.max.apply(null, valores));
                        if (isNaN(max)) {
                            max = fntGetCountSW();
                        }
                        return max + 1;
                    }

                    var intFilasSW = 0;

                    function agregarSW() {
                        intFilasSW = fntGetCountSW();
                        intFilasSW++;

                        max = fntGetCountMaxSW();

                        var $tabla = $("#tblSoftware");
                        var $tr = $("<tr></tr>");
                        // creamos la columna o td
                        var $td = $("<td data-title='No.' style='text-align:center;'><b>" + intFilasSW + "<b><input class='form-control' type='hidden' id='hdnIdSW_" + max + "' name='hdnIdSW_" + max + "' value='A'></td>")
                        $tr.append($td);

                        var $td = $("<td data-title='Nombre' style='text-align:center;'><select class='form-control' id='selectComponenteSW_" + max + "' name='selectComponenteSW_" + max + "' style='text-align: center;'><?php $arrComponenteSW = $this->objModel->getComponenteSW();
                                                                                                                                                                                                                            reset($arrComponenteSW);
                                                                                                                                                                                                                            while ($rTMP = each($arrComponenteSW)) { ?><option value='<?php print $rTMP["key"]; ?>'><?php print $rTMP["value"]["NOMBRE"]; ?></option><?php } ?></select></td>");
                        $tr.append($td);

                        var $td = $("<td data-title='Pago' style='text-align:center;'><select class='form-control' id='selectPago_" + max + "' name='selectPago_" + max + "' style='text-align: center;'><option value='0'>No</option><option value='1'>Si</option></select></td>");
                        $tr.append($td);

                        var $td = $("<td data-title='Version' style='text-align:center;'><input class='form-control' type='text' id='txtSWVersion_" + max + "' name='txtSWVersion_" + max + "'></td>")
                        $tr.append($td);

                        var $td = $("<td style='text-align:center; display:none;'></td>");
                        $tr.append($td);
                        var $td = $("<td style='text-align:center; display:none;'></td>");
                        $tr.append($td);

                        $tabla.append($tr);
                    }

                    function checkForm() {
                        var boolError = false;

                        //Hardware
                        $("input[name*='txtHDMarca_']").each(function() {
                            if ($(this).val() == '') {
                                $(this).css('background-color', '#f4d0de');
                                boolError = true;
                            } else {
                                $(this).css('background-color', '');
                            }
                        });

                        $("input[name*='txtHDValor_']").each(function() {
                            if ($(this).val() == '') {
                                $(this).css('background-color', '#f4d0de');
                                boolError = true;
                            } else {
                                $(this).css('background-color', '');
                            }
                        });

                        if (boolError == false) {
                            var objSerialized = $("#tblDatosPersonales, #tblHardware, #tblSoftware, #tblComentarios, #tblDatosEquipo, #tblPlataforma").find("select, input, textarea").serialize();
                            $.ajax({
                                url: "expediente.php",
                                data: objSerialized,
                                type: "POST",
                                beforeSend: function() {
                                    $("#divShowLoadingGeneralBig").show();
                                },
                                success: function(data) {
                                    $("#divShowLoadingGeneralBig").hide();
                                    location.href = "expediente.php";
                                }
                            });
                        } else {
                            alert('Faltan campos por llenar y/o revisar que los campos no contengan caracteres extraños');
                        }
                    }

                    /*
                        Funcion que permite enviar en POST parametros que no pertenecen a un objeto
                        del arbol DOM y en forma de AJAX se procesan para poder exportar el reporte
                        */
                    function addHidden(theForm, key, value) {
                        // Create a hidden input element, and append it to the form:
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        'name-as-seen-at-the-server';
                        input.value = value;
                        theForm.appendChild(input);
                    }

                    //Permite enviar la peticion para poder exportar el reporte en PDF o EXCEL
                    function fntExportarData(strTipoExportar) {
                        var intColaborador = $("#selectColaboradores").val();
                        var objForm = document.getElementById("frmFiltros");

                        objForm.target = "_self";
                        addHidden(objForm, 'hdnExportar', 'true');
                        addHidden(objForm, 'TipoExport', strTipoExportar);
                        addHidden(objForm, 'intColaborador', intColaborador);
                        objForm.submit();
                    }

                    $(document).ready(function() {
                        loadColaboradores();
                    });
                </script>
            </body>

            </html>
    <?php
    }
}
    ?>