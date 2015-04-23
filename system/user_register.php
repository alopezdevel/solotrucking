<!---- HEADER ----->
<?php include("header.php"); ?> 
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script>
$(document).ready(inicio);
function inicio(){
    $("#btn_register").click(onInsertarUsuario)
}
function alta_usuario(onInsertarUsuario){
    //Variables
    var name = $("#name").val();
    var email = $("#email").val();
    var password = $("password").val();
    var re_password = $("#recapturapassword").val();
    $.post("funciones.php", { accion: "alta_usuario", name: name , email: email, password: password, nivel: "C"},
    function(data){ 
         switch(data.error){
         case "1":   alert('Error');
                break;
         case "0":    
                     alert("correcto");
                break;  
         }
     }
     ,"json");    
}
</script>
<div id="layer_content" class="main-section">
	<div class="container">
		<h2>User Registration</h2>
		<form method="post" action="">
			<input name="name" type="text" placeholder="Company Name:">
			<input name="email" type="email" placeholder="E-mail:">
			<input name="password" type="password" placeholder="Password:">
			<input name="recapturapassword" type="password" placeholder="Repeat the Password:">
			<button class="btn_register" type="button">Register</button>
		</form>
	</div>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
