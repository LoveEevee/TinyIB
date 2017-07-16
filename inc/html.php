<?php
if (!defined('TINYIB_BOARD')) { die(''); }

$hasposts = 0;

/* Page header (start) */
function pageHeader($fortitle=0) {
	$boardletter=TINYIB_BOARD;
	$boarddesc=TINYIB_BOARDDESC;
	if($fortitle){
		$pagetitle="/$boardletter/ - $fortitle - $boarddesc";
	}else{
		$pagetitle="/$boardletter/ - $boarddesc";
	}
	$return = <<<EOF
<!doctype html>
<html>
	<head>
		<title>$pagetitle</title>
		<link rel="stylesheet" type="text/css" href="/chan/global.css">
		<meta http-equiv="content-type" content="text/html;charset=UTF-8">
		<meta http-equiv="pragma" content="no-cache">
		<meta http-equiv="expires" content="-1">
		<link rel="alternate" title="Latest posts" href="/$boardletter/posts.rss" type="application/rss+xml">
		<link rel="icon" href="/favicon.ico">
	</head>
EOF;
	return $return;
}

/* Page footer (end) */
function pageFooter() {
	/* If the footer is removed from the page, please link to TinyIB somewhere on the site. */
	return <<<EOF
		<script src="/chan/global.js"></script>
		<div class="footer">
			<a href="https://github.com/tslocum/TinyIB" target="_top">tinyib</a>
		</div>
	</body>
</html>
EOF;
}

/* Post reply */
function buildPost($post, $res) {
	$return = "";
	$threadid = ($post['parent'] == TINYIB_NEWTHREAD) ? $post['id'] : $post['parent'];
	$postlink = ($res == TINYIB_RESPAGE) ? ($threadid . '#' . $post['id']) : ('thread/' . $threadid . '#' . $post['id']);
	if (!isset($post["omitted"])) { $post["omitted"] = 0; }
	
	if ($post["parent"] == TINYIB_NEWTHREAD) {
		$return .= '<div class="thread" id="'.$threadid.'">';
	}
	
	if ($post["parent"] != TINYIB_NEWTHREAD) {
		$return .= <<<EOF
<div class="reply" id="${post["id"]}">
EOF;
	} elseif ($post["file"] != "") {
		$return .= <<<EOF
<div class="filesize">
File: <a href="src/${post["file"]}">${post["file_original"]}</a> (${post["file_size_formatted"]}, ${post["image_width"]}x${post["image_height"]})
<a target="_blank" href="src/${post["file"]}" class="thumblink"><img src="thumb/${post["thumb"]}" alt="${post["id"]}" class="thumb" width="${post["thumb_width"]}" height="${post["thumb_height"]}"></a>
</div>
EOF;
	}
	
	$return .= <<<EOF
<div class="postInfo">
<input type="checkbox" name="delete" value="${post['id']}">
EOF;

/*if ($post['subject'] != '') {
	$return .= '	<span class="filetitle">' . $post['subject'] . '</span> ';
}*/

$nametime=$post["nameblock"];
$splitnum=strrpos($nametime,"</span> ")+8;
$nameonly=substr($nametime,0,$splitnum);
$timeonly='<span class="timestamp" numbers="'.$post['timestamp'].'">'.substr($nametime,$splitnum).'</span>';

$return .= <<<EOF
${nameonly} ${timeonly}
<span class="reflink">
	<a href="$postlink">No.</a><a href="javascript:quote('${post["id"]}')">${post["id"]}</a>
</span>
EOF;
	if ($post['parent'] == TINYIB_NEWTHREAD && $res == TINYIB_INDEXPAGE) {
		$return .= "<span class=\"replylink\">&nbsp;[<a href=\"thread/${post["id"]}\">Reply</a>]</span>";
	}
	$return .= '</div>';
	
	if ($post['parent'] != TINYIB_NEWTHREAD && $post["file"] != "") {
		$return .= <<<EOF
<div class="filesize"><a href="src/${post["file"]}">${post["file_original"]}</a> (${post["file_size_formatted"]}, ${post["image_width"]}x${post["image_height"]})
<a target="_blank" href="src/${post["file"]}" class="thumblink"><img src="thumb/${post["thumb"]}" alt="${post["id"]}" class="thumb" width="${post["thumb_width"]}" height="${post["thumb_height"]}"></a>
</div>
EOF;
	}
	
	
	if (TINYIB_TRUNCATE > 0 && !$res && substr_count($post['message'], '<br>') > TINYIB_TRUNCATE) { // Truncate messages on board index pages for readability
		$br_offsets = strallpos($post['message'], '<br>');
		$post['message'] = substr($post['message'], 0, $br_offsets[TINYIB_TRUNCATE - 1]);
		$post['message'] .= "<br><span class=\"omittedposts\">Post truncated. Click <a href=\"".$postlink."\">here</a> to view.</span><br>";
	}
	$return .= <<<EOF
<div class="message">
${post["message"]}
</div>
EOF;

	if ($post['parent'] == TINYIB_NEWTHREAD) {
		if ($res == TINYIB_INDEXPAGE && $post['omitted'] > 0) {
			$return .= '<span class="omittedposts">' . $post['omitted'] . ' ' . plural('post', $post['omitted']) . " omitted. Click <a href=\"thread/${post["id"]}\">here</a> to view.</span>";
		}
	} else {
		$return .= <<<EOF
</div>
EOF;
	}
	
	return preg_replace("/\s+/S"," ",$return);
}

