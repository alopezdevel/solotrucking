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
            
			<li><a href="login" class="icon logout"title="Log Out"><span>Log Out</span></a></li>
		</ul>
        <ul class="main-nav">
      		<?php if($_SESSION['acceso'] == "U"){ ?>
      		<!---- Admin navigation ---->
      			<li><a href="inicio">Home</a></li>
      			<li class="submenu"><a href="#">Catalogs</a>
	        		<ul>
	        			<li><a href="users_clients"><i class="fa fa-users"></i> Users</a></li>
	                    <li><a href="companies"><i class="fa fa-users"></i> Insured Companies</a></li>  
	        			<li style="display: none;"><a href="#">UnderWriters</a></li>
	        			<li style="display: none;"><a href="#">Financial</a></li>
	        		</ul>
        		</li>
        		<li class="submenu"><a href="#">Users</a>
	        		<ul>
	        			<li><a href="user_register"><i class="fa fa-user-plus"></i> New Insured Company User</a></li>
	        		</ul>
        		</li>
        		<li class="submenu"><a href="#">Certificates</a>
	                <ul>
	                    <li><a href="certificate_request_pdf_upload"><i class="fa fa-upload"></i> Upload Certificates</a></li>
	                </ul>
            	</li>
            	<li style="display: none;"><a href="#">Claims</a></li>  
            	<li style="display: none;"><a href="#">Quotes</a></li>
            	<li style="display: none;"><a href="#">Support</a></li>
            <!---- End Admin navigation ---->	
            <?php } ?>
            <?php if($_SESSION['acceso'] == "C"){ ?>
      		<!---- Customer navigation ---->
      			<li><a href="inicio">Home</a></li>
      			<li style="display: none;" class="submenu"><a href="#">Catalogs</a>
	        		<ul>
	        			<li><a href="#"><i class="fa fa-users"></i> Operators</a></li>
	                    <li><a href="#"><i class="fa fa-users"></i> Units</a></li>  
	        			<li><a href="#"><i class="fa fa-truck"></i> Trailers</a></li>
	        		</ul>	
        		</li>
        		<li class="submenu"><a href="#">Certificates</a>
	                <ul>
	                    <li><a href="certificate_request_company"><i class="fa fa-download"></i> Download Certificate</a></li>
	                </ul> 
            	</li>
            	<li style="display: none;"><a href="#">Claims</a></li>  
            	<li style="display: none;"><a href="#">Quotes</a></li>
            	<li style="display: none;"><a href="#">Support</a></li>
	
      		<!---- End Customer navigation ---->	
            <?php } ?>
      	</ul>
        <!--<a class="res-nav_click right" href="#"><i class="fa-bars"></i></a>-->
    </div>
</nav><!--main-nav-end-->
