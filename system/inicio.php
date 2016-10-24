<?php session_start();    
if ( !($_SESSION["acceso"] != '')  && ($_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL)){ 
    //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<!---- HEADER ----->
<?php include("header.php"); ?>     
    <script>    
            
    $(document).ready(inicio);
    function inicio(){ 
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        if(usuario_actual == ""  || usuario_actual == null){location.href= "login.php";} 
    }                                                                              
    </script>
     
<div id="layer_content" class="main-section">
	<div class="container">
		<h2 class="txt-center">Welcome to Solo-Trucking System</h2>
        <p style ="text-align:center;">The System is UNDER CONSTRUCTION</p>
		<div style="clear:both;padding-top: 40px;">
        <?php if($_SESSION['acceso'] == "U"){  ?>
                <div class="col_3 left">
                    <div class="bann">
                        <h3>Certificates</h3>
                        <br>
                        <ul>
                            <li><a href="certificate_request_pdf_upload"><span><i class="fa fa-upload color-blue"></i> </span>Upload Certificates</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col_3 left">
                    <div class="bann">
                        <h3>Quotes</h3>
                        <br>
                        <ul>
                            <li><a href="#"><span><i class="fa fa-external-link-square color-blue"></i> </span>Get a Quote</a> </li>
                            <li><a href="#"><span><i class="fa fa-eye color-blue"></i> </span>Consult One</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col_3 left">
                    <div class="bann">
                        <h3>Endorsements</h3>
                        <br>
                        <ul>
                            <li><a href="#"><span><i class="fa fa-external-link-square color-blue"></i> </span>Request One</a></li>
                        </ul>
                    </div>
                </div>
        <?php }?>
        <?php if($_SESSION['acceso'] == "C"){  ?>
                <div class="col_3 left" style="margin: 0 auto; float:none!important;">
                    <div class="bann">
                        <h3>Certificates</h3>
                        <br>
                        <p>Now you can edit your certificate:</p>
                        <ul>
                            <li><a href="certificate_request_company"><span><i class="fa fa-external-link-square color-blue"></i> </span> Go Now!</a></li>
                        </ul>
                    </div>
                </div>
        <?php }?>
        </div>
	</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 
</div>
<div id="domMessage" style="display:none;"> 
    <h1>We are processing your request.  Please be patient.</h1> 
</div> 
</body>

</html>
<?php } ?>
