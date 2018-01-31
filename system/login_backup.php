<?php
    session_start();
    session_unset();
    session_destroy();
    include("cn_usuarios.php");
    //include("libs_header.php");
?>                     
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script type='text/javascript'>
    var expr = /^[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/;
    var expr1 = /^[a-zA-Z]*$/;      
    $(document).ready(inicio);
function inicio(){  
     //variables
     mensaje = $( ".mensaje_valido" );
     usuario = $( "#loginUser" ),
     password = $( "#loginPassword" ),
     todosloscampos = $( [] ).add( usuario ).add( password );
     $("#loginUser").focus().css("background-color","#FFFFC0");     
     $("#button_aceptar").click(onValidarAcceso);
     $("#loginUser").focus(onFocus); 
     $("#loginPassword").focus(onFocus); 
     $("#loginUser").blur(onBlur);
     $("#loginPassword").blur(onBlur);
     $("#loginPassword").keyup(function(event){
         if(event.keyCode == '13'){
            event.preventDefault;
            onValidarAcceso(); 
         }
     });
}
function onValidarAcceso(){   
     var emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
     actualizarMensajeAlerta( "All form fields are required." ); 
     todosloscampos.removeClass( "error" );
     $("#loginUser").removeClass( "error" );
     $("#loginPassword").removeClass( "error" );
     var valid = true; 
     
     valid = valid && checkLength( $('#loginUser'), "user", 3, 80 );
     //valid = valid && checkRegexp( $('#loginUser'), emailRegex, "eg. ui@solotrucking.com" );
     
     //valid = valid && checkLength( $("#loginPassword"), "password", 1, 9 );
     //valid = valid && checkRegexp( $("#loginPassword"), /^([0-9a-zA-Z])+$/, "Password field only allow : a-z 0-9" );
     
     if($("#loginPassword").val() == ""){
        valid = false; 
        $("#loginPassword").addClass('error');
        actualizarMensajeAlerta("Please verify if all fields has a valid value.");
     }
     if(valid){conexion($("#loginUser").val(), $("#loginPassword").val());}
}
function conexion(u, p){
     $.post("funciones_login.php", { accion: "conexion", usuario: u , password: p}, 
     function(data){ 
         switch(data.respuesta){
         case "0":  $("#loginPassword").val("");
                    $("#loginPassword").focus();
                    $("#loginUser").addClass( "error" );
                    $("#loginPassword").addClass( "error" );
                    actualizarMensajeAlerta("Error: The User " +  $("#loginUser").val() + " does not exist" ); 
                break;
         case "1":   
                     location.href= "inicio.php?type=88e5542d2cd5b7f86cd6c204dc77fb523fb719071b2b08cfd7cbfbcadb365af1c8c9ba63";
                break;
         case "2":  $("#loginPassword").val("");
                    $("#loginPassword").focus();
                    $("#loginPassword").addClass("error");
                    actualizarMensajeAlerta(data.mensaje);
                break;  
         }
     }
     ,"json");
}
function onFocus(){$(this).css("background-color","#FFFFC0");}
function onBlur(){$(this).css("background-color","#FFFFFF");}
function checkRegexp( o, regexp, n ) {
    if ( !( regexp.test( o.val() ) ) ) {
        actualizarMensajeAlerta( n );
        o.addClass( "error" );
        o.focus();
        return false;
    } else {                     
        return true;        
    }
}
function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
        actualizarMensajeAlerta( "Length of " + n + " must be between " + min + " and " + max + "."  );
        o.addClass( "error" );
        o.focus();
        return false;    
    } else {             
        return true;                     
    }                    
}
function actualizarMensajeAlerta(t) {
      mensaje = $('.mensaje_valido'); 
      mensaje.text(t).addClass( "alertmessage" );
      setTimeout(function() {
        mensaje.removeClass( "alertmessage", 2500 );
      }, 700 );
 }  
 

</script>
<!DOCTYPE html>
<html>

<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title>SoloTrucking - System Access</title>
<link rel="icon" href="../images/favicon.png" type="image/png">
<link rel="stylesheet" href="css/login.css" type="text/css">
    <!-----SLIDER HOMEPAGE----->
    <link rel='stylesheet' id='camera-css'  href='../camera/css/camera.css' type='text/css' media='all'>     
    <script type='text/javascript' src='../camera/scripts/jquery.min.js'></script>
    <script type='text/javascript' src='../camera/scripts/jquery.mobile.customized.min.js'></script>
    <script type='text/javascript' src='../camera/scripts/jquery.easing.1.3.js'></script> 
    <script type='text/javascript' src='../camera/scripts/camera.js'></script> 
    <script type='text/javascript' src='../camera/scripts/script.js'></script>
    <style type="text/css">
        .mensaje_valido { border: .5px solid transparent; padding: 0.1em; }
    </style>
</head>
<?php if ($conexion) {    ?>
<body>
<div id="layer_login">
    <img alt="" src="images/login/img-logo-login.png" alt="logo">
    <form method="post" action="" >
        <p class="mensaje_valido">&nbsp;All form fields are required.</p>
        <input id="loginUser" class="user" name="user" type="text" placeholder="User" <?php if(isset($_COOKIE['USER'])){echo 'value="'.$_COOKIE['USER'].'"';}else{echo 'value=""';} ?> >
        <input id="loginPassword" class="pass" name="password" type="password" placeholder="Password" maxlength="9">
        <button id="button_aceptar" class="btn_login" type="button">LOGIN</button>
        <p class="m_inf" style="display:none"><a href="#">Forgot your password?</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;         |&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;          
        <a href="#">Create Account</a></p>
    </form>
</div>
<!--- SLIDER ----->
<div id="slider-container" class="clear">
    <div class="fluid_container">
        <div class="camera_wrap camera_emboss" id="camera_wrap_3">
            <div data-src="../camera/images/slides/1.jpg"></div>
            <div data-src="../camera/images/slides/2.jpg"></div>
            <div data-src="../camera/images/slides/3.jpg"></div>
            <div data-src="../camera/images/slides/4.jpg"></div>
        </div><!-- #camera_wrap_3 -->
    </div><!-- .fluid_container -->
</div>
<!--- TERMINA SLIDER ----->
<section class="section-phone">
    <p>Need Help? CALL  (956) 606-4478</p>
</section>
<div class="copyright">SoloTrucking 2015 . � All rights reserved.</div>
</body>
<?php }else{ ?>
<?php  } ?>
</html>