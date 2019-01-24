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
      		<!-- Admin MASTER navigation -->
      			<li><a href="inicio"><i class="fa fa-home"></i></a></li>
      			<li class="submenu"><a href="#">ADMINISTRATOR</a>
                    <ul>
                        <li><a href="ourcompany">Our Company</a></li> 
                        <li><a href="users">Users</a></li>  
                    </ul>
                </li>
                <li class="submenu"><a href="#">COMPANIES</a>
                    <ul>
                        <li><a href="companies">Companies</a></li>
                        <li><a href="certificate_request_pdf_upload">Company Certificates (upload)</a></li>   
                    </ul>
                </li>
                <li class="submenu"><a href="#">POLICIES</a>
                    <ul>
                        <li><a href="policies">Policies</a></li>
                        <li><a href="list_admin">Administrator drivers/vehicles</a></li>
                    </ul>
                </li>
                <li class="submenu"><a href="#">Endorsements</a>
                   <ul>
                        <li><a href="endorsements">New Endorsement (like company)</a></li> 
                        <li><a href="endorsement_request">Drivers</a></li>
                        <li><a href="endorsement_request_units">Vehicles</a></li>
                        <li><a href="endorsement_request_adds">Loss Payee or Additional Insured</a></li>
                        <li style="display: none;"><a href="endorsement_month">Monthly Report to CRC</a></li>
                        <li style="display: none;"><a href="endorsement_files">Upload Files</a></li>  
                        <li><a href="brokers">Brokers to send endorsements</a></li>
                    </ul>
                </li>
                <li class="submenu"><a href="#">CLAIMS</a>
                    <ul>
                        <li><a href="claims">New Claim</a></li>
                        <li><a href="claims_requests">Applications for Claims</a></li>
                        <li><a href="insurances">Insurances to send email</a></li> 
                    </ul>
                </li> 
                <li class="submenu"><a href="#">QUOTES</a>
                   <ul>
                        <li><a href="quote_formats">Download Formats</a></li>  
                    </ul>
                </li>
                <li class="submenu"><a href="invoices">ACCOUNTING</a>
                    <ul>
                        <li><a href="services_products">Products & Services</a></li> 
                        <li><a href="invoices">Invoices</a></li> 
                    </ul>
                </li> 

            <!-- End Admin navigation -->
            <?php } if($_SESSION['acceso'] == "4"){?>
            <!-- SOLO-TRUCKING USER -->
                  <li><a href="inicio">Home</a></li>
                  <li class="submenu"><a href="#">COMPANIES</a>
                    <ul> 
                        <li><a href="companies"><i class="fa fa-briefcase"></i>Companies</a></li>  
                        <li><a href="users"> <i class="fa fa-users"></i> Company Users</a></li> 
                        <li><a href="certificate_request_pdf_upload"> <i class="fa fa-cloud-upload"></i> Company Certificates (upload)</a></li>   
                    </ul>
                  </li>
                  <li class="submenu"><a href="#">POLICIES</a>
                    <ul>
                        <li><a href="policies">Policies</a></li>
                        <?php if($_SESSION["usuario_actual"] == "customerservice@solo-trucking.com"){?> 
                            <li><a href="list_admin">Administrator drivers/vehicles</a></li>
                        <?php }?>
                        
                    </ul>
                  </li>
                  <?php if($_SESSION["usuario_actual"] == "customerservice@solo-trucking.com"){?>
                  <li class="submenu"><a href="#">Endorsements</a>
                   <ul>
                        <li><a  href="endorsement_request">New for Drivers</a></li>
                        <li><a  href="endorsement_request_units">New for Units</a></li>
                        <li><a href="endorsement_request_adds" title="New for Loss Payee or Additional Insured">Loss Payee or Additional Insured</a></li>
                        <li style="display: none;"><a href="endorsement_month">Monthly Report to CRC</a></li>
                        <li style="display: none;"><a href="endorsement_files">Upload files to endorsements</a></li> 
                        <li><a href="brokers">Configure e-mails for Brokers</a></li>
                        <li style="display: none;"><a href="endorsements">New Endorsement like a company</a></li> 
                   </ul>
                 </li>
                 <?php }?>
                 <li class="submenu"><a href="#">CLAIMS</a>
                    <ul>
                        <li><a href="claims">New Claim</a></li>
                        <li><a href="claims_requests">Applications for Claims</a></li>
                        <li><a href="insurances">Configure e-mails for Insurances</a></li> 
                    </ul>
                 </li>  
                 <li class="submenu"><a href="#">QUOTES</a>
                   <ul style="width: 100px;">
                        <li><a href="quote_formats">Formats</a></li>  
                    </ul>
                 </li>
              <?php } if($_SESSION['acceso'] == "3"){ //SOLO TRUCKING ADMIN?>
              <!-- Admin MASTER navigation -->
                  <li><a href="inicio">Home</a></li>
                  <li class="submenu"><a href="#">ADMINISTRATOR</a>
                    <ul>
                        <li><a href="brokers"><i class="fa fa-usd"></i> Brokers to endorsements</a></li>
                        <li><a href="insurances"><i class="fa fa-building"></i> Insurances to claims</a></li>  
                    </ul>
                  </li>
                  <li class="submenu"><a href="#">COMPANIES</a>
                    <ul> 
                        <li><a href="companies"><i class="fa fa-briefcase"></i>Companies</a></li>  
                        <li><a href="users"> <i class="fa fa-users"></i> Company Users</a></li> 
                        <li><a href="certificate_request_pdf_upload"> <i class="fa fa-cloud-upload"></i> Company Certificates (upload)</a></li>   
                    </ul>
                  </li>
                  <li><a href="policies">POLICIES</a></li>
                  <li class="submenu"><a href="#">Endorsements</a>
                    <ul>
                        <li><a href="endorsement_request">New for Drivers</a></li>
                        <li><a href="endorsement_request_units">New for Units</a></li>
                        <li><a href="endorsement_request_adds" title="New for Loss Payee or Additional Insured">Loss Payee or Additional Insured</a></li>
                        <li style="display: none;"><a href="endorsement_month">Monthly Report to CRC</a></li>
                        <li style="display: none;"><a href="endorsement_files">Upload files to endorsements</a></li> 
                        <li><a href="brokers">Configure e-mails for Brokers</a></li>
                        <li style="display: none;"><a href="endorsements">New Endorsement like a company</a></li> 
                   </ul> 
                  </li>
                  <li class="submenu"><a href="#">CLAIMS</a>
                    <ul>
                        <li><a href="claims"><i class="fa fa-plus-circle"></i>New Claim</a></li>
                        <li><a href="claims_requests"><i class="fa fa-envelope"></i>Applications for Claims</a></li>
                    </ul>
                  </li> 
                  <li class="submenu"><a href="#">QUOTES</a>
                   <ul>
                        <li><a href="quote_formats"><i class="fa fa-file-text"></i> QUOTE FORMATS</a></li>  
                    </ul>
                </li> 
                <li style="display:none;" class="submenu"><a href="#">SYSTEM SUPPORT</a>
                    <ul>
                        <li><a href="bitacora_actividades"><i class="fa fa-list-alt"></i> SYSTEM DEVELOPMENT ACTIVITIES</a></li>
                    </ul>
                </li>
            <!-- COMPANY NAVIGATION -->	
            <?php }  if($_SESSION['acceso'] == "2"){ ?>
      			<li><a href="inicio">Home</a></li>
                <li style="display: none;"><a href="#">MY COMPANY</a></li>
                <li class="submenu"><a href="#">Catalogs</a>
                    <ul>
                        <li><a href="mydrivers"><i class="fa fa-users"></i> My Drivers' List</a></li>
                        <li><a href="myvehicles"><i class="fa fa-truck"></i> My Vehicles' List</a></li>
                    </ul>
                </li> 
                <li><a href="mypolicies">Policies</a></li> 
                <li><a href="endorsements">ENDORSEMENTS</a></li>
        		<li><a href="certificates">Certificates</a></li>
            	<li><a href="claims">Claims</a></li>  
            	<?php //<li style="display: none;"><a href="#">Quotes</a></li><li style="display: none;"><a href="#">Support</a></li> ?>	
            <?php } ?>
            <!-- END COMPANY NAVIGATION --> 
      	</ul>
        <!--<a class="res-nav_click right" href="#"><i class="fa-bars"></i></a>-->
    </div>
</nav><!--main-nav-end-->
<div id="mensaje" title="System Notification"></div> 
<div id="wait_container"><br><br><img src="images/ajax-loader.gif" alt="ajax-loader.gif"></div>
<div id="Wait" title="Solo-Trucking Message"></div>
