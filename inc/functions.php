<?php
if (!defined('TINYIB_BOARD')) { die(''); }

function cleanString($string) {
	$search = array("<", ">");
	$replace = array("&lt;", "&gt;");
	
	return str_replace($search, $replace, $string);
}

function plural($singular, $count, $plural = 's') {
	if ($plural == 's') {
        $plural = $singular . $plural;
    }
    return ($count == 1 ? $singular : $plural);
}

function threadUpdated($id) {
	rebuildThread($id);
	rebuildIndexes();
}

function newPost($parent = TINYIB_NEWTHREAD) {
	return array('parent' => $parent,
				'timestamp' => '0',
				'bumped' => '0',
				'ip' => '',
				'name' => '',
				'tripcode' => '',
				'email' => '',
				'nameblock' => '',
				'subject' => '',
				'message' => '',
				'password' => '',
				'file' => '',
				'file_hex' => '',
				'file_original' => '',
				'file_size' => '0',
				'file_size_formatted' => '',
				'image_width' => '0',
				'image_height' => '0',
				'thumb' => '',
				'thumb_width' => '0',
				'thumb_height' => '0');
}

function convertBytes($number) {
	$len = strlen($number);
	if ($len < 4) {
		return sprintf("%dB", $number);
	} elseif ($len <= 6) {
		return sprintf("%0.2fKB", $number/1024);
	} elseif ($len <= 9) {
		return sprintf("%0.2fMB", $number/1024/1024);
	}

	return sprintf("%0.2fGB", $number/1024/1024/1024);						
}

function nameAndTripcode($name) {
	if (preg_match("/(#|!)(.*)/", $name, $regs)) {
		$cap = $regs[2];
		$cap_full = '#' . $regs[2];
		
		if (function_exists('mb_convert_encoding')) {
			$recoded_cap = mb_convert_encoding($cap, 'SJIS', 'UTF-8');
			if ($recoded_cap != '') {
				$cap = $recoded_cap;
			}
		}
		
		if (strpos($name, '#') === false) {
			$cap_delimiter = '!';
		} elseif (strpos($name, '!') === false) {
			$cap_delimiter = '#';
		} else {
			$cap_delimiter = (strpos($name, '#') < strpos($name, '!')) ? '#' : '!';
		}
		
		if (preg_match("/(.*)(" . $cap_delimiter . ")(.*)/", $cap, $regs_secure)) {
			$cap = $regs_secure[1];
			$cap_secure = $regs_secure[3];
			$is_secure_trip = true;
		} else {
			$is_secure_trip = false;
		}
		
		$tripcode = "";
		if ($cap != "") { // Copied from Futabally
			$cap = strtr($cap, "&amp;", "&");
			$cap = strtr($cap, "&#44;", ", ");
			$salt = substr($cap."H.", 1, 2);
			$salt = preg_replace("/[^\.-z]/", ".", $salt);
			$salt = strtr($salt, ":;<=>?@[\\]^_`", "ABCDEFGabcdef"); 
			$tripcode = substr(crypt($cap, $salt), -10);
		}
		
		if ($is_secure_trip) {
			if ($cap != "") {
				$tripcode .= "!";
			}
			
			$tripcode .= "!" . substr(md5($cap_secure . TINYIB_TRIPSEED), 2, 10);
		}
		
		return array(preg_replace("/(" . $cap_delimiter . ")(.*)/", "", $name), $tripcode);
	}
	
	return array($name, "");
}

function nameBlock($name, $tripcode, $email, $timestamp, $rawposttext) {
	$output = '<span class="postername">';
	$output .= ($name == '' && $tripcode == '') ? 'Anonymous' : $name;
	
	if ($tripcode != '') {
		$output .= '</span><span class="postertrip">!' . $tripcode;
	}
	
	$output .= '</span>';
	
	if ($email != '' && strtolower($email) != 'noko') {
		$output = '<a href="mailto:' . $email . '">' . $output . '</a>';
	}

	return $output . $rawposttext . ' ' . date('y/m/d(D)H:i:s', $timestamp);
}