/* Page links */
function buildPage($htmlposts, $parent, $pages=0, $thispage=0, $fortitle=0) {
	$managelink = basename($_SERVER['PHP_SELF']) . "?manage";
	$maxdimensions = TINYIB_MAXWOP . 'x' . TINYIB_MAXHOP;
	if (TINYIB_MAXW != TINYIB_MAXWOP || TINYIB_MAXH != TINYIB_MAXHOP) {
		$maxdimensions .= ' (new thread) or ' . TINYIB_MAXW . 'x' . TINYIB_MAXH . ' (reply)';
	}
	
	$postingmode = "";
	$pagenavigator = "";
	if ($parent == TINYIB_NEWTHREAD) {
		$pages = max($pages, 0);
		$previous = $thispage;
		$next = $thispage + 2;
		
		$pagelinks = ($thispage == 0) ? "<input type=\"button\" disabled value=\"&lt;&lt;\">" : '<form method="get" action="' . $previous . '" style="display:inline"><input value="&lt;&lt;" type="submit"></form>';
		
		for ($i = 0;$i <= $pages;$i++) {
			$href = $i + 1;
			if ($thispage == $i) {
				$pagelinks .= '&#91;' . $href . '&#93; ';
			} else {
				$pagelinks .= '&#91;<a href="' . $href . '">' . $href . '</a>&#93; ';
			}
		}
		
		$pagelinks .= ($pages <= $thispage) ? "<input type=\"button\" disabled value=\"&gt;&gt;\">" : '<form method="get" action="' . $next . '" style="display:inline"><input value="&gt;&gt;" type="submit"></form>';
		
		$pagenavigator = <<<EOF
	<div class="pagelinks">
		$pagelinks
	</div>
EOF;
	} else {
		$postingmode = '&#91;<a href="../">Return</a>&#93;';
	}
	
	$unique_posts_html = '';
	$unique_posts = uniquePosts();
	if ($unique_posts > 0) {
		$unique_posts_html = "<li>Currently $unique_posts unique user posts.</li>\n";
	}
	
	$max_file_size_input = '';
	$max_file_size_html = '';
	if (TINYIB_MAXKB > 0) {
		$max_file_size_input_html = '<input type="hidden" name="MAX_FILE_SIZE" value="' . strval(TINYIB_MAXKB * 1024) . '">';
		$max_file_size_rules_html = '<li>Maximum file size allowed is ' . TINYIB_MAXKBDESC . '</li>';
	}
	
/* Start of body content */
	$boardletter=TINYIB_BOARD;
	$boarddesc=TINYIB_BOARDDESC;
	$body = <<<EOF
	<body id="$boardletter">
		<div class="boardlist">
			[<a href="/">home</a>] [<a href="/s/">s </a>/<a href="/froge/"> froge</a>] [<a href="/dots/">dots </a>/<a href="/viruse/"> viruse</a>]
		</div>
		<div class="logo">
			/$boardletter/ - $boarddesc
			<div class="cataloglink">[<a href="catalog">catalog</a>]</div>
		</div>
		<div class="postarea">
			<form name="postform" id="postform" action="imgboard.php" method="post" enctype="multipart/form-data">
				$max_file_size_input
				<input type="hidden" name="parent" value="$parent">
				<input type="hidden" name="password" value="">
				<div>
					<input type="text" name="name" maxlength="75" placeholder="Name">
					<input type="submit" value="Submit">
				</div>
				<div>
					<textarea name="message" cols="48" rows="4" placeholder="Comment"></textarea>
				</div>
				<div>
					<input type="file" name="file" size="35">
				</div>
				<div class="rules">
					<ul>
						<li>Supported file types are GIF, JPG, and PNG</li>
						$max_file_size_rules_html
						<li>Images greater than $maxdimensions will be thumbnailed</li>
						<li>NSFW images are not allowed to be posted</li>
						$unique_posts_html
					</ul>
				</div>
			</form>
		</div>
		<form id="delform" action="imgboard.php?delete" method="post">
			<input type="hidden" name="board" value="$boardletter">
			<input type="hidden" name="password" value="">
			$htmlposts
			<table class="userdelete">
				<tbody>
					<tr>
						<td>
							Delete Post:
							<input name="deletepost" value="Delete" type="submit"> 
						</td>
					</tr>
				</tbody>
			</table>
		</form>
		$pagenavigator
		<br>
EOF;
	return preg_replace("/\s+/S"," ",pageHeader($fortitle) . $body . pageFooter());
}

