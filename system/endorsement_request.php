<!---- HEADER ----->
<?php include("header.php"); ?> 
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script>
$(document).ready(inicio);
function inicio(){
    //variable 
    mensaje = $( ".mensaje_valido" );
    $("#btn_register").click(onInsertarCompania);
    cargarCountry();
    cargarUserdata();
    
    
    
}
function cargarCountry(){
    //llenando select de estados:
    $.post("funciones.php", { accion: "get_country"},
        function(data){ 
                $("#country").append(data.tabla);
         }
         ,"json"); 
}
function onInsertarCompania(){
    //Variables
    var address = $("#address");
    var city = $("#city");
    var zipcode = $("#zipcode");
    var country = $("#country");
    var phone = $("#phone");
    var usdot = $("#usdot");  
    
    todosloscampos = $( [] ).add( address ).add( city ).add(zipcode).add(country).add(phone).add(usdot);
    todosloscampos.removeClass( "error" );
    
    
    $("#address").focus().css("background-color","#FFFFC0");
    actualizarMensajeAlerta( "" ); 
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
            accion: "add_company", 
            userid:"15",  
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
                         alert("The application for endorsement been performed successfully.");
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
            <h1>Endorsements</h1>
            <h2>Request for endorsement</h2>
        </div>
        <form method="post" action="">
        <p class="mensaje_valido">&nbsp;All form fields are required.</p>
        <fieldset name="DriversInformation">
        <legend>Type of insurance policy</legend>
        <div class="center txt-center">
        	<div class="left col_3"><input name="policytype" type="radio" value="1"><label>Physical Damage</label></div>
        	<div class="left col_3"><input name="policytype" type="radio" value="2"><label>Motor Truck Cargo</label></div>
			<div class="left col_3"><input name="policytype" type="radio" value="3"><label>Auto Liability</label></div>
        </div>
        </fieldset>
        <fieldset name="DriversInformation">
        <legend>Information for drivers</legend>
            <div class="field_item chofer"> 
                <input tabindex="1" id="chofer" name="Chofer" type="text" placeholder="* Chofer Name:" maxlength="100">
                <input tabindex="2" id="fdn" name="Fdn" type="text" placeholder="* FDN:" maxlength="100"> 
                <input tabindex="3" id="exp" name="Exp" type="text" placeholder="* EXP:" maxlength="100">  
                <input tabindex="4" id="license" name="License" type="text" placeholder="* License number:" maxlength="100">
                <div class="uploadfile"><label>Upload license copy (.PDF)</label><input id="copylicense" tabindex="5" name="CopyLicense" type="file"></div>     
            </div>
        </fieldset>
        <button id="add_chofer" class="btn_3 right">Add Chofer +</button> 
        <br>
        <fieldset name="UnitsInformation">
        <legend>Information for Units</legend>   
            <div class="field_item unit"> 
                <input tabindex="1" id="year" name="UnitYear" type="text" placeholder="* Year:" maxlength="100">
                <input tabindex="2" id="model" name="Model" type="text" placeholder="* Model:" maxlength="100"> 
                <input tabindex="3" id="vin" name="Vin" type="text" placeholder="* VIN Number:" maxlength="100">  
                <div class="uploadfile"><label>Upload Unit title copy (.PDF)</label><input id="copytitle" tabindex="5" name="CopyTitle" type="file"></div>
            </div>
            <button id="add_unit" class="btn_3 right">Add Unit +</button> 
        </fieldset>  
            <br><br> 
            <button id="btn_register" type="button" class="btn_2" style="margin: 15px auto 0px;left: 50%;position: absolute;margin-left: -100px;">Create Endorsement</button>
            <br><br> 
        </form>
    </div>

<!---- FOOTER ----->
<?php include("footer.php"); ?> 
 </div> 
</body>

</html>
