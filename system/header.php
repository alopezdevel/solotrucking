<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title>SoloTrucking - Internal Control System</title>
<link rel="stylesheet" href="css/style_system.css" type="text/css">
<link rel="shortcut icon" href="images/favicon.png" type="img/x-icon">
</head>

<body>
<nav class="main-nav-outer" id="layer_menu"><!--main-nav-start-->
	<div class="container">
		<a href="#home" class="img-logo"><img  src="images/nav/img-logo.png" alt="logo"></a>
		<ul class="top-nav">
			<li><a href="#" class="icon user" title="Log In"><span><?php echo $_SESSION["usuario_actual"].'-'.$_SESSION['acceso'];?></span></a></li>
			<li><a href="#" class="icon logout"title="Log Out"><span>Log Out</span></a></li>
		</ul>
        <ul class="main-nav">
        	<li><a href="inicio.php">Home</a></li>
        	<li class="submenu"><a href="#">Catalogs</a>
        		<ul>
        			<li><a href="#">Companies</a></li>
        			<li><a href="#">Insurers</a></li>
        			<li><a href="#">Financial</a></li>
        		</ul>
        	</li>
        	<li class="submenu"><a href="#">Users</a>
        		<ul>
        			<li><a href="user_register.php">New User</a></li>
        			<li><a href="users_clients.php">Client Users</a></li>
        		</ul>
        	</li>
            <li><a href="#">Endorsements</a></li>
            <li class="submenu"><a href="#">Certificates</a>
                <ul>
                    <li><a href="certificate_request.php">Requests to Certificates</a></li>
                </ul>
            </li> 
            <li><a href="#">Claims</a></li>  
            <li><a href="#">Quotes</a></li>
            <li><a href="#">Support</a></li>
        </ul>
        <!--<a class="res-nav_click right" href="#"><i class="fa-bars"></i></a>-->
    </div>
</nav><!--main-nav-end-->
