<?php
require_once("core/core.php");
error_reporting(E_ALL);
ini_set('display_errors',0);
session_start();
if ( isset($_SESSION['user_id']) ) {
  $strRolUserSession = getRolUserSession($_SESSION['user_id']);
  $strPuestoUserSession = getPuestoUserSession($_SESSION['user_id']);
  $intIDUserSession = getIDUserSession($_SESSION['user_id']);

  if( $strRolUserSession != '' ){
    $arrRolUser["ID"] = $intIDUserSession;
    $arrRolUser["NAME"] = $_SESSION['user_id'];
    $arrRolUser["PUESTO"] = $strPuestoUserSession;

    if( $strRolUserSession == "master" ){
      $arrRolUser["MASTER"] = true;
    }elseif( $strRolUserSession == "normal" ){
      $arrRolUser["NORMAL"] = true;
    }
  }
}else{
  header("Location: index.php");
}

$objController = new rptstock_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class rptstock_controller{

    private $objModel;
    private $objView;
    private $arrRolUser;

	public function __construct($arrRolUser){
		$this->objModel = new rptstock_model();
        $this->objView = new rptstock_view($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContentController(){
        $this->objView->drawContent(); 
    }

    public function runAjax(){
        $this->exportPDF();
        $this->ajaxLoadEstadisticas();
        $this->ajaxLoadModalDetail();
        $this->ajaxDestroySession();
    }

    public function ajaxLoadEstadisticas(){
        if( isset($_POST["getReporte"]) && $_POST["getReporte"] == "true" ){
            $intAreas = isset($_POST["intArea"])? $_POST["intArea"]:0;
            $intComponente = isset($_POST["intComponente"])? intval($_POST["intComponente"]):0;
            print $this->objView->drawContentReport($intComponente, $intAreas);
            exit();
        }
    }

    public function ajaxLoadModalDetail(){
        if( isset($_POST["getDetail"]) && $_POST["getDetail"] == "true" ){
            $intArea = isset($_POST["intArea"])? intval($_POST["intArea"]):0;
            $intComponente = isset($_POST["intComponente"])? intval($_POST["intComponente"]):0;
            $arrDetalle = $this->objModel->getDetalle($intArea, $intComponente);
            print $this->objView->drawContentModalDetail($arrDetalle);
            exit();
        }
    }

    public function ajaxDestroySession(){
        if( isset($_POST["destroSession"]) ){
          header("Content-Type: application/json;");
          session_destroy();
          $arrReturn["Correcto"] = "Y";
          print json_encode($arrReturn);
          exit();
        }
    }
    
    //Funcion que permite exportar a formato PDF o EXCEL el contenido del reporte
    public function exportPDF(){
        if( isset($_POST['GenerarReporte']) ){
            header('Content-Type: text/html; charset=utf-8');
            require_once("tcpdf/tcpdf.php");

            $strTipoExportar = isset($_POST["TipoExport"])? $_POST["TipoExport"] :'';
            $intComponente = isset($_POST["intComponente"])? intval($_POST["intComponente"]):0;
            $intAreas = isset($_POST["selectArea"])? $_POST["selectArea"]:0;
            $strNombreArchivo= "ReporteIT_Stock_".date("Y")."_".date("m")."_".date("d");
            $strHTML = $this->objView->drawExportReport($intComponente, $intAreas);

            if( $strTipoExportar == 'PDF' ){
                ob_start();
                    $pdf = new TCPDF("P", "mm", "LETTER", false, 'UTF-8', false);
                    $pdf->AddPage('P');
                    $pdf->SetFont('helvetica', '', 7);
                    $pdf->writeHTML($strHTML, true, false, true, false, "C");
                ob_end_clean();
                $pdf->Output($strNombreArchivo.'.pdf', 'D');
            }
            
            exit();
        }
    }

}

class rptstock_model{

    public function getArea(){
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
        if( !empty($result) ){
          while($row = mysqli_fetch_assoc($result)) {
            $arrArea[$row["id"]]["NOMBRE"] = $row["nombre"];
          }
        }
    
        mysqli_close($conn);
        return $arrArea;
    }

    public function getComponenteHD(){
        $conn = getConexion();
        $arrComponenteHD = array();
        $strQuery = "SELECT id, nombre FROM componente WHERE categoria = 1 ORDER BY nombre";
        $result = mysqli_query($conn, $strQuery);
        if( !empty($result) ){
          while($row = mysqli_fetch_assoc($result)) {
            $arrComponenteHD[$row["id"]]["NOMBRE"] = $row["nombre"];
          }
        }
    
        mysqli_close($conn);
        return $arrComponenteHD;
    }

    public function getDetalle($intArea, $intComponente){
        if( $intArea>0 && $intComponente>0 ){
            $conn = getConexion();
            $arrDetalle = array();
            $strQuery = "SELECT colaborador.id,
                                colaborador.cif,
                                CONCAT(colaborador.nombres,' ', colaborador.apellidos) nombrecolaborador, 
                                colaborador.puesto,
                                COUNT(expediente_detail.id) conteo
                           FROM expediente_detail 
                                INNER JOIN expedientes 
                                        ON expediente_detail.expediente = expedientes.id 
                                INNER JOIN colaborador 
                                        ON expedientes.colaborador = colaborador.id 
                                INNER JOIN area 
                                        ON colaborador.area = area.id 
                          WHERE expediente_detail.componente = {$intComponente} 
                            AND area.id IN({$intArea})
                          GROUP BY nombrecolaborador
                          ORDER BY nombrecolaborador";
            $result = mysqli_query($conn, $strQuery);
            if( !empty($result) ){
              while($row = mysqli_fetch_assoc($result)) {
                $arrDetalle[$row["id"]]["CIF"] = $row["cif"];
                $arrDetalle[$row["id"]]["NOMBRES"] = $row["nombrecolaborador"];
                $arrDetalle[$row["id"]]["PUESTO"] = $row["puesto"];
                $arrDetalle[$row["id"]]["CONTEO"] = $row["conteo"];
              }
            }
        
            mysqli_close($conn);
            return $arrDetalle;
        }
    }

    public function getEstadisticas($intComponente = 0, $intAreas = 0, $boolExport = false){

        if( $intComponente>0 ){

            $strAndAreas = "";
            if($boolExport){
                $strAndAreas = "AND area.id IN({$intAreas})";
            }else{
                if( count($intAreas)>0 ){
                    $intCountAreas = count($intAreas)-1;
                    if( count($intAreas)>=1 ){
                        for ( $i=0; $i<count($intAreas); $i++ ) {
                            if( $i == $intCountAreas ){
                                $strAndAreas = $strAndAreas.$intAreas[$i];
                            }else{
                                $strAndAreas = $strAndAreas.$intAreas[$i].",";
                            }
                        }
                    }
                    $strAndAreas = "AND area.id IN({$strAndAreas})";
                }elseif($intAreas == 0){
                    $strAndAreas = "";
                }else{
                    $strAndAreas = "";
                }
            }

            $arrEstadisticas = array();
            $conn = getConexion();
            $strQuery = "SELECT area.id,
                                area.nombre nombrearea, 
                                COUNT(expediente_detail.id) conteo 
                           FROM expediente_detail
                                INNER JOIN expedientes 
                                        ON expediente_detail.expediente = expedientes.id
                                INNER JOIN colaborador 
                                        ON expedientes.colaborador = colaborador.id
                                INNER JOIN area 
                                        ON colaborador.area = area.id
                          WHERE expediente_detail.componente = {$intComponente}
                                {$strAndAreas}
                          GROUP BY area.nombre
                          ORDER BY area.nombre";
            $result = mysqli_query($conn, $strQuery);
            if( !empty($result) ){
                while($row = mysqli_fetch_assoc($result)) {
                    $arrEstadisticas[$row["id"]]["COMPONENTE"] = $intComponente;
                    $arrEstadisticas[$row["id"]]["NOMBREAREA"] = $row["nombrearea"];
                    $arrEstadisticas[$row["id"]]["CONTEO"] = $row["conteo"];
                }
            }
            
            mysqli_close($conn);
            return $arrEstadisticas;
        }
    }

}

class rptstock_view{

    private $objModel;
    private $arrRolUser;

	public function __construct($arrRolUser){
        $this->objModel = new rptstock_model();
        $this->arrRolUser = $arrRolUser;
    }

    public function drawSelectArea(){
        $arrArea = $this->objModel->getArea();
        ?>
        <label><h4>1. Seleccione un área:</h4></label>
        <select id="selectArea" name="selectArea[]" style="text-align:center; width:100%;" class="form-control selectpicker" data-selected-text-format="count" data-live-search="true"  data-actions-box="true" multiple>
          <?php
          reset($arrArea);
          while( $rTMP = each($arrArea) ){
          ?>
            <option value="<?php print $rTMP["key"];?>" ><?php print $rTMP["value"]["NOMBRE"];?></option>
          <?php   
          }
          ?>
        </select>
        <?php
    }

    public function drawSelectComponenteHD(){
        $arrComponenteHD = $this->objModel->getComponenteHD();
        ?>
        <label><h4>Seleccione un componente:</h4></label>
        <select id="selectComponente" name="selectComponente" style="text-align:center; width:100%;" class="form-control select2">
          <?php
          reset($arrComponenteHD);
          while( $rTMP = each($arrComponenteHD) ){
          ?>
            <option value="<?php print $rTMP["key"];?>" ><?php print $rTMP["value"]["NOMBRE"];?></option>
          <?php   
          }
          ?>
        </select>
        <?php
    }

    public function drawContentModalDetail($arrDetalle){
        if( count($arrDetalle)>0 ){
            ?>
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th style="text-align:center;">No.</th>
                        <th style="text-align:center;">CIF</th>
                        <th style="text-align:center;">Nombres</th>
                        <th style="text-align:center;">Puesto</th>
                        <th style="text-align:center;">Conteo individual</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $intSumaModal = 0;
                $intCount = 0;
                reset($arrDetalle);
                while( $rTMP = each($arrDetalle) ){
                    $intCount++;
                    $strCIF = $rTMP["value"]["CIF"];
                    $strNombres = $rTMP["value"]["NOMBRES"];
                    $intConteo = intval($rTMP["value"]["CONTEO"]);
                    $strPuesto = $rTMP["value"]["PUESTO"];
                    $intSumaModal = $intSumaModal + $intConteo;
                    ?>
                    <tr>
                        <td style="text-align:center;"><?php print $intCount;?></td>
                        <td style="text-align:center;"><?php print $strCIF;?></td>
                        <td style="text-align:center;"><?php print $strNombres;?></td>
                        <td style="text-align:center;"><?php print $strPuesto;?></td>
                        <td style="text-align:center;"><?php print $intConteo;?></td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <td style="text-align:center;" colspan="3">&nbsp;</td>
                    <td style="text-align:center;"><b>Total</b></td>
                    <td style="text-align:center;"><b><?php print $intSumaModal;?></b></td>
                </tr>
                </tbody>
            </table>
            <?php
        }else{
            ?>
            <h3>Resultados no disponibles.</h3>
            <?php
        }
    }

    public function drawModalDetail(){
        ?>
        <div class="modal fade bd-example-modal-lg" id="modalDetail" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #0480be; color:white;">
                        <h5 class="modal-title" id="titlemodaldetail">Detalle individual por área</h5>
                    </div>
                    <div class="modal-body" id="divcontentmodaldetail">
                        ...
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function drawExportReport($intComponente = 0, $intAreas = 0){
        $strHTML = '';
        $arrEstadisticas = $this->objModel->getEstadisticas($intComponente, $intAreas, true);
        $strComponenteEvaluado = getNombreComponente($intComponente);
        if( count($arrEstadisticas)>0 ){
            $strHTML .= '<div class="row">';
            $strHTML .= '<table class="table table-sm table-hover table-borderless table-condensed">';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="5"></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="5" style="text-align:left; background-color:#2242d4; color:#ffffff;"><h1>Reporte stock componente</h1></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="5"></td>';
            $strHTML .= '</tr>';
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="5" style="text-align:left;"><b>Componente:</b> '.$strComponenteEvaluado.'</td>';
            $strHTML .= '</tr>';
            $hoy = date("d-m-Y");
            $strHTML .= '<tr>';
            $strHTML .= '<td colspan="5" style="text-align:left;"><b>Fecha Impresion</b>: '.$hoy.'</td>';
            $strHTML .= '</tr>';
            $strHTML .= '</table>';
            $strHTML .= '</div>';
            $strHTML .= '<table class="table table-sm table-hover table-borderless table-condensed">';
            $intSuma = 0;
            $intCount = 0;
            reset($arrEstadisticas);
            while( $rTMP = each($arrEstadisticas) ){
                $intCount++;
                $intArea = intval($rTMP["key"]);
                $intComponente = intval($rTMP["value"]["COMPONENTE"]);
                $strNombreArea = utf8_decode($rTMP["value"]["NOMBREAREA"]);
                $intConteo = intval($rTMP["value"]["CONTEO"]);
                $intSuma = $intSuma + $intConteo;
                $arrDetalle = $this->objModel->getDetalle($intArea, $intComponente);
                $strHTML .= '<tr>';
                $strHTML .= '<td style="text-align:left; background-color: #7088f6; color:white;" colspan="5"><b>'.$strNombreArea.' ('.$intConteo.')</b></td>';
                $strHTML .= '</tr>';
                $strHTML .= '<tr>';
                $strHTML .= '<td colspan="5"><br></td>';
                $strHTML .= '</tr>';
                $strHTML .= '<tr>';
                $strHTML .= '<td width="10%" style="text-align:center;"><b>No.</b></td>';
                $strHTML .= '<td width="22.5%" style="text-align:center;"><b>No. CIF</b></td>';
                $strHTML .= '<td width="22.5%" style="text-align:center;"><b>Colaborador</b></td>';
                $strHTML .= '<td width="22.5%" style="text-align:center;"><b>Puesto</b></td>';
                $strHTML .= '<td width="22.5%" style="text-align:center;"><b>Conteo</b></td>';
                $strHTML .= '</tr>';
                $intCountDetail = 0;
                reset($arrDetalle);
                while( $rTMP2 = each($arrDetalle) ){
                    $intCountDetail++;
                    $strCIF = $rTMP2["value"]["CIF"];
                    $strColaborador = utf8_decode($rTMP2["value"]["NOMBRES"]);
                    $strPuesto = utf8_decode($rTMP2["value"]["PUESTO"]);
                    $intConteoDet = $rTMP2["value"]["CONTEO"];
                    $strHTML .= '<tr>';
                    $strHTML .= '<td style="text-align:center;">'.$intCountDetail.'</td>';
                    $strHTML .= '<td style="text-align:center;">'.$strCIF.'</td>';
                    $strHTML .= '<td style="text-align:center;">'.$strColaborador.'</td>';
                    $strHTML .= '<td style="text-align:center;">'.$strPuesto.'</td>';
                    $strHTML .= '<td style="text-align:center;">'.$intConteoDet.'</td>';
                    $strHTML .= '</tr>';
                }
                $strHTML .= '<tr>';
                $strHTML .= '<td colspan="5"><br></td>';
                $strHTML .= '</tr>';
            }
            $strHTML .= '<tr>';
            $strHTML .= '<td></td>';
            $strHTML .= '<td></td>';
            $strHTML .= '<td></td>';
            $strHTML .= '<td style="text-align:center;"><b>Total</b></td>';
            $strHTML .= '<td style="text-align:center;"><b>'.$intSuma.'</b></td>';
            $strHTML .= '</tr>';
            $strHTML .= '</table>';
        }else{
            $strHTML .= '<div class="col-sm-12 col-md-12 col-lg-12">';
            $strHTML .= '<div class="card card-primary">';
            $strHTML .= '<div class="card-body">';
            $strHTML .= '<h3>No se encontró ningún resultado.</h3>';
            $strHTML .= '</div>';
            $strHTML .= '</div>';
            $strHTML .= '</div>';
        }

        return  $strHTML;
    }

    public function drawContentReport($intComponente = 0, $intAreas = 0){
        $arrEstadisticas = $this->objModel->getEstadisticas($intComponente, $intAreas);
        if( count($arrEstadisticas)>0 ){
            ?>
            <script>
                $("#btnExportarPDF").show();
            </script>
            <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card card-primary">
                    <div class="card-body">
                        <div id="no-more-tables">
                            <table class="table table-sm table-hover table-borderless table-condensed table-striped">
                                <thead class="cf">
                                    <tr style="background-color: #17a2b8; color:white;">
                                        <th style="text-align:center;">No.</th>
                                        <th style="text-align:center;">Área</th>
                                        <th style="text-align:center;">Conteo</th>
                                        <th style="text-align:center;">Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $intSuma = 0;
                                $intCount = 0;
                                reset($arrEstadisticas);
                                while( $rTMP = each($arrEstadisticas) ){
                                    $intCount++;
                                    $intArea = intval($rTMP["key"]);
                                    $intComponente = intval($rTMP["value"]["COMPONENTE"]);
                                    $strNombreArea = $rTMP["value"]["NOMBREAREA"];
                                    $intConteo = intval($rTMP["value"]["CONTEO"]);
                                    $intSuma = $intSuma + $intConteo;
                                    ?>
                                    <tr>
                                        <td data-title="No." style="text-align:center;"><h3><span class="badge badge-info"><?php print $intCount;?></span></h3></td>
                                        <td data-title="Área" style="text-align:center;"><?php print $strNombreArea; ?></td>
                                        <td data-title="Conteo" style="text-align:center;"><?php print $intConteo; ?></td>
                                        <td data-title="Detalle" style="text-align:center;"><button class="btn btn-success" onclick="getDetail('<?php print $intArea;?>','<?php print $intComponente;?>')">Ver detalles</button></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td style="text-align:center;"><b>Total..</b></td>
                                    <td style="text-align:center;"><b><?php print $intSuma;?></b></td>
                                    <td>&nbsp;</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }else{
            //
            ?>
            <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card card-primary">
                    <div class="card-body">
                    <h3>No se encontró ningún resultado.</h3>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    public function drawContent(){
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
            <link rel="stylesheet" href="dist/css/bootstrap-select.min.css">
            <!-- Daterange picker -->
            <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker-bs3.css">
            <!-- bootstrap wysihtml5 - text editor -->
            <link rel="stylesheet" href="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
            <!-- Google Font: Source Sans Pro -->
            <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
            <style>
                .centrar{
                    position: absolute;
                    /*nos posicionamos en el centro del navegador*/
                    top:50%;
                    left:50%;
                    float: none;
                    /*determinamos una anchura*/
                    width:400px;
                    /*indicamos que el margen izquierdo, es la mitad de la anchura*/
                    margin-left:-130px;
                    /*determinamos una altura*/
                    height:300px;
                    /*indicamos que el margen superior, es la mitad de la altura*/
                    margin-top:-150px;
                    padding:5px;
                    z-index: 1;
                }

                @media only screen and (max-width: 800px) {
                    /* Force table to not be like tables anymore */
                    #no-more-tables table,
                    #no-more-tables thead,
                    #no-more-tables tbody,
                    #no-more-tables th,
                    #no-more-tables td,
                    #no-more-tables tr { display: block; }
                
                    /* Hide table headers (but not display: none;, for accessibility) */
                    #no-more-tables thead tr {
                        position: absolute;
                        top: -9999px;
                        left: -9999px;
                    }

                    #no-more-tables tr { border: 1px solid #ccc; }
                    
                    #no-more-tables td {
                        /* Behave like a "row" */
                        border: none;
                        border-bottom: 1px solid #eee;
                        position: relative;
                        padding-left: 50%;
                        white-space: normal;
                        text-align:left;
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
                        text-align:left;
                        font-weight: bold;
                    }

                    /*
                    Label the data
                    */
                    #no-more-tables td:before { content: attr(data-title); }
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
            <?php draMenu("rpt_stock.php",3);?>
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
                                <div class="card-header">
                                    <h3 class="card-title">Reporte de Stock</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php $this->drawSelectArea();?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php $this->drawSelectComponenteHD();?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                            <button class="btn btn-info btn-block" onclick="getReporte()">Generar</button>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row"> 
                                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                                            <button id="btnExportarPDF" class="btn btn-info btn-block" onclick="fntExportarData('PDF')" style="display:none;">Imprimir PDF</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-body -->
                                <div class="card-footer clearfix"></div>
                            </div>
                            <?php $this->drawModalDetail(); ?>
                            <div id="divShowLoadingGeneralBig" style="display:none;" class='centrar'><img src="images/load.gif" height="250px" width="250px"></div>
                            <div class="row" id="divContentReporte">
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
            <strong>Copyright 2020</strong>
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
        <script src="dist/js/bootstrap-select.min.js"></script>
        <script>
            $(function () {
                $("#selectArea").selectpicker();
                if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
                    $('.selectpicker').selectpicker('mobile');
                }
            });
        </script>
        <!-- AdminLTE for demo purposes -->
        <script src="dist/js/demo.js"></script>
        <script>

            function destroSession(){
                if (confirm("¿Desea salir de la aplicación?")) {
                    $.ajax({
                        url:"rpt_stock.php",
                        data:
                        {
                            destroSession:true
                        },
                        type:"post",
                        dataType: "json",
                        success:function(data){
                            if ( data.Correcto == "Y" ){
                            alert("Usted ha cerrado sesión");
                            location.href = "index.php";
                            }
                        }
                    });
                }
            }

            function getDetail(intArea, intComponente){
                if( intArea>0 && intComponente>0 ){
                    $.ajax({
                        url: "rpt_stock.php",
                        data: {
                            getDetail: true,
                            intArea: intArea,
                            intComponente: intComponente
                        },
                        type: "post",
                        dataType: "html",
                        beforeSend: function() {
                            $("#divShowLoadingGeneralBig").css("z-index",1050);
                            $("#divShowLoadingGeneralBig").show();
                        },
                        success: function(data) {
                            $("#divShowLoadingGeneralBig").hide();
                            $('#modalDetail').modal();
                            $("#divcontentmodaldetail").html('');
                            $("#divcontentmodaldetail").html(data);
                        }
                    });
                }
            }

            function getReporte(){
                var intArea = $("#selectArea").val();
                var intComponente = $("#selectComponente").val();

                if( intArea == 0){
                    $("#selectArea").css('background-color','#f4d0de');
                }else{
                    $("#selectArea").css('background-color','');
                    var selectAreas = [];
                    $.each($("#selectArea option:selected"), function(){            
                        selectAreas.push($(this).val());
                    });
                }

                if( intComponente == 0){
                    $("#selectComponente").css('background-color','#f4d0de');
                }else{
                    $("#selectComponente").css('background-color','');
                }

                if( intComponente>0 ){
                    $.ajax({
                        url: "rpt_stock.php",
                        data: {
                            getReporte: true,
                            intArea: selectAreas,
                            intComponente: intComponente
                        },
                        type: "post",
                        dataType: "html",
                        beforeSend: function() {
                            $("#divShowLoadingGeneralBig").css("z-index",1050);
                            $("#divShowLoadingGeneralBig").show();
                        },
                        success: function(data) {
                            $("#divShowLoadingGeneralBig").hide();
                            $("#divContentReporte").html(data);
                        }
                    });
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
                input.name = key;'name-as-seen-at-the-server';
                input.value = value;
                theForm.appendChild(input);
            }

            //Permite enviar la peticion para poder exportar el reporte en PDF o EXCEL
            function fntExportarData(strTipoExportar){
                var intArea = $("#selectArea").val();
                var intComponente = $("#selectComponente").val();
                var selectArea = [];
                $.each($("#selectArea option:selected"), function(){            
                    selectArea.push($(this).val());
                });
                
                var objForm = document.getElementById("frmFiltros");
                objForm.target = "_self";
                addHidden(objForm, 'GenerarReporte', true);
                addHidden(objForm, 'selectArea', selectArea);
                addHidden(objForm, 'TipoExport', strTipoExportar);
                addHidden(objForm, 'intComponente', intComponente);
                objForm.submit();
            }

            $( document ).ready(function() {
            });

        </script>
        </body>
        </html>
        <?php
    }

}
?>