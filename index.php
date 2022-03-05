<?php
require_once("core/core.php");
$objController = new login_controller();
$objController->runAjax();
$objController->drawContentController();

class login_controller
{

    private $objModel;
    private $objView;

    public function __construct()
    {
        $this->objModel = new login_model();
        $this->objView = new login_view();
    }

    public function drawContentController()
    {
        $this->objView->drawContent();
    }

    public function runAjax()
    {
        $this->ajaxAuthUser();
    }

    public function ajaxAuthUser()
    {
        if (isset($_POST['loginUsername'])) {
            $strUser = isset($_POST['loginUsername']) ? trim($_POST['loginUsername']) : "";
            $strPassword = isset($_POST['loginPassword']) ? trim($_POST['loginPassword']) : "";
            $arrReturn = array();
            $boolRedirect = $this->objModel->redirect_dashboard($strUser, $strPassword);
            $arrReturn["boolAuthRedirect"] = $boolRedirect;
            print json_encode($arrReturn);
            exit();
        }
    }
}
class login_model
{

    public function redirect_dashboard($username, $password)
    {
        $boolRedirect = auth_user($username, $password);
        return $boolRedirect;
    }
}
class login_view
{

    private $objModel;

    public function __construct()
    {
        $this->objModel = new login_model();
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

            <!-- Font Awesome -->
            <link rel="stylesheet" href="dist/css/font-awesome.min.css">
            <!-- Ionicons -->
            <link rel="stylesheet" href="dist/css/ionicons.min.css">
            <!-- Theme style -->
            <link rel="stylesheet" href="dist/css/adminlte.min.css">
            <!-- iCheck -->
            <link rel="stylesheet" href="plugins/iCheck/square/blue.css">
            <!-- Google Font: Source Sans Pro -->
            <link href="dist/css/fontgoogleapiscss.css" rel="stylesheet">
        </head>

        <body class="hold-transition login-page">
            <div class="login-box">
                <div class="login-logo">
                    Inventario IT
                </div>
                <!-- /.login-logo -->
                <div class="card">
                    <div class="card-body login-card-body" id="frmLogin">
                        <form method="POST" action="javascript:void(0);">
                            <div class="form-group has-feedback">
                                <input id="loginUsername" name="loginUsername" type="text" class="form-control" placeholder="Usuario">
                            </div>
                            <div class="form-group has-feedback">
                                <input id="loginPassword" name="loginPassword" type="password" class="form-control" placeholder="Password">
                            </div>
                            <div class="row">
                                <!-- /.col -->
                                <div class="col-12">
                                    <button class="btn btn-primary btn-block btn-flat" id="btnInicioSession" onclick="checkForm()">Iniciar Sesion</button>
                                </div>
                                <!-- /.col -->
                            </div>
                        </form>
                    </div>
                    <!-- /.login-card-body -->
                </div>
            </div>
            <!-- /.login-box -->

            <!-- jQuery -->
            <script src="plugins/jquery/jquery.min.js"></script>
            <!-- Bootstrap 4 -->
            <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
            <!-- iCheck -->
            <script src="plugins/iCheck/icheck.min.js"></script>
            <script>
                $("#loginPassword").keypress(function(e) {
                    if (e.which == 13) {
                        checkForm();
                    }
                });

                function checkForm() {
                    var boolError = false;
                    if ($("#loginUsername").val() == '') {
                        $("#loginUsername").css('background-color', '#f4d0de');
                        boolError = true;
                    } else {
                        $("#loginUsername").css('background-color', '');
                    }

                    if ($("#loginPassword").val() == '') {
                        $("#loginPassword").css('background-color', '#f4d0de');
                        boolError = true;
                    } else {
                        $("#loginPassword").css('background-color', '');
                    }

                    if (boolError == false) {
                        var objSerialized = $("#frmLogin").find("select, input").serialize();
                        $.ajax({
                            url: "index.php",
                            data: objSerialized,
                            type: "post",
                            dataType: "json",
                            beforeSend: function() {
                                $("#btnInicioSession").prop('disabled', true);
                                $("#divShowLoadingGeneralBig").css("z-index", 1050);
                                $("#divShowLoadingGeneralBig").show();
                            },
                            success: function(data) {
                                $("#btnInicioSession").prop('disabled', false);
                                if (data.boolAuthRedirect == "1") {
                                    $("#divShowLoadingGeneralBig").hide();
                                    location.href = "menu.php";
                                } else if (data.boolAuthRedirect == "2") {
                                    alert("Password invalido");
                                    $("#divShowLoadingGeneralBig").hide();
                                    $("#loginUsername").val('');
                                    $("#loginPassword").val('');
                                } else if (data.boolAuthRedirect == "3") {
                                    alert("Usuario y/o password invalidos");
                                    $("#divShowLoadingGeneralBig").hide();
                                    $("#loginUsername").val('');
                                    $("#loginPassword").val('');
                                }
                            }
                        });
                    }
                }
            </script>
        </body>

        </html>

<?php
    }
}

?>