function writePage($filename, $contents) {
	$tempfile = tempnam('thread/', TINYIB_BOARD . 'tmp'); /* Create the temporary file */
	$fp = fopen($tempfile, 'w');
	fwrite($fp, $contents);
	fclose($fp);
	/* If we aren't able to use the rename function, try the alternate method */
	if (!@rename($tempfile, $filename)) {
		copy($tempfile, $filename);
		unlink($tempfile);
	}
	
	chmod($filename, 0664); /* it was created 0600 */
}

function fixLinksInRes($html) {
	$search =  array(' href="src/',    ' href="thumb/',    ' href="thread/',    ' href="imgboard.php',    ' href="favicon.ico',
	'src="thumb/',    ' action="imgboard.php',    ' href="catalog"');
	$replace = array(' href="../src/', ' href="../thumb/', ' href="../thread/', ' href="../imgboard.php', ' href="../favicon.ico',
	'src="../thumb/', ' action="../imgboard.php', ' href="../catalog"');
	
	return str_replace($search, $replace, $html);
}

function _postLink($matches) {
	$post = postByID($matches[1]);
	if ($post) {
		return '<a href="thread/' . ($post['parent'] == TINYIB_NEWTHREAD ? $post['id'] : $post['parent']) . '#' . $matches[1] . '">' . $matches[0] . '</a>';
	}
	return $matches[0];
}

function postLink($message) {
	return preg_replace_callback('/&gt;&gt;([0-9]+)/', '_postLink', $message);
}

function _cbPostLink($matches) {
	if($matches[1]=='s4s'){
		return '<a href="https://sys.4chan.org/s4s/imgboard.php?res='.$matches[2].'">' . $matches[0] . '</a>';
	}else{
		return '<a href="/'.$matches[1].'/thread/' . $matches[2] . '">' . $matches[0] . '</a>';
	}
}

function cbPostLink($message) {
	return preg_replace_callback('/&gt;&gt;&gt;\/(s|froge|s4s)\/([0-9]+)/', '_cbPostLink', $message);
}

function _cbLink($matches) {
	if($matches[1]=='s4s'){
		return '<a href="http://boards.4chan.org/s4s/">' . $matches[0] . '</a>';
	}else{
		return '<a href="/'.$matches[1].'/">' . $matches[0] . '</a>';
	}
}

function cbLink($message) {
	return preg_replace_callback('/&gt;&gt;&gt;\/(s|froge|s4s)\/(?![0-9])/', '_cbLink', $message);
}

function colorQuote($message) {
	if (substr($message, -1, 1) != "\n") { $message .= "\n"; }
	return preg_replace('/^(&gt;[^\>](.*))\n/m', '<span class="unkfunc">\\1</span>' . "\n", $message);
}

function deletePostImages($post) {
	if ($post['file'] != '') { @unlink('src/' . $post['file']); }
	if ($post['thumb'] != '') { @unlink('thumb/' . $post['thumb']); }
}

function checkBanned() {
	$ban = banByIP($_SERVER['REMOTE_ADDR']);
	if ($ban) {
		if ($ban['expire'] == 0 || $ban['expire'] > time()) {
			$expire = ($ban['expire'] > 0) ? ('<br>This ban will expire ' . date('y/m/d(D)H:i:s', $ban['expire'])) : '<br>This ban is permanent and will not expire.';
			$reason = ($ban['reason'] == '') ? '' : ('<br>Reason: ' . $ban['reason']);
			fancyDie("Look who it is again, " . $ban['ip'] . ". I'm fed up with your poop, fig. The other day when you called me a fig newton, yeah, haven't forgotten about that yet<br>Fug you, I've been on here for months and probably get on here more than you anyways. Don't you know that you make yourself look like a newfig when you call others newfigs?<br>Just because you learned how to hack your name and change it to \"Heaven\" does not give you the right to disrespect anyone at any time<br>" . $expire . $reason . "<br><br>You can appeal your ban on [s4s], not that it will guarantee your unban");
		} else {
			clearExpiredBans();
		}
	}
}

