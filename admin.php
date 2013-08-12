<?PHP
error_reporting(E_ALL);
define('IN_ADMINCP', true);
define('NOROBOT', true);

include'./source/class/core.class.php';


$admincp = new LamosAdmincp();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if(empty($action)){
	$admincp->show_admincp_main();
}