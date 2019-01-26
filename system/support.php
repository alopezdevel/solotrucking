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
    <?php if($_SESSION['acceso'] != "2"){ //USUARIOS SOLO-TRUCKING ?>
        <div>
            <div class="bann">
                <img src="images/home/img_faqs_homepage.png" border="0" width="262" height="46" alt="img_faqs_homepage.png">  
                <h3>Frequently asked questions / Preguntas frecuentes</h3>
                <br>
                <ul>
                    <li><a href="documentos/support/CREAR%20Y%20ENVIAR%20ENDOSOS%20TIPO%20LOSS%20PAYEE%20OR%20ADDITIONAL%20INSURED.pdf" target="_blank">¿Cómo puedo agregar un nuevo endoso a LOSS PAYEE o ASEGURADO ADICIONAL?</a></li>
                    <li><a href="documentos/support/how_can_add_a_new_company(ESP).pdf" target="_blank">¿Cómo puedo agregar una nueva Compa&ntilde;ia?</a></li>
                    <li><a href="documentos/support/how_can_add_a_new_user(ESP).pdf">¿Cómo puedo agregar un nuevo usuario?</a></li> 
                    <li><a href="documentos/support/how_can_upload_a_new_certificate(ESP).pdf">¿Cómo puedo subir un nuevo certificado?</a></li> 
                    <li><a href="documentos/support/How_can_upload_lists_of_drivers_and_units.pdf" target="_blank">¿Cómo puedo subir las listas de drivers y unidades?</a></li> 
                </ul>
            </div>
        </div>
    <?php }?>
    <?php if($_SESSION['acceso'] == "2"){ //USUARIOS COMPANIES?>
        <div class="col_3 left">
            <div class="bann">
                <img src="images/home/img_monitor_homepage.png" border="0" width="344" height="254" alt="img_monitor_homepage.png">
                <h3>The NEWEST</h3>
                <p>Section Underconstruction</p>
            </div>
        </div>
        <div class="col_3 left">
            <div class="bann">
                <img src="images/home/img_gears_homepage.png" border="0" width="160" height="135" alt="img_gears_homepage.png">  
                <h3 style="color:#a22c2c;">Quick Access</h3>
                <br>
                <ul style="list-style:none;">
                    <li><a href="mydrivers"><i class="fa fa-users"></i> My Drivers' list</a></li> 
                    <li><a href="myvehicles"> <i class="fa fa-truck"></i> Me Vehicles' list</a></li>
                    <li><a href="mypolicies"><i class="fa fa-file-text"></i> My Policies</a></li> 
                    <li><a href="endorsements"><i class="fa fa-plus-circle"></i> Create a New Endorsement</a></li>
                    <li><a href="certificates"> <i class="fa fa-cloud-upload"></i> Certificate Layouts</a></li>
                </ul>
            </div>
        </div>
        <div class="col_3 left">
            <div class="bann">
                <img src="images/home/img_faqs_homepage.png" border="0" width="262" height="46" alt="img_faqs_homepage.png">  
                <h3>Frequently asked questions</h3>
                <br>
                <p>Section Underconstruction</p>
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
