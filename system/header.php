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
<link href="../css/font-awesome.css" rel="stylesheet" type="text/css"> 
</head>
 
<body>
<nav class="main-nav-outer" id="layer_menu"><!--main-nav-start-->
	<div class="container">
		<a href="#home" class="img-logo"><img  src="images/nav/img-logo.png" alt="logo"></a>
		<ul class="top-nav">
			<li class="top-submenu"><a href="#" class="icon user" title="Log In"><span><?php echo $_SESSION["usuario_actual"]?></span></a>
                <ul>
                    <li style="display: none;"><a href="#">Change password</a></li>
                    <?php if($_SESSION['acceso'] == "C"){ ?>
                    <li><a  id="aUpdateAccount" href="#">Update account Information</a></li>
                    <?php }?>
                </ul>
            </li>
            
			<li><a href="login.php" class="icon logout"title="Log Out"><span>Log Out</span></a></li>
		</ul>
        <ul class="main-nav">
        	<li><a href="inicio.php">Home</a></li>
            <?php if($_SESSION['acceso'] == "U"){ ?>
        	<li class="submenu" style="display: none;"><a href="#">Catalogs</a>
        		<ul>
        			<li><a href="#">Insured Companies</a></li>
        			<li><a href="#">UnderWriters</a></li>
        			<li><a href="#">Financial</a></li>
        		</ul>
        	</li>
        	<li class="submenu"><a href="#">Users</a>
        		<ul>
        			<li><a href="user_register.php">New Insured Company User</a></li>
        			<li><a href="users_clients.php">Insured Company Users</a></li>
        		</ul>
        	</li>
            <?php } ?>
            <li class="submenu" style="display: none;"><a href="#">Endorsements</a>
            	<ul>
        			<li><a href="endorsement_request.php">Request an Endorsement</a></li>
        		</ul>
            </li>
            <?php  if($_SESSION['acceso'] == "U"){ ?>
             <li class="submenu"><a href="#">Certificates</a>
                <ul>
                    <li><a href="certificate_request.php">Requests to Certificates</a></li>
                    <li><a href="certificate_request_pdf_upload.php">Upload Certificate</a></li>
                </ul>
                
            </li> 
            <?php } ?>
            <?php if($_SESSION['acceso'] == "C"){ ?>
             <li class="submenu"><a href="#">Certificates</a>
                <ul>
                    <li><a href="certificate_request_company.php">Download Certificate</a></li>
                </ul>
                
            </li> 
            <?php } ?>
           
            <li style="display: none;"><a href="#">Claims</a></li>  
            <?php if($_SESSION['acceso'] == "U"){ ?>
            <li style="display: none;"><a href="#">Quotes</a></li>
            <?php }?>
            <li style="display: none;"><a href="#">Support</a></li>
        </ul>
        <!--<a class="res-nav_click right" href="#"><i class="fa-bars"></i></a>-->
    </div>
</nav><!--main-nav-end-->