function checkFlood() {
	if (TINYIB_DELAY > 0) {
		$lastpost = lastPostByIP();
		if ($lastpost) {
			if ((time() - $lastpost['timestamp']) < TINYIB_DELAY) {
				fancyDie("Please wait a moment before posting again. You will be able to make another post in " . (TINYIB_DELAY - (time() - $lastpost['timestamp'])) . " " . plural("second", (TINYIB_DELAY - (time() - $lastpost['timestamp']))));
			}
		}
	}
}

function checkMessageSize() {
	if (strlen($_POST["message"]) > 3000) {
		fancyDie("Please shorten your message, or post it in multiple parts. Your message is " . strlen($_POST["message"]) . " characters long, and the maximum allowed is 3000");
	}
}

function manageCheckLogIn() {
	$loggedin = false; $isadmin = false;
	/*if (isset($_POST['password'])) {
		if ($_POST['password'] == TINYIB_ADMINPASS) {
			$_SESSION['tinyib'] = TINYIB_ADMINPASS;
		} elseif (TINYIB_MODPASS != '' && $_POST['password'] == TINYIB_MODPASS) {
			$_SESSION['tinyib'] = TINYIB_MODPASS;
		}
	}
	
	if (isset($_SESSION['tinyib'])) {
		if ($_SESSION['tinyib'] == TINYIB_ADMINPASS) {
			$loggedin = true;
			$isadmin = true;
		} elseif (TINYIB_MODPASS != '' && $_SESSION['tinyib'] == TINYIB_MODPASS) {
			$loggedin = true;
		}
	}*/
	if(hash('ripemd160',getenv('REMOTE_ADDR'))==TINYIB_MODPASS){
		$loggedin=true;
		$isadmin=true;
	}
	
	return array($loggedin, $isadmin);
}

function setParent() {
	if (isset($_POST["parent"])) {
		if ($_POST["parent"] != TINYIB_NEWTHREAD) {
			if (!threadExistsByID($_POST['parent'])) {
				fancyDie("Invalid parent thread ID supplied, unable to create post");
			}
			
			return $_POST["parent"];
		}
	}
	
	return TINYIB_NEWTHREAD;
}

function isRawPost() {
	if (isset($_POST['rawpost'])) {
		list($loggedin, $isadmin) = manageCheckLogIn();
		if ($loggedin) {
			return true;
		}
	}
	
	return false;
}

function validateFileUpload() {
	switch ($_FILES['file']['error']) {
		case UPLOAD_ERR_OK:
			break;
		case UPLOAD_ERR_FORM_SIZE:
			fancyDie("That file is larger than " . TINYIB_MAXKBDESC);
			break;
		case UPLOAD_ERR_INI_SIZE:
			fancyDie("The uploaded file exceeds the upload_max_filesize directive (" . ini_get('upload_max_filesize') . ") in php.ini");
			break;
		case UPLOAD_ERR_PARTIAL:
			fancyDie("The uploaded file was only partially uploaded");
			break;
		case UPLOAD_ERR_NO_FILE:
			fancyDie("No file was uploaded");
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			fancyDie("Missing a temporary folder");
			break;
		case UPLOAD_ERR_CANT_WRITE:
			fancyDie("Failed to write file to disk");
			break;
		default:
			fancyDie("Unable to save the uploaded file");
	}
}

function checkDuplicateImage($hex) {
	$hexmatches = postsByHex($hex);
	if (count($hexmatches) > 0) {
		foreach ($hexmatches as $hexmatch) {
			fancyDie("That file has already been posted <a href=\"thread/" . (($hexmatch["parent"] == TINYIB_NEWTHREAD) ? $hexmatch["id"] : $hexmatch["parent"]) . "#" . $hexmatch["id"] . "\">here</a>");
		}
	}
}

