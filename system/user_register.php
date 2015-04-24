<!---- HEADER ----->
<?php include("header.php"); ?> 
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script src="/../../../code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script>
$(document).ready(inicio);
function inicio(){
    $("#btn_register").click(onInsertarUsuario)
}
function onInsertarUsuario(){
    //Variables
    var emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    mensaje = $( ".mensaje_valido" );
    var name = $("#name").val();
    var email = $("#email").val();
    var password = $("#password").val();
    var re_password = $("#recapturapassword").val();
    todosloscampos = $( [] ).add( name ).add( email ).add( password ).add( re_password );
    $("#name").focus().css("background-color","#FFFFC0");
    //focus
    $("#name").focus(onFocus);
    $("#email").focus(onFocus);
    $("#password").focus(onFocus);
    $("#recapturapassword").focus(onFocus);
    //blur
    $("#loginUser").blur(onBlur);
    $("#email").blur(onBlur);
    $("#password").blur(onBlur);
    $("#recapturapassword").blur(onBlur);
    
    //validaciones
    var valid = true;
    
    //tamano
    valid = valid && checkLength( name, "Company name", 5, 25 );
    valid = valid && checkLength( email, "E-mail", 6, 80 );
    valid = valid && checkLength( password, "password", 6, 25 );
    valid = valid && checkLength( re_password, "password", 6, 25 );
    
    //exp
    valid = valid && checkRegexp( name, /^[a-z]([0-9a-z_\s])+$/i, "Company name of a-z, 0-9, underscores, spaces and must begin with a letter." );
    valid = valid && checkRegexp( email, emailRegex, "eg. ui@test.com" );
    valid = valid && checkRegexp( password, /^([0-9a-zA-Z])+$/, "Password field only allow : a-z 0-9" );
    valid = valid && checkRegexp( re_password, /^([0-9a-zA-Z])+$/, "Password field only allow : a-z 0-9" );
 
    
    if ( valid ) {
        $.post("funciones.php", { accion: "alta_usuario", name: name , email: email, password: password, nivel: "C"},
        function(data){ 
             switch(data.error){
             case "1":   alert('Error');
                    break;
             case "0":    
                         alert("correcto");
                         $("#name").val("");
                         $("#email").val("");
                         $("#password").val("");
                         $("#recapturapassword").val("");
                         $("#name").focus();
                    break;  
             }
         }
         ,"json"); 
    }   
}
function onFocus(){
     $(this).css("background-color","#FFFFC0");
 }
 function onBlur(){
    $(this).css("background-color","#FFFFFF");
 }
 function actualizarMensajeAlerta( t ) {
      mensaje
        .text( t )
        .addClass( "alertmessage" );
      setTimeout(function() {
        mensaje.removeClass( "alertmessage", 2500 );
      }, 700 );
 }
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
</script>
<div id="layer_content" class="main-section">
	<div class="container">
		<h2>User Registration</h2>
		<form method="post" action="">
            <p class="mensaje_valido">&nbsp;Favor de llenar todos los campos.</p>
			<input  id = "name"   name="name" type="text" placeholder="Company Name:">
			<input  id = "email"name="email" type="email" placeholder="E-mail:">
			<input  id = "password"name="password" type="password" placeholder="Password:">
			<input  id = "recapturapassword"name="recapturapassword" type="password" placeholder="Repeat the Password:">
			<button id = "btn_register" class="btn_register btn_4" type="button">Register</button>
		</form>
	</div>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
