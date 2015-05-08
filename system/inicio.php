<?php session_start();    
if ( !(($_SESSION["acceso"] == 'C' || $_SESSION["acceso"] == 'U')  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<!---- HEADER ----->
<?php include("header.php"); ?> 
    
    <!---- Fancybox -------->
    <script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="/fancybox/source/jquery.fancybox.js"></script>
    <link rel="stylesheet" type="text/css" href="/fancybox/source/jquery.fancybox.css" media="screen">
    
    <script type="text/javascript" src="/fancybox/fancy.js"></script> 
    <script src="/js/jquery.blockUI.js" type="text/javascript"></script>
    <script>    
            
    $(document).ready(inicio);
    function inicio(){ 
       
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        if(tipo_usuario == "C"){
        $.blockUI();
        }
        validapantalla(usuario_actual);
        if(tipo_usuario == "C"){
            validarLoginCliente(usuario_actual);
        }  
        $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); });         
        
    }                                                                              
    function validapantalla(usuario){
        
        if(usuario == ""  || usuario == null){
            location.href= "login.php";
        }
        
    }
    
    function actualizarCliente(usuario){
        $.post("funciones.php", { accion: "validar_cliente_acceso", usuario: usuario},
        function(data){ 
                                // 
         if(data.error == "0"){
             codigo_1 = data.codigo.substring(0, 10);
             codigo_2 = data.codigo.substring(5, 15);
             total_len = data.consecutivo.length;
             location.href= "company_register.php?ref="+ total_len + '_'  +  codigo_1 +  data.consecutivo;
         }else{
             //error
         }   
         
     }
     ,"json");
    }
    
    function validarLoginCliente(usuario){
        //$.blockUI({ message: $('#domMessage') });
        $.post("funciones.php", { accion: "validar_cliente_acceso", usuario: usuario},
        function(data){ 
                                // 
         if(data.error == "0"){  
             switch(data.estatus){                                                                                                
                case "0":  
                                    //location.href= "login.php";
                                    break;
                                    
                case "1":           $.unblockUI();                          
                                    codigo_1 = data.codigo.substring(0, 10);
                                    codigo_2 = data.codigo.substring(5, 15);
                                    total_len = data.consecutivo.length;
                                    location.href= "company_register.php?ref="+ total_len + '_'  +  codigo_1 +  data.consecutivo;                                    
                                    break;
                case "2":          $.unblockUI();                          
                                    /*codigo_1 = data.codigo.substring(0, 10);
                                    codigo_2 = data.codigo.substring(5, 15);
                                    total_len = data.consecutivo.length;
                                    location.href= "company_register.php?ref="+ total_len + '_'  +  codigo_1 +  data.consecutivo;  */
                                    break;
                 
             }
         }else{
             //error
         }   
         
     }
     ,"json");
        
        
    }
    </script>
     
<div id="layer_content" class="main-section">
	<div class="container">
		<h2 class="txt-center">Welcome to Solo-Trucking System</h2>
		<div style="clear:both;padding-top: 40px;">
        <div class="col_3 left">
            <div class="bann">
                <h3>Certificates</h3>
                <br>
                <a class="fancybox-certificate" href="javascript:;"><span><img src="../images/cont/btn_certificate_a.gif" border="0"  alt="btn_certificate_a.gif (5,626 bytes)"></span></a>
            </div>
        </div>
        <div class="col_3 left">
            <div class="bann">
                <h3>Quotes</h3>
                <br>
                <ul>
                    <li><a href="#"><span>»</span>Get a Quote</a> </li>
                    <li><a href="#"><span>»</span>Consult One</a></li>
                </ul>
            </div>
        </div>
        <div class="col_3 left">
            <div class="bann">
                <h3>Endorsements</h3>
                <br>
                <ul>
                    <li><a href="#"><span>»</span>Request One</a></li>
                </ul>
            </div>
        </div>
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
