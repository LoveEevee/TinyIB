<?
require 'settings.php';
include 'inc/functions.php';
if (in_array(TINYIB_DBMODE, array('flatfile', 'mysql', 'sqlite'))) {
	$includes[] = 'inc/database_' . TINYIB_DBMODE . '.php';
} else {
	die("Unknown database mode specificed");
}
$matches=preg_replace("/\\.html$/","",pathinfo($_SERVER["REQUEST_URI"],PATHINFO_BASENAME));
$boardletter=TINYIB_BOARD;
if(ctype_digit($matches)){
	$post=postByID($matches);
	if($post){
		$thread=$post['parent']==0?$post['id']:$post['parent'];
		echo "<meta http-equiv=\"refresh\" content=\"0;url=/$boardletter/thread/$thread#$matches\">";
	}else{
		$boarddesc=TINYIB_BOARDDESC;
		$dietext=<<<EOF
<!doctype html><html><head><title>/$boardletter/ - $boarddesc</title><style>html,body{height:100%;margin:0}body{background:linear-gradient(to bottom,#fed6af 0,#ffe 200px) no-repeat;background-color:#ffe;font:20px sans-serif;color:#800000;text-align:center;display:flex;flex-direction:column;justify-content:center;align-items:center}.error{background-color:#f0e0d6;padding:7px;border:1px solid #d9bfb7;border-left:0;border-top:0;width:700px;margin:20px 0}</style></head><body><div class="error">The post you are looking for does not exist</div><a href="javascript:history.go(-1)">Click here to go back</a></body></html>
EOF;
		die($dietext);
	}
}else{
	include $_SERVER['DOCUMENT_ROOT'] . '/error.php';
}
?>