<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
require('plugins/fpdf186/fpdf.php');

function getConexion()
{

    $servername = "localhost:3306";
    $username = "root";
    $password = "";
    $dbname = "inventarioit";
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    } else {
        return $conn;
    }
}

function executeQuery($strQuery){
    if( $strQuery!='' ){
        $conn = getConexion();
        $result = mysqli_query($conn, $strQuery);
        mysqli_close($conn);
        return $result;
    }
}

function preformato($objeto){
    print "<pre>";
    print_r($objeto);
    print "</pre>";
}

function formatoFecha($fecha) {
    // Establece el idioma en español
    setlocale(LC_TIME, 'es_ES.UTF-8');

    // Convierte la fecha al formato Unix timestamp
    $timestamp = strtotime($fecha);

    // Verifica si la conversión fue exitosa
    if ($timestamp === false) {
        return "Fecha inválida";
    }

    // Obtiene el número de mes (1-12)
    $numero_mes = date('n', $timestamp);

    // Convierte el número de mes en su nombre en español
    switch ($numero_mes) {
        case 1:
            $nombre_mes = 'enero';
            break;
        case 2:
            $nombre_mes = 'febrero';
            break;
        case 3:
            $nombre_mes = 'marzo';
            break;
        case 4:
            $nombre_mes = 'abril';
            break;
        case 5:
            $nombre_mes = 'mayo';
            break;
        case 6:
            $nombre_mes = 'junio';
            break;
        case 7:
            $nombre_mes = 'julio';
            break;
        case 8:
            $nombre_mes = 'agosto';
            break;
        case 9:
            $nombre_mes = 'septiembre';
            break;
        case 10:
            $nombre_mes = 'octubre';
            break;
        case 11:
            $nombre_mes = 'noviembre';
            break;
        case 12:
            $nombre_mes = 'diciembre';
            break;
        default:
            $nombre_mes = 'desconocido';
    }

    // Formatea la fecha en el formato "DD de mes de YYYY"
    $fecha_formateada = strftime('%d de '.$nombre_mes.' de %Y', $timestamp);

    return $fecha_formateada;
}

function auth_user($username, $password)
{
    $arrValues = array();

    if ( $username != '' ) {
        $strQuery = "SELECT password FROM usuarios WHERE nombre = '{$username}'";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrValues["PASSWORD"] = $row["password"];
            }
        }
    }

    if (isset($arrValues["PASSWORD"])) {
        if (($arrValues["PASSWORD"] == $password)) {
            session_start();
            $_SESSION['user_id'] = $username;
            $strValueSession = $_SESSION['user_id'];
            insertSession($strValueSession);
            return 1;
        } else {
            return 2;
        }
    } else {
        return 3;
    }
}

function convertDateMysql($strFecha)
{
    $strFechaConvert = "";
    if ($strFecha != '') {
        $arrExplode = explode("/", $strFecha);
        $strFechaConvert = $arrExplode[2] . '-' . $arrExplode[1] . '-' . $arrExplode[0];
    }
    return $strFechaConvert;
}

function insertSession($strSession)
{
    if ($strSession != '') {
        $strQuery = "INSERT INTO session_user (nombre, add_user, add_fecha) VALUES ('{$strSession}', 1, now())";
        executeQuery($strQuery);
    }
}

function getRolUserSession($sessionName)
{
    $strRolUserSession = "";
    if ($sessionName != '') {
        $strQuery = "SELECT tipo_usuario.nombre
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nombre 
                            INNER JOIN tipo_usuario 
                                    ON usuarios.tipo = tipo_usuario.id
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $strRolUserSession = $row["nombre"];
            }
        }
    }

    return $strRolUserSession;
}

function getPuestoUserSession($sessionName)
{
    $strPuestoUserSession = "";
    if ($sessionName != '') {
        $strQuery = "SELECT usuarios.puesto
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nombre 
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $strPuestoUserSession = $row["puesto"];
            }
        }
    }

    return $strPuestoUserSession;
}

function getIDUserSession($sessionName)
{
    $intIDUserSession = "";
    if ($sessionName != '') {
        $strQuery = "SELECT usuarios.id
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nombre 
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $intIDUserSession = $row["id"];
            }
        }
    }

    return $intIDUserSession;
}

function getNombreColaborador($intColaborador)
{
    if ($intColaborador > 0) {
        $strNombre = "";
        $strQuery = "SELECT CONCAT(nombres,' ',apellidos) nombrecompleto FROM colaborador WHERE id = {$intColaborador}";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $strNombre = $row["nombrecompleto"];
            }
        }

        return $strNombre;
    }
}