/* Admin panel pages from this point on */
function rebuildIndexes() {
	$page = 0; $i = 0; $htmlposts = '';
	$threads = allThreads(); 
	$pages = ceil(count($threads) / TINYIB_THREADSPERPAGE) - 1;
	
	foreach ($threads as $thread) {
		$replies = postsInThreadByID($thread['id']);
		$thread['omitted'] = max(0, count($replies) - TINYIB_PREVIEWREPLIES - 1);
		
		// Build replies for preview
		$htmlreplies = array();
		for ($j = count($replies) - 1; $j > $thread['omitted']; $j--) {
			$htmlreplies[] = buildPost($replies[$j], TINYIB_INDEXPAGE);
		}
		
		$htmlposts .= buildPost($thread, TINYIB_INDEXPAGE) . implode('', array_reverse($htmlreplies)) . "</div>\n";
		
		if (++$i >= TINYIB_THREADSPERPAGE) {
			$file = $page+1;
			if($file==1){
				$pagefile=0;
			}else{
				$pagefile="Page $file";
			}
			writePage($file, buildPage($htmlposts, 0, $pages, $page,$pagefile));
			
			$page++; $i = 0; $htmlposts = '';
		}
	}
	
	if ($page == 0 || $htmlposts != '') {
		$file = $page+1;
		if($file==1){
			$pagefile=0;
		}else{
			$pagefile="Page $file";
		}
		writePage($file, buildPage($htmlposts, 0, $pages, $page,$pagefile));
	}
	
	writePage('posts.rss', buildRSS());
	writePage('catalog', buildCatalog($threads));
}

function rebuildThread($id) {
	$htmlposts = "";
	$posts = postsInThreadByID($id);
	$first=1;
	$teaser="";
	foreach ($posts as $post) {
		if($first){
			$first=0;
			$teaser=preg_replace('/<[^>]*>/','',preg_replace('/<br>/',' ',$post["message"]));
		}
		$htmlposts .= buildPost($post, TINYIB_RESPAGE);
	}
	
	$htmlposts .= "<br clear=\"left\">\n";
	
	if(strlen($teaser)>50){
		$teaser=preg_replace('/\\W+\\w+$/','',substr($teaser,0,50));
	}
	
	writePage('thread/' . $id, fixLinksInRes(buildPage($htmlposts, $id,0,0,$teaser)));
}

