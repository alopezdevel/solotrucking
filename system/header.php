<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head> 
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title>SoloTrucking - Internal Control System</title>   
<?php include("libs_header.php"); ?>  
</head>
 
<body>
<div class="overlay-background"></div>
<nav class="main-nav-outer" id="layer_menu"><!--main-nav-start-->
	<div class="container">
		<a href="#home" class="img-logo"><img  src="images/nav/img-logo.png" alt="logo"></a>
		<ul class="top-nav">
			<li class="top-submenu"><a href="#" class="icon user" title="Log In"><span>
                <?php 
                    if(isset($_SESSION["company_name"])){echo $_SESSION["company_name"];}else{echo $_SESSION["usuario_actual"];}
                ?>
            </span></a>
                <ul style="display: none;">
                    <li style="display: none;"><a href="#">Change password</a></li>
                </ul>
            </li>
			<li><a href="login" class="icon logout"title="Log Out" style="display: block;"><span>Log Out</span></a></li>
		</ul>
        <ul class="main-nav">  
      		<?php if($_SESSION['acceso'] == "1"){ ?>
      		<!---- Admin MASTER navigation ---->
      			<li><a href="inicio">Home</a></li>
      			<li class="submenu"><a href="#">CATALOGS</a>
                    <ul>
                        <li><a href="companies"><i class="fa fa-briefcase"></i> Companies</a></li>
                        <li><a href="users"> <i class="fa fa-users"></i> Users</a></li>
                        <li><a href="certificate_request_pdf_upload">COMPANY CERTIFICATES</a></li>   
                        <li><a href="brokers"><i class="fa fa-usd"></i> Brokers</a></li>
                        <li><a href="insurances"><i class="fa fa-building"></i> Insurances</a></li> 
                    </ul>
                </li>
                <li><a href="policies">POLICIES</a></li>
                <li class="submenu"><a href="#">Endorsements</a>
                   <ul>
                        <li><a href="endorsement_request"><i class="fa fa-users"></i> DRIVERS</a></li>
                        <li><a href="endorsement_request_units"> <i class="fa fa-truck"></i> UNITS</a></li>
                        <li><a href="endorsement_files"> <i class="fa fa-file-text"></i> UPLOAD FILES</a></li>  
                    </ul>
                </li>
                <li><a href="claims_requests">CLAIMS</a></li> 
                <li class="submenu"><a href="#">QUOTES</a>
                   <ul>
                        <li><a href="quote_formats"><i class="fa fa-file-text"></i> QUOTE FORMATS</a></li>  
                    </ul>
                </li>
                <li class="submenu"><a href="invoices">INVOICES</a> 
            	<li class="submenu"><a href="#">SYSTEM SUPPORT</a>
                    <ul>
                        <li><a href="bitacora_actividades"><i class="fa fa-list-alt"></i> SYSTEM DEVELOPMENT ACTIVITIES</a></li>
                    </ul>
                </li>
            <!---- End Admin navigation ---->
            <?php } if($_SESSION['acceso'] == "4"){ //CUSTOMER SERVICES?>
              <!---- Admin MASTER navigation ---->
                  <li><a href="inicio">Home</a></li>
                  <li class="submenu"><a href="#">CATALOGS</a>
                    <ul>
                        <li><a href="companies"><i class="fa fa-briefcase"></i> Companies</a></li>
                        <li><a href="users"> <i class="fa fa-users"></i> Company Users</a></li> 
                        <li><a href="certificate_request_pdf_upload">COMPANY CERTIFICATES</a></li>                         
                        <li><a href="brokers"><i class="fa fa-usd"></i> Brokers</a></li>
                        <li><a href="insurances"><i class="fa fa-building"></i> Insurances</a></li>  
                    </ul>
                  </li>
                  <li><a href="policies">POLICIES</a></li>
                  <li class="submenu"><a href="#">Endorsements</a>
                   <ul>
                        <li><a href="endorsement_request"><i class="fa fa-users"></i> DRIVERS</a></li>
                        <li><a href="endorsement_request_units"> <i class="fa fa-truck"></i> UNITS</a></li> 
                        <li><a href="endorsement_files"> <i class="fa fa-file-text"></i> UPLOAD FILES</a></li> 
                    </ul>
                </li>
                <li><a href="claims_requests">CLAIMS</a></li> 
                <li class="submenu"><a href="#">SYSTEM SUPPORT</a>
                    <ul>
                        <li><a href="bitacora_actividades"><i class="fa fa-list-alt"></i> SYSTEM DEVELOPMENT ACTIVITIES</a></li>
                    </ul>
                </li> 
            <?php } if($_SESSION['acceso'] == "3"){ //SOLO TRUCKING ADMIN?>
              <!---- Admin MASTER navigation ---->
                  <li><a href="inicio">Home</a></li>
                  <li class="submenu"><a href="#">CATALOGS</a>
                    <ul>
                        <li><a href="companies"><i class="fa fa-briefcase"></i> Companies</a></li>
                        <li><a href="users"> <i class="fa fa-users"></i> Company Users</a></li>
                        <li><a href="certificate_request_pdf_upload">COMPANY CERTIFICATES</a></li> 
                        <li><a href="brokers"><i class="fa fa-usd"></i> Brokers</a></li>
                        <li><a href="insurances"><i class="fa fa-building"></i> Insurances</a></li>  
                    </ul>
                  </li>
                  <li><a href="policies">POLICIES</a></li>
                  <li class="submenu"><a href="#">Endorsements</a>
                   <ul>
                        <li><a href="endorsement_request"><i class="fa fa-users"></i> DRIVERS</a></li>
                        <li><a href="endorsement_request_units"> <i class="fa fa-truck"></i> UNITS</a></li> 
                        <li><a href="endorsement_files"> <i class="fa fa-file-text"></i> UPLOAD FILES</a></li> 
                    </ul>
                </li>
                <li><a href="claims_requests">CLAIMS</a></li> 
                  <li class="submenu"><a href="#">SYSTEM SUPPORT</a>
                    <ul>
                        <li><a href="bitacora_actividades"><i class="fa fa-list-alt"></i> SYSTEM DEVELOPMENT ACTIVITIES</a></li>
                    </ul>
                </li>
            <!---- COMPANY NAVIGATION --->	
            <?php }  if($_SESSION['acceso'] == "2"){ ?>
      			<li><a href="inicio">Home</a></li>
                <li style="display: none;"><a href="#">MY COMPANY</a></li>
                <li><a href="mypolicies">MY Policies</a></li> 
                <li><a href="endorsements">ENDORSEMENTS</a></li>
        		<li><a href="certificates">Certificates</a></li>
            	<li><a href="claims">Claims</a></li>  
            	<li style="display: none;"><a href="#">Quotes</a></li>
            	<li style="display: none;"><a href="#">Support</a></li>	
            <?php } ?>
            <!---- END COMPANY NAVIGATION ----> 
      	</ul>
        <!--<a class="res-nav_click right" href="#"><i class="fa-bars"></i></a>-->
    </div>
</nav><!--main-nav-end-->
<div id="mensaje" title="System Notification"></div> 
<div id="wait_container"><br><br><img src="images/ajax-loader.gif" alt="ajax-loader.gif"></div>
<div id="Wait" title="Solo-Trucking Message"></div>
