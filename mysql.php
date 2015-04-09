<?php
/******************************************************************************
 * CREATED BY JAW DEVELOPMENT LLC
 * MySQL CLI
 *****************************************************************************/

session_start();
if( isset( $_POST['q'] ) ) {
	$mysql_user = $_SESSION['mysql_cli_u'];
	$mysql_pass = $_SESSION['mysql_cli_p'];
	$query = $_POST['q'];
	$res = shell_exec( 'mysql -u '.$mysql_user.' -p'.$mysql_pass.' -tre "'.addcslashes( $query, '"' ).'" 2>&1' );
	if( empty( $res ) ) echo "<i>No rows returned.</i>";
	else echo $res;
	exit;
}
$logged_in = false;
if( isset( $_POST['submit_login'] ) ) {
	$user = $_POST['username'];
	$pass = $_POST['password'];
	$check = shell_exec( "mysql -u $user -p$pass 2>&1" );
	if( strpos( $check, "ERROR 1045" ) === false ) {
		$_SESSION['mysql_cli_u'] = $user;
		$_SESSION['mysql_cli_p'] = $pass;
		header( "Location: ./index.php" );
		exit;
	}
}
if( isset( $_GET['logout'] ) ) {
	session_unset();
	session_destroy();
	header( "Location: ./index.php" );
	exit;
	
}
if( isset( $_SESSION['mysql_cli_u'] ) && isset( $_SESSION['mysql_cli_p'] ) ) $logged_in = true;

?>
<html>
<head>
	<title>MySQL CLI</title>
	<style type='text/css'>
	html {
		height: 100%;
		margin: 0;
		padding: 0;
		width: 100%;
	}
	body {
		background: #000;
		color: #fff;
		font-family: inconsolata, monospace;
		font-size: 0.9em;
		margin: 0;
		padding: 0;
	}
	div.container {
		display: flex;
		flex-direction: column;
		height: 100%;
		position: relative;
		width: 100%;
	}
		div.container > div {
			-moz-box-sizing: border-box;
			box-sizing: border-box;
		}
		div.container > div#output {
			color: #1ad;
			background: #000;
			height: 100%;
			overflow-y: auto;
			position: relative;
			width: 100%;
		}
			div.container > div#output > * {
				border-bottom: 1px dashed rgba(255,255,255,0.02);
				display: block;
				margin: 0;
				overflow-x: auto;
				padding: 1em;
			} div.container > div#output > *:nth-child(even) {
				background: rgba(255,255,255,0.06);
			}
		div.container > div#input {
			background: #222;
			border-top: 2px solid #09c;
			box-shadow: 0 -0.5em 1em rgba(40,180,255,0.1);
			min-height: 1em;
			outline: 0;
			padding: 1em;
			text-shadow: 0 1px 1em rgba(40,180,255,0.2);
			width: 100%;
		}
		i {
			color: #999;
			font-size: 0.7em;
		}
		form.login {
			-moz-box-sizing: border-box;
			box-sizing: border-box;
			background: #09c;
			border-radius: 3px;
			border-top: 2px solid rgba(0,0,0,0.2);
			border-top: 2px solid rgba(255,255,255,0.2);
			display: block;
			left: 50%;
			top: 50%;
			margin-left: -10em;
			margin-top: -20em;
			padding: 1em;
			position: absolute;
			width: 20em;
		}
			form.login > input {
				-moz-box-sizing: border-box;
				box-sizing: border-box;
				border: 1px solid rgba(0,0,0,0.4);
				padding: 0.5em;
				width: 100%;
				display: block;
			}
	</style>
</head>
<body>
<?php if( $logged_in ) { ?>
<div class='container'>
	<div id='output'></div>
	<div id='input' contenteditable='true'></div>
</div>
<script type='text/javascript'>
var input = null;
var output = null;
var running = false;
var qhist = new Array();
var qhist_id = -1;
var saved_query = false;
var keys = new Array( 256 );
for( var i = 0; i < keys.length; i++ ) keys[i] = false;
function selectInput() {
	var r = document.createRange();
	r.selectNodeContents( input );
	var s = window.getSelection();
	s.removeAllRanges();
	s.addRange( r );
}
function submitQuery() {
	if( running ) return;
	var query = input.innerHTML;
	if( query == "" ) return;
	else {
		qlow = query.toLowerCase().trim();
		if( qlow == "quit"   || qlow == "quit;"   ||
                    qlow == "logout" || qlow == "logout;" ||
                    qlow == "exit"   || qlow == "exit;" ) {
			window.open( './index.php?logout=1', '_self' );
			return;
		} else if( qlow == 'clear' || qlow == "clear;" ) {
			output.innerHTML = "";
			input.innerHTML = "";
			selectInput();
			return;
		}
	}
	running = true;
	qhist_id = -1;
	qhist.push( query );
	input.innerHTML = "";
	selectInput();
	var pre = document.createElement( 'pre' );
	output.appendChild( pre );
	pre.innerHTML = "<i>running...</i>";
	output.scrollTop = output.scrollHeight;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if( xmlhttp.readyState == 4 && xmlhttp.status == 200 ) {
			pre.innerHTML = xmlhttp.responseText;
			output.scrollTop = output.scrollHeight;
			running = false;
		}
	};
	xmlhttp.open( "POST", "index.php", true );
	xmlhttp.setRequestHeader( "Content-type", "application/x-www-form-urlencoded" );
	xmlhttp.send( "q="+query );
}
window.onload = function() {
	input = document.getElementById( 'input' );
	output = document.getElementById( 'output' );
	selectInput();
}
document.onkeydown = function( e ) {
	keys[e.keyCode] = true;
	if( !keys[16] && e.keyCode == 13 ) {
		submitQuery();
		e.preventDefault();
	}
	else if( keys[16] && e.keyCode == 9 ) {
		e.preventDefault();
		if( saved_query === false ) saved_query = input.innerHTML;
		if( qhist_id < 0 ) qhist_id = qhist.length-1;
		else {
			qhist_id -= 1;
			if( qhist_id < 0 ) qhist_id = 0;
		}
		if( qhist_id < 0 || qhist >= history.length ) return;
		input.innerHTML = qhist[qhist_id];
	} else if( !keys[16] && e.keyCode == 9 ) {
		e.preventDefault();
		if( qhist_id < 0 ) return;
		qhist_id += 1;
		if( qhist_id >= qhist.length ) {
			qhist_id = -1;
			input.innerHTML = saved_query;
			saved_query = false;
		} else input.innerHTML = qhist[qhist_id];
	}
};
document.onkeyup = function( e ) {
	keys[e.keyCode] = false;
};
</script>
<?php } else { ?>
<form class='login' method='post'>
	<input type='text' name='username' placeholder='username' />
	<input type='password' name='password' placeholder='password' />
	<input type='submit' name='submit_login' value='Log In' />
</form>
<script type='text/javascript'>
window.onload = function() {
<?php
if( isset( $_POST['submit_login'] ) && !$logged_in ) {
	echo "\talert( 'Login failed. Please try again' );\n";
}
?>
	document.getElementsByTagName( 'input' )[0].select();
}
</script>
<?php } ?>
</body>
</html>