function adminBar() {
	global $loggedin, $isadmin, $returnlink;
	$return = '[<a href="' . $returnlink . '" style="text-decoration: underline;">Return</a>]';
	if (!$loggedin) {
		return preg_replace("/\s+/S"," ",$return);
	}
	$return='[<a href="?manage">Status</a>] [' . (($isadmin) ? '<a href="?manage&bans">Bans</a>] [' : '') . '<a href="?manage&moderate">Moderate Post</a>] [<a href="?manage&rawpost">Raw Post</a>] [' . (($isadmin) ? '<a href="?manage&rebuildall">Rebuild All</a>] [' : '') . '<a href="?manage&logout">Log Out</a>] &middot; ' . $return;
	return preg_replace("/\s+/S"," ",$return);
}

function managePage($text, $onload='') {
	$adminbar = adminBar();
	$boardletter=TINYIB_BOARD;
	$boarddesc=TINYIB_BOARDDESC;
	$body = <<<EOF
	<body$onload>
		<div class="boardlist">
			[<a href="/">home</a>] [<a href="/s/">s </a>/<a href="/froge/"> froge</a>] [<a href="/dots/">dots </a>/<a href="/viruse/"> viruse</a>]
		</div>
		<div class="boardlist" style="float:right">
			$adminbar
		</div>
		<div class="logo">
			/$boardletter/ - $boarddesc
			<div class="cataloglink">[<a href="catalog">catalog</a>]</div>
		</div>
		$text
EOF;
	return preg_replace("/\s+/S"," ",pageHeader() . $body . pageFooter());
}

function manageOnLoad($page) {
	switch ($page) {
		case 'login':
			return ' onload="document.tinyib.password.focus();"';
		case 'moderate':
			return ' onload="document.tinyib.moderate.focus();"';
		case 'rawpost':
			return ' onload="document.tinyib.message.focus();"';
		case 'bans':
			return ' onload="document.tinyib.ip.focus();"';
	}
}

function manageBanForm() {
	$return=<<<EOF
	<form id="tinyib" name="tinyib" method="post" action="?manage&bans">
	<fieldset>
	<legend>Ban an IP address</legend>
	<label for="ip">IP Address:</label> <input type="text" name="ip" id="ip" value="${_GET['bans']}"> <input type="submit" value="Submit"><br>
	<label for="expire">Expire(sec):</label> <input type="text" name="expire" id="expire" value="0">&nbsp;&nbsp;<small><a href="#" onclick="document.tinyib.expire.value='3600';return false;">1hr</a>&nbsp;<a href="#" onclick="document.tinyib.expire.value='86400';return false;">1d</a>&nbsp;<a href="#" onclick="document.tinyib.expire.value='172800';return false;">2d</a>&nbsp;<a href="#" onclick="document.tinyib.expire.value='604800';return false;">1w</a>&nbsp;<a href="#" onclick="document.tinyib.expire.value='1209600';return false;">2w</a>&nbsp;<a href="#" onclick="document.tinyib.expire.value='2592000';return false;">30d</a>&nbsp;<a href="#" onclick="document.tinyib.expire.value='0';return false;">never</a></small><br>
	<label for="reason">Reason:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> <input type="text" name="reason" id="reason">&nbsp;&nbsp;<small>optional</small>
	<legend>
	</fieldset>
	</form><br>
EOF;
	return preg_replace("/\s+/S"," ",$return);
}

function manageBansTable() {
	$text = '';
	$allbans = allBans();
	if (count($allbans) > 0) {
		$text .= '<table border="1"><tr><th>IP Address</th><th>Set At</th><th>Expires</th><th>Reason Provided</th><th>&nbsp;</th></tr>';
		foreach ($allbans as $ban) {
			$expire = ($ban['expire'] > 0) ? date('y/m/d(D)H:i:s', $ban['expire']) : 'Does not expire';
			$reason = ($ban['reason'] == '') ? '&nbsp;' : htmlentities($ban['reason']);
			$text .= '<tr><td>' . $ban['ip'] . '</td><td>' . date('y/m/d(D)H:i:s', $ban['timestamp']) . '</td><td>' . $expire . '</td><td>' . $reason . '</td><td><a href="?manage&bans&lift=' . $ban['id'] . '">lift</a></td></tr>';
		}
		$text .= '</table>';
	}
	return preg_replace("/\s+/S"," ",$text);;
}

