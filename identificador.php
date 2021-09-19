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

$objController = new identificador_controller($arrRolUser);
$objController->process();
$objController->runAjax();
$objController->drawContentController();

class identificador_controller{

    private $objModel;
    private $objView;
    private $arrRolUser;

	public function __construct($arrRolUser){
		$this->objModel = new identificador_model();
        $this->objView = new identificador_view($arrRolUser);
        $this->arrRolUser = $arrRolUser;
    }

    public function drawContentController(){
        $this->objView->drawContent(); 
    }

    public function runAjax(){
        $this->ajaxDestroySession();
      
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

    public function process(){
        reset($_POST);
        while( $arrTMP = each($_POST) ){
          $arrExplode = explode("_",$arrTMP['key']);
    
          if( $arrExplode[0] == "hdnIdentificador"){
            $intTI = $arrExplode[1];
            $strAccion = isset($_POST["hdnIdentificador_{$intTI}"]) ? trim($_POST["hdnIdentificador_{$intTI}"]):'';
            $strNombre = isset($_POST["txtNombre_{$intTI}"]) ? trim($_POST["txtNombre_{$intTI}"]):'';
    
            if( $strAccion == "A" ){
                $this->objModel->insertIdentificador($strNombre, $this->arrRolUser["ID"]);
            }elseif( $strAccion == "D" ){
                $this->objModel->deleteIdentificador($intTI);
            }elseif( $strAccion == "E" ){
                $this->objModel->updateIdentificador($intTI, $strNombre, $this->arrRolUser["ID"]);
            }
          }
        }
      }

}

class identificador_model{

    public function insertIdentificador($strNombre, $intUser){
        if( $strNombre != '' && $intUser > 0 ){
            $conn = getConexion();
            $strQuery = "INSERT INTO tipo_identificador (nombre, add_fecha, add_user) VALUES ('{$strNombre}', now(), {$intUser})";
            mysqli_query($conn, $strQuery);
        }
      }
    
    public function deleteIdentificador($intTipoIdentificador){
        if( $intTipoIdentificador > 0 ){
            $conn = getConexion();
            $strQuery = "DELETE FROM tipo_identificador WHERE id = {$intTipoIdentificador}";
            mysqli_query($conn, $strQuery);
        }
    }
    
    public function updateIdentificador($intTipoIdentificador, $strNombre, $intUser){
        if( $intTipoIdentificador > 0 && $strNombre != '' && $intUser > 0 ){
            $conn = getConexion();
            $strQuery = "UPDATE tipo_identificador 
                            SET nombre = '{$strNombre}',
                                mod_fecha = now(),
                                mod_user = {$intUser} 
                          WHERE id = {$intTipoIdentificador}";
            mysqli_query($conn, $strQuery);
        }
    }
    
    public function getIdentificador(){
        $conn = getConexion();
        $arrTipoIdentificador = array();
        $strQuery = "SELECT id, nombre FROM tipo_identificador ORDER BY nombre";
        $result = mysqli_query($conn, $strQuery);
        if( !empty($result) ){
          while($row = mysqli_fetch_assoc($result)) {
            $arrTipoIdentificador[$row["id"]]["NOMBRE"] = $row["nombre"];
          }
        }
    
        mysqli_close($conn);
        return $arrTipoIdentificador;
    }


}

class identificador_view{

    private $objModel;
    private $arrRolUser;

