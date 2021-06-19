<?php
require_once("core/core.php");
error_reporting(E_ALL);
ini_set('display_errors',0);
session_start();
if ( isset($_SESSION['user_id']) ) {
  $strRolUserSession = getRolUserSession($_SESSION['user_id']);
  $intIDUserSession = getIDUserSession($_SESSION['user_id']);

  if( $strRolUserSession != '' ){
    $arrRolUser["ID"] = $intIDUserSession;
    $arrRolUser["NAME"] = $_SESSION['user_id'];

    if( $strRolUserSession == "master" ){
      $arrRolUser["MASTER"] = true;
    }elseif( $strRolUserSession == "normal" ){
      $arrRolUser["NORMAL"] = true;
    }
  }
}else{
  header("Location: index.php");
}

$objController = new colaborador_controller($arrRolUser);
$objController->process();
$objController->runAjax();
$objController->drawContentController();

class colaborador_controller{

    private $objModel;
    private $objView;
    private $arrRolUser;

	public function __construct($arrRolUser){
		$this->objModel = new colaborador_model();
        $this->objView = new colaborador_view($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContentController(){
        $this->objView->drawContent(); 
    }

    public function runAjax(){
        $this->ajaxDestroySession();
        $this->ajacSearchByArea();
      
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

    public function ajacSearchByArea(){
        if( isset($_POST["searchbyarea"]) && $_POST["searchbyarea"] == true){
            $intArea = isset($_POST["intArea"])? intval($_POST["intArea"]):0;
            $this->objView->drawContentSearch($intArea);
            exit();
        }
    }

    public function process(){
     
        reset($_POST);
        while( $arrTMP = each($_POST) ){
          $arrExplode = explode("_",$arrTMP['key']);
    
          if( $arrExplode[0] == "hdnColaborador"){
            $intColaborador = $arrExplode[1];
            $strAccion = isset($_POST["hdnColaborador_{$intColaborador}"]) ? trim($_POST["hdnColaborador_{$intColaborador}"]):'';
            $strNombres = isset($_POST["txtNombres_{$intColaborador}"]) ? trim($_POST["txtNombres_{$intColaborador}"]):'';
            $strApellidos = isset($_POST["txtApellidos_{$intColaborador}"]) ? trim($_POST["txtApellidos_{$intColaborador}"]):'';
            $intCIF = isset($_POST["txtCIF_{$intColaborador}"]) ? intval($_POST["txtCIF_{$intColaborador}"]):0;
            $strPuesto = isset($_POST["txtPuesto_{$intColaborador}"]) ? trim($_POST["txtPuesto_{$intColaborador}"]):'';
            $intArea = isset($_POST["selectArea_{$intColaborador}"]) ? intval($_POST["selectArea_{$intColaborador}"]):0;
            $intActivo = isset($_POST["selectActivo_{$intColaborador}"]) ? intval($_POST["selectActivo_{$intColaborador}"]):0;
               
            if( $strAccion == "A" ){
                $this->objModel->insertcolaborador($strNombres, $strApellidos, $intCIF, $strPuesto, $intArea, $intActivo,  $this->arrRolUser["ID"]);
            }elseif( $strAccion == "D" ){
                $this->objModel->deletecolaborador($intColaborador);
            }elseif( $strAccion == "E" ){
                $this->objModel->updatecolaborador($intColaborador, $strNombres, $strApellidos, $intCIF, $strPuesto, $intArea, $intActivo, $this->arrRolUser["ID"]);
            }
          }
        }
    }

}

class colaborador_model{

    public function getIDExpedienteColaborador($intColaborador){
        if( $intColaborador>0 ){
            $conn = getConexion();
            $intIDExpediente = 0;
            $strQueryExp = "SELECT id FROM expedientes WHERE colaborador = {$intColaborador}"; 
            $result = mysqli_query($conn, $strQueryExp);
            if( !empty($result) ){
                while($row = mysqli_fetch_assoc($result)) {
                    $intIDExpediente = $row["id"];
                }
            }

            mysqli_close($conn);
            return $intIDExpediente;
        }
    }

    public function insertcolaborador($strNombres, $strApellidos, $intCIF, $strPuesto, $intArea, $intActivo, $intUser){
        if( $strNombres!='' && $strApellidos!= '' && $intCIF>0 && $strPuesto!='' && $intArea>0 && $intUser>0 ){
            $conn = getConexion();
            $strQuery = "INSERT INTO colaborador (nombres, apellidos, cif, puesto, area, activo, add_fecha, add_user) VALUES ('{$strNombres}', '{$strApellidos}', {$intCIF}, '{$strPuesto}', {$intArea}, {$intActivo}, now(), {$intUser})";
            mysqli_query($conn, $strQuery);
            //print $strQuery;

            $intIDColaborador = 0;
            $strQueryCol = "SELECT id FROM colaborador WHERE nombres = '{$strNombres}' AND apellidos = '{$strApellidos}' AND cif = '{$intCIF}'";
            $result = mysqli_query($conn, $strQueryCol);
            if( !empty($result) ){
                while($row = mysqli_fetch_assoc($result)) {
                    $intIDColaborador = $row["id"];
                }
            }

            //Crear evaluacion
            $strQueryExp = "INSERT INTO expedientes (colaborador, add_user, add_fecha) VALUES ({$intIDColaborador},{$intUser},NOW())";
            mysqli_query($conn, $strQueryExp);
        }
    }
    
    public function deletecolaborador($intColaborador){
        if( $intColaborador > 0 ){
            $conn = getConexion();
            $strQuery = "DELETE FROM colaborador WHERE id = {$intColaborador}";
            mysqli_query($conn, $strQuery);

            $intIDExpediente = $this->getIDExpedienteColaborador($intColaborador);
            //Eliminar detail del expediente
            $strQueryDetail = "DELETE FROM expediente_detail WHERE expediente = {$intIDExpediente}";
            mysqli_query($conn, $strQueryDetail);

            $strQueryMaster = "DELETE FROM expedientes WHERE id = {$intIDExpediente}";
            mysqli_query($conn, $strQueryMaster);

        }
    }
    
    public function updatecolaborador($intColaborador, $strNombres, $strApellidos, $intCIF, $strPuesto, $intArea, $intActivo, $intUser){
        if( $intColaborador>0 && $strNombres!='' && $strApellidos!='' && $intCIF>0 && $strPuesto!='' && $intArea>0 && $intUser>0 ){
            $conn = getConexion();
            $strQuery = "UPDATE colaborador 
                            SET nombres = '{$strNombres}',
                                apellidos = '{$strApellidos}',
                                cif = {$intCIF},
                                puesto = '{$strPuesto}',
                                area = {$intArea},
                                activo = {$intActivo},
                                mod_fecha = now(),
                                mod_user = {$intUser} 
                          WHERE id = {$intColaborador}";
            mysqli_query($conn, $strQuery);
        }
    }
    
    public function getColaboradores($intArea = 0){
        
        $strWhereArea = ($intArea>0)? "WHERE area.id = {$intArea}": "";
        $conn = getConexion();
        $arrColaborador = array();
        $strQuery = "SELECT colaborador.id,
                            colaborador.nombres,
                            colaborador.apellidos,
                            colaborador.cif,
                            colaborador.puesto,
                            area.id idarea,
                            area.nombre nombrearea,
                            colaborador.activo
                       FROM colaborador
                            INNER JOIN area 
                                    ON colaborador.area = area.id
                            {$strWhereArea}
                      ORDER BY area.nombre, colaborador.nombres";
        $result = mysqli_query($conn, $strQuery);
        if( !empty($result) ){
          while($row = mysqli_fetch_assoc($result)) {
            $arrColaborador[$row["id"]]["NOMBRES"] = $row["nombres"];
            $arrColaborador[$row["id"]]["APELLIDOS"] = $row["apellidos"];
            $arrColaborador[$row["id"]]["CIF"] = $row["cif"];
            $arrColaborador[$row["id"]]["PUESTO"] = $row["puesto"];
            $arrColaborador[$row["id"]]["IDAREA"] = $row["idarea"];
            $arrColaborador[$row["id"]]["NOMBREAREA"] = $row["nombrearea"];
            $arrColaborador[$row["id"]]["ACTIVO"] = $row["activo"];
          }
        }
    
        mysqli_close($conn);
        return $arrColaborador;
    }

    public function getArea(){
        $conn = getConexion();
        $arrArea = array();
        $strQuery = "SELECT id, nombre FROM area ORDER BY nombre";
        $result = mysqli_query($conn, $strQuery);
        if( !empty($result) ){
          while($row = mysqli_fetch_assoc($result)) {
            $arrArea[$row["id"]]["NOMBRE"] = $row["nombre"];
          }
        }
    
        mysqli_close($conn);
        return $arrArea;
    }

    

}

class colaborador_view{

    private $objModel;
    private $arrRolUser;

	public function __construct($arrRolUser){
        $this->objModel = new colaborador_model();
        $this->arrRolUser = $arrRolUser;
    }

    public function drawSelectAreaAll(){
        $arrArea = $this->objModel->getArea();
        ?>
        <select id="selectAreaAll" name="selectAreaAll" style="text-align: center;" class="form-control">
          <?php
          reset($arrArea);
          while( $rTMP = each($arrArea) ){
            ?>
            <option value="<?php print $rTMP["key"];?>"><?php print $rTMP["value"]["NOMBRE"];?></option>
            <?php   
          }
          ?>
        </select>
        <?php
    }

    public function drawSelectArea($intId = 0, $intArea = 0){
        $arrArea = $this->objModel->getArea();
        ?>
        <select id="selectArea_<?php print $intId;?>" name="selectArea_<?php print $intId;?>" style="text-align: center;" class="form-control">
          <?php
          reset($arrArea);
          while( $rTMP = each($arrArea) ){
          $strSelected = ( ($rTMP["key"] == $intArea) )? 'selected':'';
          ?>
          <option value="<?php print $rTMP["key"];?>" <?php print $strSelected;?> ><?php print $rTMP["value"]["NOMBRE"];?></option>
          <?php   
          }
          ?>
        </select>
        <?php
    }

    public function drawSelectActivo($intId = 0, $intActivo = 0){
        ?>
        <select id="selectActivo_<?php print $intId;?>" name="selectActivo_<?php print $intId;?>" style="text-align: center;" class="form-control">
          <option value="0" <?php print ($intActivo == 0)? "selected":"";?> >No</option>
          <option value="1" <?php print ($intActivo == 1)? "selected":"";?> >Si</option>
        </select>
        <?php
    }

    public function drawContentSearch($intArea = 0){
        if( $intArea>0 ){
            $arrColaborador = $this->objModel->getColaboradores($intArea);
            ?>
            <div id="no-more-tables">
            <table class="table table-sm table-hover table-condensed" id="tblColaborador">
                <thead class="cf">
                    <tr style="background-color: #28a745; color:white;">
                        <th style="text-align:center;">No. </th>
                        <th style="text-align:center;">CIF</th>
                        <th style="text-align:center;">Nombres</th>
                        <th style="text-align:center;">Apellidos</th>
                        <th style="text-align:center;">Puesto</th>
                        <th style="text-align:center;">Area</th>
                        <th style="text-align:center;">Activo</th>
                        <th colspan="2"></th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $intConteo = 0;
                reset($arrColaborador);
                while( $rTMP = each($arrColaborador) ){
                    $intConteo++;
                    $intID = $rTMP["key"];
                    $strNombres = isset($rTMP["value"]["NOMBRES"])? $rTMP["value"]["NOMBRES"]: "";
                    $strApellidos = isset($rTMP["value"]["APELLIDOS"])? $rTMP["value"]["APELLIDOS"]: "";
                    $intCIF = isset($rTMP["value"]["CIF"])? intval($rTMP["value"]["CIF"]): 0;
                    $strPuesto = isset($rTMP["value"]["PUESTO"])? $rTMP["value"]["PUESTO"]: "";
                    $intArea = isset($rTMP["value"]["IDAREA"])? intval($rTMP["value"]["IDAREA"]): 0;
                    $strNombreArea = isset($rTMP["value"]["NOMBREAREA"])? $rTMP["value"]["NOMBREAREA"]: "";
                    $intActivo = isset($rTMP["value"]["ACTIVO"])? intval($rTMP["value"]["ACTIVO"]): 0;
                    $strNombreActivo = ($intActivo == 1)? "Si": "No";
                    ?>
                    <tr id="trColaborador_<?php print $intID;?>">
                        <td data-title="No." style="text-align:center;">
                            <h3><span class="badge badge-success"><?php print $intConteo;?></span></h3>
                            <input id="hdnColaborador_<?php print $intID;?>" name="hdnColaborador_<?php print $intID;?>"  type="hidden" value="N">
                        </td>
                        <td data-title="CIF" style="text-align:center;">
                            <div id="divShowColaboradorCIF_<?php print $intID;?>">
                                <?php print $intCIF;?>
                            </div>
                            <div id="divEditColaboradorCIF_<?php print $intID;?>" style="display:none;">
                                <input class="form-control" type="number" id="txtCIF_<?php print $intID;?>" name="txtCIF_<?php print $intID;?>" value="<?php print $intCIF;?>">
                            </div>
                        </td>
                        <td data-title="Nombres" style="text-align:center;">
                            <div id="divShowColaboradorNombres_<?php print $intID;?>">
                                <?php print $strNombres;?>
                            </div>
                            <div id="divEditColaboradorNombres_<?php print $intID;?>" style="display:none;">
                                <input class="form-control" type="text" id="txtNombres_<?php print $intID;?>" name="txtNombres_<?php print $intID;?>" value="<?php print $strNombres;?>">
                            </div>
                        </td>
                        <td data-title="Apellidos" style="text-align:center;">
                            <div id="divShowColaboradorApellidos_<?php print $intID;?>">
                                <?php print $strApellidos;?>
                            </div>
                            <div id="divEditColaboradorApellidos_<?php print $intID;?>" style="display:none;">
                                <input class="form-control" type="text" id="txtApellidos_<?php print $intID;?>" name="txtApellidos_<?php print $intID;?>" value="<?php print $strApellidos;?>">
                            </div>
                        </td>
                        <td data-title="Puesto" style="text-align:center;">
                            <div id="divShowColaboradorPuesto_<?php print $intID;?>">
                                <?php print $strPuesto;?>
                            </div>
                            <div id="divEditColaboradorPuesto_<?php print $intID;?>" style="display:none;">
                                <input class="form-control" type="text" id="txtPuesto_<?php print $intID;?>" name="txtPuesto_<?php print $intID;?>" value="<?php print $strPuesto;?>">
                            </div>
                        </td>
                        <td data-title="Area" style="text-align:center;">
                            <div id="divShowColaboradorArea_<?php print $intID;?>">
                                <?php print $strNombreArea;?>
                            </div>
                            <div id="divEditColaboradorArea_<?php print $intID;?>" style="display:none;">
                                <?php 
                                    $this->drawSelectArea($intID, $intArea);
                                ?>
                            </div>
                        </td>
                        <td data-title="Activo" style="text-align:center;">
                            <div id="divShowColaboradorActivo_<?php print $intID;?>">
                                <?php print $strNombreActivo;?>
                            </div>
                            <div id="divEditColaboradorActivo_<?php print $intID;?>" style="display:none;">
                                <?php 
                                    $this->drawSelectActivo($intID, $intActivo);
                                ?>
                            </div>
                        </td>
                        <td style="text-align:center;"><button class="btn btn-info btn-block" onclick="editColaborador('<?php print $intID;?>')">Editar</button></td>
                        <td style="text-align:center;"><button class="btn btn-danger btn-block btn-block" onclick="deleteColaborador('<?php print $intID;?>')">Eliminar</button></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <table class="table table-sm table-hover table-condensed">
                <tr>
                    <td style="text-align:center;"><button class="btn btn-success btn-block" onclick="agregarColaborador()"><i class="fa fa-plus"> Agregar</i></button></td>
                    <td style="text-align:center;"><button type="button" class="btn btn-success btn-block" onclick="checkForm()"><i class="fa fa-floppy-o"> Guardar</i></button></td>
                </tr>
            </table>
            </div>
            <?php
        }else{
            ?>
            <p>No se encontraron resultados.</p>
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
            <!-- Daterange picker -->
            <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker-bs3.css">
            <!-- bootstrap wysihtml5 - text editor -->
            <link rel="stylesheet" href="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
            <!-- Google Font: Source Sans Pro -->
            <link href="dist/css/fontgoogleapiscss.css" rel="stylesheet">
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

           <?php draMenu("colaborador.php", 1);?>
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
            <div id="divShowLoadingGeneralBig" style="display:none;" class='centrar'><img src="images/load.gif" height="250px" width="250px"></div>
            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xs-12 col-md-12 col-sm-12 col-lg-12">
                            <div class="card">
                                <div class="card-header" style="text-align:center; padding: 10px;">
                                    <h3 class="card-title">Gestión de colaboradores</h3>
                                    <br><br>
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">
                                            <h5><b>Seleccione un Agencia/Área:</b></h5>
                                        </div>
                                        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">
                                            <?php $this->drawSelectAreaAll(); ?>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">
                                            <button class="btn btn-success btn-block" onclick="searchbyarea()">Mostrar Listado</button>
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <!-- /.card-header -->
                                <div class="card-body" id="divContent"></div>
                                <!-- /.card-body -->
                                <div class="card-footer clearfix"></div>
                            </div>
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
        <!-- AdminLTE for demo purposes -->
        <script src="dist/js/demo.js"></script>
        <script>

            function destroSession(){
                if (confirm("¿Desea salir de la aplicación?")) {
                    $.ajax({
                        url:"colaborador.php",
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

            function fntGetCountColaborador(){
                var intCount = 0;
                $("input[name*='txtNombres_']").each(function(){
                    intCount++;   
                });
                return intCount;  
            }

            function fntGetCountMax(){
                var valores = [];
                var intCount = 0;
                $("input[name*='hdnColaborador_']").each(function(){
                    var arrSplit = $(this).attr("id").split("_");
                    valores.push(arrSplit[1]); 
                });
                var max = parseInt(Math.max.apply(null,valores));
                if( isNaN(max) ){
                    max = fntGetCountColaborador();
                }
                return max+1;  
            }


            function editColaborador(id){
                $("#divEditColaboradorNombres_"+id).show();
                $("#divShowColaboradorNombres_"+id).hide();

                $("#divEditColaboradorApellidos_"+id).show();
                $("#divShowColaboradorApellidos_"+id).hide();

                $("#divEditColaboradorCIF_"+id).show();
                $("#divShowColaboradorCIF_"+id).hide();

                $("#divEditColaboradorPuesto_"+id).show();
                $("#divShowColaboradorPuesto_"+id).hide();

                $("#divEditColaboradorArea_"+id).show();
                $("#divShowColaboradorArea_"+id).hide();

                $("#divEditColaboradorActivo_"+id).show();
                $("#divShowColaboradorActivo_"+id).hide();

                $("#hdnColaborador_"+id).val("E");
            }

            function deleteColaborador(id){
                $("#trColaborador_"+id).css('background-color','#f4d0de');
                $("#hdnColaborador_"+id).val("D");
            }

            var intFilasColaborador = 0;
            function agregarColaborador(){
                intFilasColaborador = fntGetCountColaborador();
                intFilasColaborador++;

                max = fntGetCountMax();

                var $tabla = $("#tblColaborador");
                var $tr = $("<tr></tr>");
                // creamos la columna o td
                var $td = $("<td data-title='No.' style='text-align:center;'><b>"+intFilasColaborador+"<b><input class='form-control' type='hidden' id='hdnColaborador_"+max+"' name='hdnColaborador_"+max+"' value='A'></td>")
                $tr.append($td);

                var $td = $("<td data-title='Nombres' style='text-align:center;'><input class='form-control' type='text' id='txtNombres_"+max+"' name='txtNombres_"+max+"'></td>")
                $tr.append($td);

                var $td = $("<td data-title='Apellidos' style='text-align:center;'><input class='form-control' type='text' id='txtApellidos_"+max+"' name='txtApellidos_"+max+"'></td>")
                $tr.append($td);

                var $td = $("<td data-title='CIF' style='text-align:center;'><input class='form-control' type='number' id='txtCIF_"+max+"' name='txtCIF_"+max+"'></td>")
                $tr.append($td);

                var $td = $("<td data-title='Puesto' style='text-align:center;'><input class='form-control' type='text' id='txtPuesto_"+max+"' name='txtPuesto_"+max+"'></td>")
                $tr.append($td);

                var $td = $("<td data-title='Area' style='text-align:center;'><select class='form-control' id='selectArea_"+max+"' name='selectArea_"+max+"' style='text-align: center;'><?php $arrArea = $this->objModel->getArea();reset($arrArea);while( $rTMP = each($arrArea) ){ ?><option value='<?php print $rTMP["key"];?>'><?php print $rTMP["value"]["NOMBRE"];?></option><?php } ?></select></td>");
                $tr.append($td);

                var $td = $("<td data-title='Activo' style='text-align:center;'><select class='form-control' id='selectActivo_"+max+"' name='selectActivo_"+max+"' style='text-align: center;'><option value='0'>No</option><option value='1'>Si</option></select></td>");
                $tr.append($td);

                var $td = $("<td style='text-align:center; display:none;'></td>");
                $tr.append($td);
                
                var $td = $("<td style='text-align:center; display:none;'></td>");
                $tr.append($td);

                $tabla.append($tr); 
            }

            function fntCheckJustNumber(objToCheck){
                objCheck = $(objToCheck);
                var strText = objCheck.val().trim();
                boolResult = true;
                var regex = /^[.0-9]*$/gm;
                if( regex.test(strText) == false ){
                    boolResult = false;
                }
                else{ 
                }
                return boolResult;
            }

            function searchbyarea(){
                var intArea = $("#selectAreaAll").val();
                $.ajax({
                    url:"colaborador.php",
                    data:
                    {
                        searchbyarea:true,
                        intArea:intArea
                    },
                    type:"POST",
                    beforeSend: function() {
                        $("#divShowLoadingGeneralBig").show();
                    },
                    success:function(data){
                        $("#divShowLoadingGeneralBig").hide();
                        $("#divContent").html('');
                        $("#divContent").html(data);
                    }
                });
            }

            function checkForm(){
                var boolError = false;

                $("input[name*='txtNombres_']").each(function(){
                    if( $(this).val() == '' ){
                        $(this).css('background-color','#f4d0de');
                        boolError = true;
                    }else{
                        $(this).css('background-color','');
                    }   
                });

                $("input[name*='txtApellidos_']").each(function(){
                    if( $(this).val() == '' ){
                        $(this).css('background-color','#f4d0de');
                        boolError = true;
                    }else{
                        $(this).css('background-color','');
                    }   
                });

                $("input[name*='txtCIF_']").each(function(){
                    if( $(this).val() == '' || fntCheckJustNumber($(this)) == false || $(this).val()<=0 ){
                        $(this).css('background-color','#f4d0de');
                        boolError = true;
                    }else{
                        $(this).css('background-color','');
                    }   
                });

                $("input[name*='txtPuesto_']").each(function(){
                    if( $(this).val() == '' ){
                        $(this).css('background-color','#f4d0de');
                        boolError = true;
                    }else{
                        $(this).css('background-color','');
                    }   
                });

                if( boolError == false ){
                    var objSerialized = $("#tblColaborador").find("select, input").serialize();
                    $.ajax({
                        url:"colaborador.php",
                        data: objSerialized,
                        type:"POST",
                        beforeSend: function() {
                            $("#divShowLoadingGeneralBig").show();
                        },
                        success:function(data){
                            $("#divShowLoadingGeneralBig").hide();
                            location.href = "colaborador.php"; 
                        }
                    });
                }else{
                    alert('Faltan campos por llenar y/o revisar que los campos no contengan caracteres extraños');
                }
            }


        </script>
        </body>
        </html>

        <?php
    }

}

?>