function manageModeratePostForm() {
	$return=<<<EOF
	<form id="tinyib" name="tinyib" method="get" action="?">
	<input type="hidden" name="manage" value="">
	<fieldset>
	<legend>Moderate a post</legend>
	<div valign="top"><label for="moderate">Post ID:</label> <input type="text" name="moderate" id="moderate"> <input type="submit" value="Submit"></div><br>
	<small><b>Tip:</b> While browsing the image board, you can easily moderate a post if you are logged in:<br>
	Tick the box next to a post and click "Delete" at the bottom of the page with a blank password.</small><br>
	</fieldset>
	</form><br>
EOF;
	return preg_replace("/\s+/S"," ",$return);
}

function manageRawPostForm() {
	$return=<<<EOF
	<div class="postarea">
		<form id="postform" name="tinyib" method="post" action="?" enctype="multipart/form-data">
			<input type="hidden" name="rawpost" value="1">
			<input type="hidden" name="password" value="">
			<div>
				Reply to: <input type="text" name="parent" size="10" maxlength="75" value="0" placeholder="Reply to"> (0 to start a new thread)
			</div>
			<div>
				<input type="text" name="name" maxlength="75" placeholder="Name">
				<input type="submit" value="Submit">
			</div>
			<div>
				<textarea name="message" cols="48" rows="4" placeholder="Comment"></textarea>
			</div>
			<div>
				<input type="file" name="file" size="35">
			</div>
		</form>
	</div>
EOF;
	return preg_replace("/\s+/S"," ",$return);
}

function manageModeratePost($post) {
	global $isadmin;
	$ban = banByIP($post['ip']);
	$ban_disabled = (!$ban && $isadmin) ? '' : ' disabled';
	$ban_info = (!$ban) ? ((!$isadmin) ? 'Only an administrator may ban an IP address.' : ('IP address: ' . $post["ip"])) : (' A ban record already exists for ' . $post['ip']);
	$delete_info = ($post['parent'] == TINYIB_NEWTHREAD) ? 'This will delete the entire thread below.' : 'This will delete the post below.';
	$post_or_thread = ($post['parent'] == TINYIB_NEWTHREAD) ? 'Thread' : 'Post';
	
	if ($post["parent"] == TINYIB_NEWTHREAD) {
		$post_html = "";
		$posts = postsInThreadByID($post["id"]);
		foreach ($posts as $post_temp) {
			$post_html .= buildPost($post_temp, TINYIB_INDEXPAGE);
		}
	} else {
		$post_html = buildPost($post, TINYIB_INDEXPAGE);
	}
	
	$return=<<<EOF
	<fieldset>
	<legend>Moderating No.${post['id']}</legend>
	
	<fieldset>
	<legend>Action</legend>
	
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
	<tr><td align="right" width="50%;">
	
	<form method="get" action="?">
	<input type="hidden" name="manage" value="">
	<input type="hidden" name="delete" value="${post['id']}">
	<input type="submit" value="Delete $post_or_thread" style="width: 50%;">
	</form>
	
	</td><td><small>$delete_info</small></td></tr>
	<tr><td align="right" width="50%;">
	
	<form method="get" action="?">
	<input type="hidden" name="manage" value="">
	<input type="hidden" name="bans" value="${post['ip']}">
	<input type="submit" value="Ban Poster" style="width: 50%;"$ban_disabled>
	</form>
	
	</td><td><small>$ban_info</small></td></tr>
	
	</table>
	
	</fieldset>
	
	<fieldset>
	<legend>$post_or_thread</legend>
	$post_html
	</fieldset>
	
	</fieldset>
	<br>
EOF;
	return preg_replace("/\s+/S"," ",$return);
}

