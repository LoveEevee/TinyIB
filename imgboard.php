<?php
# TinyIB
#
# https://github.com/tslocum/TinyIB

error_reporting(E_ALL);
ini_set("display_errors", 1);
session_start();
ob_implicit_flush();
ob_end_flush();

if (get_magic_quotes_gpc()) {
	foreach ($_GET as $key => $val) { $_GET[$key] = stripslashes($val); }
	foreach ($_POST as $key => $val) { $_POST[$key] = stripslashes($val); }
}
if (get_magic_quotes_runtime()) { set_magic_quotes_runtime(0); }

function fancyDie($message,$imageupload=false) {
	$boardletter=TINYIB_BOARD;
	$boarddesc=TINYIB_BOARDDESC;
	$pagetitle="/$boardletter/ - $boarddesc";
	$dietext=<<<EOF
<!doctype html><html><head><title>$pagetitle</title><style>html,body{height:100%;margin:0}body{background:linear-gradient(to bottom,#fed6af 0,#ffe 200px) no-repeat;background-color:#ffe;font:20px sans-serif;color:#800000;text-align:center;display:flex;flex-direction:column;justify-content:center;align-items:center}.error{background-color:#f0e0d6;padding:7px;border:1px solid #d9bfb7;border-left:0;border-top:0;width:700px;margin:20px 0}</style></head><body><div class="error">$message</div><a href="javascript:history.go(-1)">Click here to go back</a></body></html>
EOF;
	if($imageupload){
		unlink($imageupload);
	}
	die(preg_replace("/\s+/S"," ",$dietext));
}

if (!file_exists('settings.php')) {
	fancyDie('Please rename the file settings.default.php to settings.php');
}
require 'settings.php';

$echohead='';
$echobody='';
function iecho($text='',$ishead=0){
	global $echohead,$echobody;
	if($ishead){
		$echohead.=$text;
	}else{
		$echobody.=$text;
	}
}

// Check directories are writable by the script
$writedirs = array("thread", "src", "thumb");
if (TINYIB_DBMODE == 'flatfile') { $writedirs[] = "inc/flatfile"; }
foreach ($writedirs as $dir) {
	if (!is_writable($dir)) {
		fancyDie("Directory '" . $dir . "' can not be written to.  Please modify its permissions.");
	}
}

$includes = array("inc/defines.php", "inc/functions.php", "inc/html.php");

if (in_array(TINYIB_DBMODE, array('flatfile', 'mysql', 'sqlite'))) {
	$includes[] = 'inc/database_' . TINYIB_DBMODE . '.php';
} else {
	fancyDie("Unknown database mode specified");
}

foreach ($includes as $include) {
	include $include;
}

// if (TINYIB_TRIPSEED == '' || TINYIB_ADMINPASS == '') {
// 	fancyDie('TINYIB_TRIPSEED and TINYIB_ADMINPASS must be configured');
// }

$redirect = true;
// Check if the request is to make a post
if(isset($_POST['password'])){
	$postpass=$_POST['password'];
}else{
	$postpass='';
}
if (isset($_POST['message']) || isset($_POST['file'])) {
	list($loggedin, $isadmin) = manageCheckLogIn();
	$rawpost = isRawPost();
	if (!$loggedin) {
		checkBanned();
		checkMessageSize();
		checkFlood();
	}
	
	$post = newPost(setParent());
	$post['ip'] = $_SERVER['REMOTE_ADDR'];
	
	list($post['name'], $post['tripcode']) = nameAndTripcode($_POST['name']);
	
	if($post['tripcode']=='4X8vLLNDE2'){
		$fortuned=true;
	}else{
		$fortuned=false;
	}
	
	$post['tripcode'] = '';
	
	$post['name'] = cleanString(substr($post['name'], 0, 75));
	//$post['email'] = cleanString(str_replace('"', '&quot;', substr($_POST['email'], 0, 75)));
	//$post['subject'] = cleanString(substr($_POST['subject'], 0, 75));
	$post['email'] = '';
	$post['subject'] = '';
	
	if ($rawpost) {
		$rawposttext = ($isadmin) ? ' <span class="afroge">## A froge</span>' : '';
		//$post['message'] = $_POST['message']; // Treat message as raw HTML
	}else {
		$rawposttext = '';
	}
	$post['message'] = str_replace("\n", '<br>', colorQuote(cbLink(cbPostLink(postLink(cleanString(rtrim($_POST['message'])))))));
	$cleanpost=preg_replace('/<[^>]*>|\s+/i', '', $post['message']);
	
	$rnum=rand(0,12);
	$fortunes=array(
		array("F51C6A","Your fortune: Reply hazy, try again"),
		array("FD4D32","Your fortune: Excellent Luck"),
		array("E7890C","Your fortune: Good Luck"),
		array("BAC200","Your fortune: Average Luck"),
		array("7FEC11","Your fortune: Very Bad Luck"),
		array("43FD3B","Your fortune: Good news will come to you by mail"),
		array("16F174","Your fortune: &#xFF08;&#x3000;&#180;_&#x309D;`&#xFF09;&#xFF8C;&#xFF70;&#xFF9D;"),
		array("00CBB0","Your fortune: &#xFF77;&#xFF80;&#x2501;&#x2501;&#x2501;&#x2501;&#x2501;&#x2501;(&#xFF9F;&#8704;&#xFF9F;)&#x2501;&#x2501;&#x2501;&#x2501;&#x2501;&#x2501; !!!!"),
		array("0893E1","Your fortune: You will meet a dark handsome stranger"),
		array("2A56FB","Your fortune: Better not tell you now"),
		array("6023F8","Your fortune: Outlook good"),
		array("9D05DA","Your fortune: Bad Luck"),
		array("D302A7","Your fortune: Godly Luck")
	);
	if($fortuned){
		$post['message'] .= '<div class="fortune" style="color:#'.$fortunes[$rnum][0].'">'.$fortunes[$rnum][1].'</div>';
	}
	//}
	$post['password'] = ($postpass != '') ? md5(md5($postpass)) : '';
	$post['nameblock'] = nameBlock($post['name'], $post['tripcode'], $post['email'], time(), $rawposttext);
	
	$nametr = strtolower(preg_replace('/[^a-z\\d]/i','',$post['name']));
	$post['name'] = trim($post['name']);
	
	if (TINYIB_BOARD == 'froge' && ($nametr == '' || $nametr == 'anonymous' || $nametr == 'anon' )){
		fancyDie("A name is required :^)");
	}elseif ($nametr == '' || $nametr == 'anonymous'){
		$post['name'] = 'Anonymous';
	}
	
	if (isset($_FILES['file'])) {
		if ($_FILES['file']['name'] != "") {
			validateFileUpload();
			
			if (!is_file($_FILES['file']['tmp_name']) || !is_readable($_FILES['file']['tmp_name'])) {
				fancyDie("File transfer failure, please go back and try again");
			}
			
			if ((TINYIB_MAXKB > 0) && (filesize($_FILES['file']['tmp_name']) > (TINYIB_MAXKB * 1024))) {
				fancyDie("Your file is larger than " . TINYIB_MAXKBDESC);
			}
			
			if(substr($_FILES['file']['name'], 0, 50)!=$_FILES['file']['name']){
				fancyDie("That's a really nice file name, you might want to change it to upload your picture though");
			}
			
			$post['file_original'] = htmlentities($_FILES['file']['name'], ENT_QUOTES);
			$post['file_hex'] = md5_file($_FILES['file']['tmp_name']);
			$post['file_size'] = $_FILES['file']['size'];
			$post['file_size_formatted'] = convertBytes($post['file_size']);
			$file_type = strtolower(preg_replace('/.*(\..+)/', '\1', $_FILES['file']['name'])); if ($file_type == '.jpeg') { $file_type = '.jpg'; }
			$file_name = time() . substr(microtime(), 2, 3);
			$post['file'] = $file_name . $file_type;
			$post['thumb'] = $file_name . "s" . $file_type;
			$file_location = "src/" . $post['file'];
			$thumb_location = "thumb/" . $post['thumb'];
			
			if (!($file_type == '.jpg' || $file_type == '.gif' || $file_type == '.png')) {
				fancyDie("Only GIF, JPG, and PNG files are allowed");
			}
			
			if (!@getimagesize($_FILES['file']['tmp_name'])) {
				fancyDie("Failed to read the size of the uploaded file");
			}
			$file_info = getimagesize($_FILES['file']['tmp_name']);
			$file_mime = $file_info['mime'];
			
			if (!($file_mime == "image/jpeg" || $file_mime == "image/gif" || $file_mime == "image/png")) {
				fancyDie("Only GIF, JPG, and PNG files are allowed");
			}

			checkDuplicateImage($post['file_hex']);
			
			list($iwidth,$iheight)=getimagesize($_FILES['file']['tmp_name']);
			if($iwidth>2500||$iheight>2500){
				fancyDie("Maximum allowed resolution: 2500x2500");
			}
			
			if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_location)) {
				fancyDie("Could not copy uploaded file");
			}
			
			if ($_FILES['file']['size'] != filesize($file_location)) {
				fancyDie("File transfer failure, please go back and try again",$file_location);
			}
			
			$post['image_width'] = $file_info[0]; $post['image_height'] = $file_info[1];
			
			list($thumb_maxwidth, $thumb_maxheight) = thumbnailDimensions($post);
			
			if($file_mime == "image/png"||$file_mime == "image/gif"){
				$img = @imagecreatefrompng($file_location);
				if(!$img){
					$img = @imagecreatefromgif($file_location);
					if(!$img){
						$img = @imagecreatefromjpeg($file_location);
						if(!$img){
							fancyDie("Could not create thumbnail",$file_location);
						}
					}
				}
				$width = $post['image_width'];
				$height = $post['image_height'];
				$backgroundImg = @imagecreatetruecolor($width, $height);
				if($post['parent'] != TINYIB_NEWTHREAD){
					$color = imagecolorallocate($backgroundImg, 240, 224, 214);
				}else{
					$color = imagecolorallocate($backgroundImg, 255, 255, 238);
				}
				imagefill($backgroundImg, 0, 0, $color);
				imagecopy($backgroundImg, $img, 0, 0, 0, 0, $width, $height);
				if($file_mime == "image/png"){
					imagepng($backgroundImg, $thumb_location, 0);
				}else{
					imagegif($backgroundImg, $thumb_location, 0);
				}
				if (!createThumbnail($thumb_location, $thumb_location, $thumb_maxwidth, $thumb_maxheight)){
					fancyDie("Could not create thumbnail",$file_location);
				}
			}else{
				if (!createThumbnail($file_location, $thumb_location, $thumb_maxwidth, $thumb_maxheight)) {
					fancyDie("Could not create thumbnail",$file_location);
				}
			}
			
			$thumb_info = getimagesize($thumb_location);
			$post['thumb_width'] = $thumb_info[0]; $post['thumb_height'] = $thumb_info[1];
		}
	}
	
	if ($post['file'] == '') { // No file uploaded
		if ($post['parent'] == TINYIB_NEWTHREAD&&$cleanpost!='') {
			fancyDie("An image is required to start a thread");
		}
		if ($cleanpost=='') {
			fancyDie('<div class="fortune" style="color:#'.$fortunes[$rnum][0].'">'.$fortunes[$rnum][1].'</div>');
		}
		iecho('Posted!');
	} else {
		iecho($post['file_original'] . ' uploaded!');
	}
	
	$post['id'] = insertPost($post);
	//if (strtolower($post['email']) == 'noko') {
	$redirect = 'thread/' . ($post['parent'] == TINYIB_NEWTHREAD ? $post['id'] : $post['parent']) . '#' . $post['id'];
	//}
	
	if (TINYIB_BOARD=='froge' && strtolower($cleanpost)=='froge'){
		$echohead='';
		$echobody='<a href="' . $redirect . '"><img src="/chan/frogepost.png" width="264" height="219"></a>';
	}
	
	trimThreads();
	
	//echo 'Updating thread...<br>';
	if ($post['parent'] != TINYIB_NEWTHREAD) {
		rebuildThread($post['parent']);
		
		//if (strtolower($post['email']) != 'sage') {
		if (TINYIB_MAXREPLIES == 0 || numRepliesToThreadByID($post['parent']) <= TINYIB_MAXREPLIES) {
			bumpThreadByID($post['parent']);
		}
		//}
	} else {
		rebuildThread($post['id']);
	}
	
	if (TINYIB_BOARD=='froge' && strtolower($cleanpost)=='froge'){
		$redirect='';
	}
	
	//echo 'Updating index...<br>';
	rebuildIndexes();
// Check if the request is to delete a post and/or its associated image
} elseif (isset($_GET['delete']) && !isset($_GET['manage'])) {
	if (!isset($_POST['delete'])) {
		fancyDie('You need to tick a box next to the post you want to delete');
	}
	$post = postByID($_POST['delete']);
	if ($post) {
		list($loggedin, $isadmin) = manageCheckLogIn();
		if ($loggedin) {
			// Redirect to post moderation page
			iecho('<meta http-equiv="refresh" content="0;url=' . basename($_SERVER['PHP_SELF']) . '?manage&moderate=' . $_POST['delete'] . '">',1);
		} elseif ($post['password'] != '' && md5(md5($postpass)) == $post['password']) {
			deletePostByID($post['id']);
			if ($post['parent'] == TINYIB_NEWTHREAD) {
				threadUpdated($post['id']);
			} else {
				threadUpdated($post['parent']);
			}
			fancyDie('Your shame has been erased from the internet');
		} else {
			fancyDie("Cannot delete the post because password doesn't match");
		}
	} else {
		fancyDie('The post was already deleted');
	}

	$redirect = false;
// Check if the request is to access the management area
} elseif (isset($_GET['manage'])) {
	$text = ''; $onload = ''; $navbar = '&nbsp;';
	$redirect = false; $loggedin = false; $isadmin = false;
	$returnlink = basename($_SERVER['PHP_SELF']);
	
	list($loggedin, $isadmin) = manageCheckLogIn();
	
	if ($loggedin) {
		if ($isadmin) {
			if (isset($_GET['rebuildall'])) {
				$allthreads = allThreads();
				foreach ($allthreads as $thread) {
					rebuildThread($thread['id']);
				}
				rebuildIndexes();
				$text .= manageInfo('Rebuilt board.');
			} elseif (isset($_GET['bans'])) {
				clearExpiredBans();
				
				if (isset($_POST['ip'])) {
					if ($_POST['ip'] != '') {
						$banexists = banByIP($_POST['ip']);
						if ($banexists) {
							fancyDie('Sorry, there is already a ban on record for that IP address');
						}
						
						$ban = array();
						$ban['ip'] = $_POST['ip'];
						$ban['expire'] = ($_POST['expire'] > 0) ? (time() + $_POST['expire']) : 0;
						$ban['reason'] = $_POST['reason'];
						
						insertBan($ban);
						$text .= manageInfo('Ban record added for ' . $ban['ip']);
					}
				} elseif (isset($_GET['lift'])) {
					$ban = banByID($_GET['lift']);
					if ($ban) {
						deleteBanByID($_GET['lift']);
						$text .= manageInfo('Ban record lifted for ' . $ban['ip']);
					}
				}
				
				$onload = manageOnLoad('bans');
				$text .= manageBanForm();
				$text .= manageBansTable();
			} else if (isset($_GET['update'])) {
				if (is_dir('.git')) {
					$git_output = shell_exec('git pull 2>&1');
					$text .= '<blockquote class="reply" style="padding: 7px;font-size: 1.25em;">
					<pre style="margin: 0px;padding: 0px;">Attempting update...' . "\n\n" . $git_output . '</pre>
					</blockquote>
					<p><b>Note:</b> If TinyIB updates and you have made custom modifications, <a href="https://github.com/tslocum/TinyIB/commits/master">review the changes</a> which have been merged into your installation.
					Ensure that your modifications do not interfere with any new/modified files.
					See the <a href="https://github.com/tslocum/TinyIB#readme">README</a> for more information.</p>';
				} else {
					$text .= '<p><b>TinyIB was not installed via Git.</b></p>
					<p>If you installed TinyIB without Git, you must <a href="https://github.com/tslocum/TinyIB">update manually</a>.  If you did install with Git, ensure the script has read and write access to the <b>.git</b> folder.</p>';
				}
			}
		}
		
		if (isset($_GET['delete'])) {
			$post = postByID($_GET['delete']);
			if ($post) {
				deletePostByID($post['id']);
				rebuildIndexes();
				if ($post['parent'] != TINYIB_NEWTHREAD) {
					rebuildThread($post['parent']);
				}
				$text .= manageInfo('Post No.' . $post['id'] . ' deleted.');
			} else {
				fancyDie("Sorry, there doesn't appear to be a post with that ID.");
			}
		} elseif (isset($_GET['moderate'])) {
			if ($_GET['moderate'] > 0) {
				$post = postByID($_GET['moderate']);
				if ($post) {
					$text .= manageModeratePost($post);
				} else {
					fancyDie("Sorry, there doesn't appear to be a post with that ID.");
				}
			} else {
				$onload = manageOnLoad('moderate');
				$text .= manageModeratePostForm();
			}
		} elseif (isset($_GET["rawpost"])) {
			$onload = manageOnLoad("rawpost");
			$text .= manageRawPostForm();
		} elseif (isset($_GET["logout"])) {
			$_SESSION['tinyib'] = '';
			session_destroy();
			die('<meta http-equiv="refresh" content="0;url=' . $returnlink . '?manage">');
		}
		if ($text == '') {
			$text = manageStatus();
		}
	} else {
		fancyDie("You're not an admin and you will never be, silly");
	}
	echo managePage($text, $onload);
} elseif (!file_exists('1') || countThreads() == 0) {
	rebuildIndexes();
}

if ($redirect) {
	iecho('<meta http-equiv="refresh" content="0;url=' . (is_string($redirect) ? $redirect : '1') . '">',1);
}

if($echohead||$echobody){
	$boardletter=TINYIB_BOARD;
	$boarddesc=TINYIB_BOARDDESC;
	$pagetitle="/$boardletter/ - $boarddesc";
	$toecho="<!doctype html><html><head><title>$pagetitle</title>$echohead";
	if($echobody){
		$toecho.="<style>html,body{height:100%;margin:0}body{display:flex;justify-content:center;align-items:center;background:linear-gradient(to bottom,#fed6af 0,#ffe 200px) no-repeat;background-color:#ffe;font:bold 50px sans-serif;color:#800000}</style>";
	}
echo preg_replace("/\s+/S"," ",$toecho . "</head><body>$echobody</body></html>");
}

?>
