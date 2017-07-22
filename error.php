<?php
require 'settings.php';
$includes = array("inc/functions.php");
if (in_array(TINYIB_DBMODE, array('flatfile', 'mysql', 'sqlite'))) {
	$includes[] = 'inc/database_' . TINYIB_DBMODE . '.php';
} else {
	die("Unknown database mode specified");
}
foreach ($includes as $include) {
	include $include;
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
	$dietext=<<<EOF
<html>
<head>
<title>404</title>
<link rel="icon" href="/favicon.ico">
<style>
body{
background:url(/chan/froge.png) no-repeat center fixed;
background-size:cover;
color:#444;
font:28px monospace;
letter-spacing:2px;
line-height:32px;
padding:10px
}
u{
font-weight:bold;
color:#00f;
position:absolute;
text-decoration:none
}
i,b{
display:inline-block;
border:1px solid #444;
width:16px;
height:16px;
position:relative;
margin:0 1px;
white-space:nowrap
}
b::after{
font-family:sans-serif;
content:'\2713';
position:absolute;
top:-10px;
left:0;
color:#f00
}
</style>
</head>
<body>
Name: <u>A froge</u>____________________<br><br>
Age: <i></i>Under 18 <i></i>Over 18 <b></b>A froge | Sex: <i></i>Male <i></i>Grill <i></i>Ur mom <b></b>With  froge<br><br>
Nationality: <u>Frogeland, [s4s]</u>____________________<br><br>
Favorite board: <b></b>[s4s] <i></i>[a/jp] <i></i>/lupchan/ <b></b>/froge/<br><br>
Boards visited in the past 3 months (check all that apply):<br>
<i></i>a <i></i>b <i></i>c <i></i>d <i></i>e <i></i>f <i></i>g <i></i>gif <i></i>h <i></i>hr <i></i>k <i></i>m <i></i>o <i></i>p <i></i>r <i></i>s <i></i>t <i></i>u <i></i>v <i></i>vg <i></i>vr <i></i>w <i></i>wg <i></i>i <i></i>ic <i></i>r9k <b></b>s4s <i></i>cm <i></i>hm <i></i>lgbt <i></i>y <i></i>3 <i></i>adv <i></i>an <i></i>asp <i></i>biz <i></i>cgl <i></i>ck <i></i>co <i></i>diy <i></i>fa <i></i>fit <i></i>gd <i></i>hc <i></i>int <i></i>jp <i></i>lit <i></i>mlp <i></i>mu <i></i>n <i></i>out <i></i>po <i></i>pol <i></i>sci <i></i>soc <i></i>sp <i></i>tg <i></i>toy <i></i>trv <i></i>tv <i></i>vp <i></i>wsg <i></i>x <b></b>A froge<br><br>
Favorite maymay (check one):<br>
<b></b>Lel <b></b>Kek <b></b>Froge <b></b>Ebin <b></b>Flowre <b></b>Le meme dream <i></i>Is it true that if I post an anime image on 4chan I will automatically get attention and replies? <b></b>Potlel <b></b>Froge <i></i>Gentoo <i></i>Semen demon <b></b>Top top lel is trapped <b></b>Froge <b></b>Sending the album that sun killer clown all <b></b>Froge <i></i>Nice board <b></b>Froge<br><b></b>Other: <u>Froge</u>____________________<br><br>
Best gets:<br>
<b></b>Singles <b></b>Dubs <b></b>Trips <b></b>Quads <b></b>Quints <b></b>Sexts <b></b>Septs <b></b>Octs <b></b>Nons <b></b>Decs <b></b>Hendecs <b></b>Duodecs <b></b>Tredecs <b></b>Quattuordecs <b></b>Quindecs <b></b>Sexdecs <b></b>Septendecs <b></b>Octodecs <b></b>Novemdecs <b></b>Vigups<br>
<b></b>Other: <u>Dubdubs, nubs, dubnubs, palindrome, guess post number, guess fortune</u>____________________<br><br>
Do you feel stealing gets from other boards is nice?<br>
<b></b>Yes <i></i>No<br><br>
Are froges cool, but should have their own board?<br>
<b></b>Yes, make /froge/ <b></b>No, stay on [s4s]<br><br>
How do you value your what you contribute of to at the [s4s]? At which do you most can't the least?<br>
<u>Ebin</u>________________________________________<br>
________________________________________<br>
________________________________________<br><br>
Any parting thoughts?<br>
<u>A froge</u>________________________________________<br>
________________________________________<br>
________________________________________<br><br>
<i></i>Hot blooded <b></b>My digits <b></b>Privilege <b></b>A froge<br><br><br>
<div style="float:right">Signature: <u>A froge</u>____________________</div>
<br style="break:both">
<script>
(onscroll=onresize=function(){
	document.body.style.backgroundPositionY=(document.body.scrollTop/(document.body.scrollHeight-document.body.clientHeight)*100|0)+'%'
})()
</script>
</body>
</html>
EOF;
	die($dietext);
}
?>