function manageStatus() {
	global $isadmin;
	$threads = countThreads();
	$bans = count(allBans());
	$info = $threads . ' ' . plural('thread', $threads) . ', ' . $bans . ' ' . plural('ban', $bans);
	
	$post_html = '';
	$posts = latestPosts();
	$i = 0;
	foreach ($posts as $post) {
		if ($post_html != '') { $post_html .= '<tr><td colspan="2"></td></tr>'; }
		$post_html .= '<tr><td>' . buildPost($post, TINYIB_INDEXPAGE) . '</td><td valign="top" align="right"><form method="get" action="?"><input type="hidden" name="manage" value=""><input type="hidden" name="moderate" value="' . $post['id'] . '"><input type="submit" value="Moderate"></form></td></tr>';
	}
	
	$output = <<<EOF
	<fieldset>
	<legend>Status</legend>
	
	<fieldset>
	<legend>Info</legend>
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
	<tbody>
	<tr><td>
		$info
	</td>
	</tr>
	</tbody>
	</table>
	</fieldset>
	
	<fieldset>
	<legend>Recent posts</legend>
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
	$post_html
	</table>
	</fieldset>
	
	</fieldset>
	<br>
EOF;
	
	return preg_replace("/\s+/S"," ",$output);
}

function manageInfo($text) {
	return '<div class="manageinfo">' . $text . '</div>';
}

/* Build RSS */

function buildPostRSS($post) {
	$boardlink=TINYIB_BOARD;
	$relurl="http://kek.epizy.com/$boardlink/";
	$return = "";
	$threadid = ($post['parent'] == TINYIB_NEWTHREAD) ? $post['id'] : $post['parent'];
	$postlink = 'thread/' . $threadid . '#' . $post['id'];
	if (!isset($post["omitted"])) { $post["omitted"] = 0; }
	
	preg_match('/>(.*)<\/span> (.*)/',$post["nameblock"],$postname);
	
	$postnamen=preg_replace('/<[^>]*>/','',$postname[1]);
	
	$return .= <<<EOF
<item>
<title>Post No.${post['id']} by $postnamen</title>
<link>$relurl$postlink</link>
<guid>$relurl$postlink</guid>

EOF;
	
	if (TINYIB_TRUNCATE > 0 && substr_count($post['message'], '<br>') > TINYIB_TRUNCATE) { // Truncate messages on board index pages for readability
		$br_offsets = strallpos($post['message'], '<br>');
		$post['message'] = substr($post['message'], 0, $br_offsets[TINYIB_TRUNCATE - 1]) . "<br/>(Post truncated)";
	}
	
	if ($post["file"] != "") {
		$post['message'] = "<a href=\"{$relurl}src/${post["file"]}\"><img src=\"{$relurl}thumb/${post["thumb"]}\"></a><br/>" . $post['message'];
	}
	
	$post["message"]=preg_replace('/<br>/','<br/>',$post["message"]);
	$post["message"]=preg_replace('/<a href="(thread\/[^"]*)">/','<a href="'.$relurl.'${1}">',$post["message"]);
	
	$posttime=date('r',$post['timestamp']);
	$return .= <<<EOF
<description>
<![CDATA[
${post["message"]}
]]>
</description>
<pubDate>${posttime}</pubDate>
</item>

EOF;
	
	if($GLOBALS['rsshasposts']==0){
		$GLOBALS['rsshasposts']=$posttime;
	}
	
	return $return;
}

function buildRSS() {
	$boardletter=TINYIB_BOARD;
	$boarddesc=TINYIB_BOARDDESC;
	$boardlink=TINYIB_BOARD;
	$relurl="http://kek.epizy.com/$boardlink/";
	$post_rss = '';
	$posts = latestPosts();
	$GLOBALS['rsshasposts'] = 0;
	foreach ($posts as $post) {
		$post_rss .= buildPostRSS($post);
	}
	
	if($GLOBALS['rsshasposts']==0){
		$lasttime=date('r');
	}else{
		$lasttime=$GLOBALS['rsshasposts'];
	}
	$output = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
<link rel="self" href="{$relurl}posts.rss" xmlns="http://www.w3.org/2005/Atom"/>
<title>/$boardletter/ - $boarddesc</title>
<link>$relurl</link>
<description>Recent posts</description>
<lastBuildDate>$lasttime</lastBuildDate>
<pubDate>$lasttime</pubDate>
$post_rss</channel>
</rss>
EOF;
	
	return $output;
}