	public function __construct($arrRolUser){
        $this->objModel = new identificador_model();
        $this->arrRolUser = $arrRolUser;
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
            <?php draMenu("identificador.php", 2);?>
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
                            <div class="card">
                                <div class="card-header" style="text-align:center;">
                                    <h3 class="card-title">Catálogo de tipo identificador</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <div id="no-more-tables">
                                        <table class="table table-sm table-hover table-condensed" id="tblAreas">
                                            <thead class="cf">
                                                <tr>
                                                    <th style="text-align:center;">No. </th>
                                                    <th style="text-align:center;">Nombre</th>
                                                    <th style="text-align:center;" colspan="2">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php 
                                            $arrIdentificador = $this->objModel->getIdentificador();
                                            $intConteo = 0;
                                            reset($arrIdentificador);
                                            while( $rTMP = each($arrIdentificador) ){
                                                $intConteo++;
                                                $intID = $rTMP["key"];
                                                $strNombre = isset($rTMP["value"]["NOMBRE"])? trim($rTMP["value"]["NOMBRE"]): "";
                                                ?>
                                                <tr id="trIdentificador_<?php print $intID;?>">
                                                    <td data-title="No." style="text-align:center;">
                                                        <h3><span class="badge badge-success"><?php print $intConteo;?></span></h3>
                                                        <input id="hdnIdentificador_<?php print $intID;?>" name="hdnIdentificador_<?php print $intID;?>"  type="hidden" value="N">
                                                    </td>
                                                    <td data-title="Nombre" style="text-align:center;">
                                                        <div id="divShowTINombre_<?php print $intID;?>">
                                                            <?php print $strNombre;?>
                                                        </div>
                                                        <div id="divEditTINombre_<?php print $intID;?>" style="display:none;">
                                                            <input class="form-control" type="text" id="txtNombre_<?php print $intID;?>" name="txtNombre_<?php print $intID;?>" value="<?php print $strNombre;?>">
                                                        </div>
                                                    </td>
                                                    <td  data-title="Acciones"  style="text-align:center;">
                                                        <button class="btn btn-info btn-block" onclick="editTI('<?php print $intID;?>')"><i class="fa fa-pencil"></i> Editar</button>
                                                        <button class="btn btn-danger btn-block" onclick="deleteTI('<?php print $intID;?>')"><i class="fa fa-trash"></i> Eliminar</button>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                        <table class="table table-sm table-hover table-condensed">
                                            <tr>
                                                <td style="text-align:center;"><button class="btn btn-primary btn-block" onclick="agregarTI()"><i class="fa fa-plus"></i> Agregar</button></td>
                                                <td style="text-align:center;"><button type="button" class="btn btn-success btn-block" onclick="checkForm()"><i class="fa fa-floppy-o"></i> Guardar</button></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
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
                        url:"identificador.php",
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

            function fntGetCountTI(){
                var intCount = 0;
                $("input[name*='txtNombre_']").each(function(){
                    intCount++;   
                });
                return intCount;  
            }

            function fntGetCountMax(){
                var valores = [];
                var intCount = 0;
                $("input[name*='hdnIdentificador_']").each(function(){
                    var arrSplit = $(this).attr("id").split("_");
                    valores.push(arrSplit[1]); 
                });
                var max = parseInt(Math.max.apply(null,valores));
                if( isNaN(max) ){
                    max = fntGetCountTI();
                }
                return max+1;  
            }

            function editTI(id){
                $("#divEditTINombre_"+id).show();
                $("#divShowTINombre_"+id).hide();
                $("#hdnIdentificador_"+id).val("E");
            }

            function deleteTI(id){
                $("#trIdentificador_"+id).css('background-color','#f4d0de');
                $("#hdnIdentificador_"+id).val("D");
            }

            var intFilasTI = 0;
            function agregarTI(){
                intFilasTI = fntGetCountTI();
                intFilasTI++;

                max = fntGetCountMax();

                var $tabla = $("#tblAreas");
                var $tr = $("<tr></tr>");
                // creamos la columna o td
                var $td = $("<td data-title='No.' style='text-align:center;'><b>"+intFilasTI+"<b><input class='form-control' type='hidden' id='hdnIdentificador_"+max+"' name='hdnIdentificador_"+max+"' value='A'></td>")
                $tr.append($td);

                var $td = $("<td data-title='Nombre' style='text-align:center;'><input class='form-control' type='text' id='txtNombre_"+max+"' name='txtNombre_"+max+"'></td>")
                $tr.append($td);

                var $td = $("<td style='text-align:center; display:none;'></td>");
                $tr.append($td);
                var $td = $("<td style='text-align:center; display:none;'></td>");
                $tr.append($td);

                $tabla.append($tr); 
            }

            function checkForm(){
                var boolError = false;
                $("input[name*='txtNombre_']").each(function(){
                    if( $(this).val() == '' ){
                        $(this).css('background-color','#f4d0de');
                        boolError = true;
                    }else{
                        $(this).css('background-color','');
                    }   
                });

                if( boolError == false ){
                    var objSerialized = $("#tblAreas").find("select, input").serialize();
                    $.ajax({
                        url:"identificador.php",
                        data: objSerialized,
                        type:"POST",
                        beforeSend: function() {
                            $("#divShowLoadingGeneralBig").show();
                        },
                        success:function(data){
                            $("#divShowLoadingGeneralBig").hide();
                            location.href = "identificador.php"; 
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