function thumbnailDimensions($post) {
	if ($post['parent'] == TINYIB_NEWTHREAD) {
		$max_width = TINYIB_MAXWOP;
		$max_height = TINYIB_MAXHOP;
	} else {
		$max_width = TINYIB_MAXW;
		$max_height = TINYIB_MAXH;
	}
	return ($post['image_width'] > $max_width || $post['image_height'] > $max_height) ? array($max_width, $max_height) : array($post['image_width'], $post['image_height']);
}

function createThumbnail($name, $filename, $new_w, $new_h) {
	$system = explode(".", $filename);
	$system = array_reverse($system);
	
	$src_img = @imagecreatefrompng($name);
	if(!$src_img){
		$src_img = @imagecreatefromgif($name);
		if(!$src_img){
			$src_img = @imagecreatefromjpeg($name);
			if(!$src_img){
				return false;
			}
		}
	}
	
	$old_x = imageSX($src_img);
	$old_y = imageSY($src_img);
	if($old_x>2500||$old_y>2500){
		imagedestroy($src_img);
		fancyDie("Maximum allowed resolution: 2500x2500",$name);
		return false;
	}
	$percent = ($old_x > $old_y) ? ($new_w / $old_x) : ($new_h / $old_y);
	$thumb_w = round($old_x * $percent);
	$thumb_h = round($old_y * $percent);
	if($thumb_w<1){
		$thumb_w=1;
	}
	if($thumb_h<1){
		$thumb_h=1;
	}
	
	$dst_img = ImageCreateTrueColor($thumb_w, $thumb_h);
	fastImageCopyResampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
	
	if (preg_match("/png/", $system[0])) {
		if (!imagepng($dst_img, $filename)) {
			return false;
		}
	} else if (preg_match("/jpe?g/", $system[0])) {
		if (!imagejpeg($dst_img, $filename, 70)) {
			return false;
		}
	} else if (preg_match("/gif/", $system[0])) {
		if (!imagegif($dst_img, $filename)) { 
			return false;
		}
	}
	
	imagedestroy($dst_img);
	imagedestroy($src_img);
	
	return true;
}

function fastImageCopyResampled(&$dst_image, &$src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3) {
	// Author: Tim Eckel - Date: 12/17/04 - Project: FreeRingers.net - Freely distributable. 
	if (empty($src_image) || empty($dst_image)) { return false; }

	if ($quality <= 1) {
		$temp = imagecreatetruecolor ($dst_w + 1, $dst_h + 1);
		
		imagecopyresized ($temp, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w + 1, $dst_h + 1, $src_w, $src_h);
		imagecopyresized ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $dst_w, $dst_h);
		imagedestroy ($temp);
	} elseif ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
		$tmp_w = $dst_w * $quality;
		$tmp_h = $dst_h * $quality;
		$temp = imagecreatetruecolor ($tmp_w + 1, $tmp_h + 1);
		
		imagecopyresized ($temp, $src_image, $dst_x * $quality, $dst_y * $quality, $src_x, $src_y, $tmp_w + 1, $tmp_h + 1, $src_w, $src_h);
		imagecopyresampled ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $tmp_w, $tmp_h);
		imagedestroy ($temp);
	} else {
		imagecopyresampled ($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
	}
	
	return true;
}

function strallpos($haystack, $needle, $offset = 0) {
	$result = array();
	for ($i = $offset;$i<strlen($haystack);$i++) {
		$pos = strpos($haystack, $needle, $i);
		if ($pos !== False) {
			$offset = $pos;
			if ($offset >= $i) {
				$i = $offset;
				$result[] = $offset;
			}
		}
	}
	return $result;
}

?>