function buildCatalog($threads) {
	$page = 0;
	$i = 0;
	$htmlposts = '';
	foreach ($threads as $thread) {
		$replies = postsInThreadByID($thread['id']);
		$repliescount = max(0, count($replies) - 1);
		$imagescount=-1;
		
		foreach ($replies as $reply) {
			if($reply["file"] != ""){
				$imagescount++;
			}
		}
		
		$threadid=$thread['id'];
		$postlink='thread/' . $threadid;
		$thumbwidth=$thread["thumb_width"];
		$thumbheight=$thread["thumb_height"];
		if($thumbwidth>$thumbheight){
			$thumbheight=round($thumbheight/$thumbwidth*150);
			$thumbwidth=150;
		}else{
			$thumbwidth=round($thumbwidth/$thumbheight*150);
			$thumbheight=150;
		}
		if($thumbwidth<50){
			$thumbwidth=50;
		}
		if($thumbheight<50){
			$thumbheight=50;
		}
		if($thread["file"] != ""){
			$imagefile=<<<EOF
<img src="thumb/${thread["thumb"]}" alt="${thread["id"]}" class="thumb" width="$thumbwidth" height="$thumbheight">
EOF;
		}else{
			$imagefile="";
		}
		$pagenumber=$page+1;
		$teaser=preg_replace('/<[^>]*>/','',preg_replace('/<br>/',' ',$thread["message"]));
		
		$htmlposts .= <<<EOF
	<div class="thread" id="$threadid">
		<a href="$postlink" target="_blank">
			$imagefile
			<div class="meta" title="(R)eplies / (I)mages / (P)age">
				R: <b>$repliescount</b> / I: <b>$imagescount</b> / P: <b>$pagenumber</b>
			</div>
			<div class="teaser">$teaser</div>
		</a>
	</div>
EOF;
		if (++$i >= TINYIB_THREADSPERPAGE) {
			$page++;
			$i = 0;
		}
	}
	$boardletter=TINYIB_BOARD;
	$boarddesc=TINYIB_BOARDDESC;
	if (TINYIB_MAXKB > 0) {
		$max_file_size_rules_html = '<li>Maximum file size allowed is ' . TINYIB_MAXKBDESC . '</li>';
	}else{
		$max_file_size_rules_html='';
	}
	$maxdimensions = TINYIB_MAXWOP . 'x' . TINYIB_MAXHOP;
	if (TINYIB_MAXW != TINYIB_MAXWOP || TINYIB_MAXH != TINYIB_MAXHOP) {
		$maxdimensions .= ' (new thread) or ' . TINYIB_MAXW . 'x' . TINYIB_MAXH . ' (reply)';
	}
	$body = <<<EOF
	<body id="$boardletter">
		<div class="boardlist">
			[<a href="/">home</a>] [<a href="/s/">s </a>/<a href="/froge/"> froge</a>] [<a href="/dots/">dots </a>/<a href="/viruse/"> viruse</a>]
		</div>
		<div class="logo">
			/$boardletter/ - $boarddesc
			<div class="cataloglink">[<a href="catalog">catalog</a>]</div>
		</div>
		<div class="postarea">
			<form name="postform" id="postform" action="imgboard.php" method="post" enctype="multipart/form-data">
				<input type="hidden" name="parent" value="0">
				<div>
					<input type="text" name="name" maxlength="75" placeholder="Name">
					<input type="submit" value="Submit">
				</div>
				<div>
					<textarea name="message" cols="48" rows="4" placeholder="Comment"></textarea>
				</div>
				<div>
					<input type="file" name="file" size="35">
				</div>
				<div>
					<input type="password" name="password" size="8" placeholder="Password">
				</div>
				<div class="rules">
					<ul>
						<li>Supported file types are GIF, JPG, and PNG</li>
						$max_file_size_rules_html
						<li>Images greater than $maxdimensions will be thumbnailed</li>
						<li>NSFW images are not allowed to be posted</li>
					</ul>
				</div>
			</form>
		</div>
		<div id="catalog">
			$htmlposts
		</div>
		<br>
EOF;
	return preg_replace("/\s+/S"," ",pageHeader("Catalog") . $body . pageFooter());
}
?>