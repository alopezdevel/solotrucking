<?php 
session_start();    
if ( !($_SESSION["acceso"] == 'C'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{
?>
<!---- HEADER ----->    
<?php include("header.php"); ?>    
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script>  
 <script src="/js/jquery.blockUI.js" type="text/javascript"></script> 
 <style>
    .mensaje_valido { border: .5px solid transparent; padding: 0.1em; }
</style>      
<script>
$(document).ready(inicio);
function inicio(){ 
        //focus
        $("#address").focus(onFocus);
        $("#city").focus(onFocus);
        $("#zipcode").focus(onFocus);
        $("#country").focus(onFocus);
        $("#phone").focus(onFocus);
        $("#usdot").focus(onFocus);
        //blur
        $("#address").blur(onBlur);
        $("#city").blur(onBlur);
        $("#zipcode").blur(onBlur);
        $("#country").blur(onBlur);
        $("#phone").blur(onBlur);
        $("#usdot").blur(onBlur); 
        //codigo
         var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>  
        $.get = function(key)   {  
            key = key.replace(/[\[]/, '\\[');  
            key = key.replace(/[\]]/, '\\]');  
            var pattern = "[\\?&]" + key + "=([^&#]*)";  
            var regex = new RegExp(pattern);  
            var url = unescape(window.location.href);  
            var results = regex.exec(url);  
            if (results === null) {  
                return null;  
            } else {  
                return results[1];  
            }  
        }  
        var code = $.get("ref");
       
        var div = code.indexOf("_");  
        var total_ref = code.substring(0, div); 
        var total_suma = 10 + total_ref; 
        var codigo_bruto = code.substring(div+1);    
        var id_var = codigo_bruto.substring(10); 
        aUpdateAccount
        //variable    
        mensaje = $( ".mensaje_valido" );
        cargarCountry();
        cargarUserdata(id_var , usuario_actual);     
        $("#btn_register").click(function() { onInsertarCompania(id_var,$(this).val()); });         
        
}
function onclick(id,accion){
    alert(accion);
    
}
function cargarCountry(){
    //llenando select de estados:     
    $.post("funciones.php", { accion: "get_country"},
        function(data){ 
                $("#country").append(data.tabla);
         }
         ,"json"); 
}
function cargarUserdata(id,usuario){
    //llenando select de estados:     
    $.post("funciones.php", { accion: "get_usuario", usuario: usuario, id: id},
        function(data){
                if(data.error == "0"){
                    if(data.estatus == "1"){
                        $("#name").val(data.user);
                        $("#email").val(data.correo);
                        $("#mensaje_valido").text("CREATE");
                        $("#btn_register").val('C');
                        $("#address").focus(); 
                    }else if(data.estatus == "2"){
                        $("#name").val(data.user);
                        $("#email").val(data.correo);
                        //compania
                        $("#address").val(data.direccion);
                        $("#city").val(data.ciudad);
                        $("#phone").val(data.telefono_principal);
                        $("#zipcode").val(data.codigo_postal);
                        $("#usdot").val(data.usdot);
                        $("#country").val(data.estado);
                        $("#address").focus();
                        $("#mensaje_valido").text("UPDATE")
                        $("#btn_register").val('U');                        
                    }
                    
                    
                }else{
                    location.href= "login.php";
                    $.blockUI();
                } 
                
                
         }
         ,"json"); 
}
function onInsertarCompania(id,accion){
    //Variables
    var address = $("#address");
    var city = $("#city");
    var zipcode = $("#zipcode");
    var country = $("#country");
    var phone = $("#phone");
    var usdot = $("#usdot");
    if(accion == "U"){
        accion = "update_company"
    }else if(accion = "C"){
        accion = "add_company"
    }  
    
    todosloscampos = $( [] ).add( address ).add( city ).add(zipcode).add(country).add(phone).add(usdot);
    todosloscampos.removeClass( "error" );
    
    
    $("#address").focus().css("background-color","#FFFFC0");
    actualizarMensajeAlerta( "" ); 
   
    
    //validaciones
    var valid = true;
    
    //tamano
    valid = valid && checkLength( address, "", 6, 25 );
    //valid = valid && checkRegexp( address, /^[0-9]([0-9a-z_\s])+$/i, "Address of a-z, 0-9, underscores, spaces and must begin with a letter." );
    
    valid = valid && checkLength( country, ""); 
    
    valid = valid && checkLength( city, "City", 6, 25 );
    valid = valid && checkRegexp( city, /^[a-z]([0-9a-z_\s])+$/i, "City name of a-z, 0-9, underscores, spaces and must begin with a letter." );
    
    valid = valid && checkLength( zipcode, "Zip Code", 1, 5 );
    valid = valid && checkRegexp( zipcode, /^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/, "The zip code is not valid." );
    
    valid = valid && checkLength( phone, "Phone", 10, 10 );
    valid = valid && checkRegexp( phone, /^[0-9-()+]{3,20}/, "Please enter a Phone number Valid: Must contain 0-9." );
    
    valid = valid && checkLength( usdot, "US DOT", 5, 6 );
    valid = valid && checkRegexp( usdot, /^[0-9-()+]{3,20}/, "Please enter a US DOT number Valid: Must contain 0-9." );
    //exp
    
    
    if ( valid ) {
        $.post("funciones.php", { 
            accion: accion, 
            userid:id,  
            address: address.val(),
            city: city.val(),
            zipcode: zipcode.val(),
            country: country.val(),
            phone: phone.val(),
            usdot: usdot.val()
        },
        function(data){ 
             switch(data.error){
             case "1":   alert(data.mensaje);
                    break;
             case "0":    
                         alert("Your information has been successfully registered.");
                         location.href= "inicio.php";
                         $.blockUI();
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
        <div class="page-title">
            <h1>Account</h1>
            <h2>Update Your Company Information</h2>
        </div>
        <form method="post" action="">
        <fieldset name="CompanyInformation">
        <legend>Company Information</legend>
            <p class="mensaje_valido">&nbsp;All form fields are required.</p>
            <div class="field_item">
                <label>Company Name:</label> 
                <input tabindex="1" id="name" name="companyname" type="text" placeholder="* Company Name:" maxlength="100" readonly>
            </div>
            <div class="field_item">
                <label>E-mail:</label>  
                <input tabindex="2" id="email" name="Email" type="email" placeholder="* E-mail:" maxlength="100" readonly>
            </div>
            <div class="field_item">
                <label>Address:</label>  
                <input tabindex="3" id="address" name="address" type="text" placeholder="* Address:" maxlength="100">
            </div>
            <div class="field_item">
                <div class="col_3 left ">
                    <label>City:</label>  
                    <input tabindex="4" id="city" name="city" type="text"  placeholder="* City:" maxlength="100" required> 
                </div> 
                <div class="col_3 left ">  
                    <label>Zip Code:</label>   
                    <input tabindex="5" id="zipcode" class="numb" name="ZipCode" type="text" maxlength="5" placeholder="Zip Code:">                
                </div>
                <div class="col_3 left "> 
                    <label>State:</label>  
                    <select tabindex="6" id="country" name="contry"></select>
                </div>
            </div>
            <div class="field_item">
                <label>Phone:</label>  
                <input tabindex="7" id="phone" class="numb" name="phone1" type="tel" placeholder="* Primary Phone:" maxlength="10">
            </div>
            <div class="field_item"> 
                <label>USDOT:</label> 
                <input tabindex="8" id="usdot" class="numb" name="usdot" type="text" placeholder="* USDOT#:" maxlength="6">
            </div>
            <button id="btn_register" type="button" class="btn-1" ><p id="mensaje_valido" font size="6" ></p></button>
        </fieldset>
        </form>
    </div>

<!---- FOOTER ----->
<?php include("footer.php"); ?> 
 </div> 
</body>

</html>
<?php }?>