function getNombreComponente($intComponente)
{
    if ($intComponente > 0) {
        $strNombre = "";
        $strQuery = "SELECT nombre FROM componente WHERE id = {$intComponente}";
        $result = executeQuery($strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $strNombre = $row["nombre"];
            }
        }

        return $strNombre;
    }
}

function generatePassword($length = 8)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $count = mb_strlen($chars);
    for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
    }
    return $result;
}

function upper_tildes($strString, $boolProper = false)
{
    if ($boolProper) {
        $strString = ucwords($strString);
    } else {
        $strString = strtoupper($strString);
        $strString = str_replace("á", "Á", $strString);
        $strString = str_replace("é", "É", $strString);
        $strString = str_replace("í", "Í", $strString);
        $strString = str_replace("ó", "Ó", $strString);
        $strString = str_replace("ú", "Ú", $strString);
        $strString = str_replace("ä", "Ä", $strString);
        $strString = str_replace("ë", "Ë", $strString);
        $strString = str_replace("ï", "Ï", $strString);
        $strString = str_replace("ö", "Ö", $strString);
        $strString = str_replace("ü", "Ü", $strString);
        $strString = str_replace("ñ", "Ñ", $strString);
    }

    return $strString;
}

function draMenu($strNamePage = "", $intMenu = 0)
{
?>
    <!-- Sidebar Menu -->
    <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <!-- Add icons to the links using the .nav-icon class with font-awesome or any other icon font library -->
            <li class="nav-item has-treeview <?php print ($intMenu == 1) ? "menu-open" : ""; ?>" style="text-align:left;">
                <a href="#" class="nav-link <?php print ($intMenu == 1) ? "active" : ""; ?>">
                    <i class="nav-icon fa fa-book"></i>
                    <p>MENÚ PRINCIPAL<i class="fa fa-angle-left right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" style="text-align:left;">
                        <a href="colaborador.php" class="<?php print ($strNamePage == "colaborador.php") ? "nav-link active" : "nav-link"; ?>">
                            <i class="fa fa-circle-o nav-icon"></i>
                            <p>Colaborador</p>
                        </a>
                    </li>
                    <li class="nav-item" style="text-align:left;">
                        <a href="expediente.php" class="<?php print ($strNamePage == "expediente.php") ? "nav-link active" : "nav-link"; ?>">
                            <i class="fa fa-circle-o nav-icon"></i>
                            <p>Expedientes</p>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item has-treeview <?php print ($intMenu == 2) ? "menu-open" : ""; ?>" style="text-align:left;">
                <a href="#" class="nav-link <?php print ($intMenu == 2) ? "active" : ""; ?>">
                    <i class="nav-icon fa fa-book"></i>
                    <p>ADMINISTRADOR<i class="fa fa-angle-left right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" style="text-align:left;">
                        <a href="area.php" class="<?php print ($strNamePage == "area.php") ? "nav-link active" : "nav-link"; ?>">
                            <i class="fa fa-circle-o nav-icon"></i>
                            <p>Área</p>
                        </a>
                    </li>
                    <li class="nav-item" style="text-align:left;">
                        <a href="categoria.php" class="<?php print ($strNamePage == "categoria.php") ? "nav-link active" : "nav-link"; ?>">
                            <i class="fa fa-circle-o nav-icon"></i>
                            <p>Categoria</p>
                        </a>
                    </li>
                    <li class="nav-item" style="text-align:left;">
                        <a href="componentes.php" class="<?php print ($strNamePage == "componentes.php") ? "nav-link active" : "nav-link"; ?>">
                            <i class="fa fa-circle-o nav-icon"></i>
                            <p>Componentes</p>
                        </a>
                    </li>
                    <li class="nav-item" style="text-align:left;">
                        <a href="identificador.php" class="<?php print ($strNamePage == "identificador.php") ? "nav-link active" : "nav-link"; ?>">
                            <i class="fa fa-circle-o nav-icon"></i>
                            <p>Tipo identificador</p>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item has-treeview <?php print ($intMenu == 3) ? "menu-open" : ""; ?>" style="text-align:left;">
                <a href="#" class="nav-link <?php print ($intMenu == 3) ? "active" : ""; ?>">
                    <i class="nav-icon fa fa-book"></i>
                    <p>REPORTERIA<i class="fa fa-angle-left right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" style="text-align:left;">
                        <a href="rpt_stock.php" class="<?php print ($strNamePage == "rpt_stock.php") ? "nav-link active" : "nav-link"; ?>">
                            <i class="fa fa-circle-o nav-icon"></i>
                            <p>Reporte de stock</p>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item has-treeview" onclick="destroSession()" style="cursor:pointer;" style="text-align:center;">
                <a class="nav-link">
                    <i class="nav-icon fa fa-sign-out"></i>
                    <p>CERRAR SESSION</p>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.sidebar-menu -->
<?php
}
?>