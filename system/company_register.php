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
    var emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    var name = $("#name");
    var email = $("#email");
    var address = $("#address");
    var city = $("#city");
    var zipcode = $("#zipcode");
    var country = $("#country");
    var phone = $("#phone");
    var usdot = $("#usdot");  
    
    todosloscampos = $( [] ).add( name ).add( email ).add( address ).add( city ).add(zipcode).add(country).add(phone).add(usdot);
    todosloscampos.removeClass( "error" );
    
    
    $("#name").focus().css("background-color","#FFFFC0");
    actualizarMensajeAlerta( "" ); 
    //focus
    $("#name").focus(onFocus);
    $("#email").focus(onFocus);
    $("#address").focus(onFocus);
    $("#city").focus(onFocus);
    $("#zipcode").focus(onFocus);
    $("#country").focus(onFocus);
    $("#phone").focus(onFocus);
    $("#usdot").focus(onFocus);
    //blur
    $("#name").blur(onBlur);
    $("#email").blur(onBlur);
    $("#address").blur(onBlur);
    $("#city").blur(onBlur);
    $("#zipcode").blur(onBlur);
    $("#country").blur(onBlur);
    $("#phone").blur(onBlur);
    $("#usdot").blur(onBlur);
    
    //validaciones
    var valid = true;
    
    //tamano
    valid = valid && checkLength( name, "Company name", 5, 25 );
    valid = valid && checkRegexp( name, /^[a-z]([0-9a-z_\s])+$/i, "Company name of a-z, 0-9, underscores, spaces and must begin with a letter." );
    
    valid = valid && checkLength( email, "E-mail", 6, 80 );
    valid = valid && checkRegexp( email, emailRegex, "eg. ui@solotrucking.com" );
    
    valid = valid && checkLength( address, "", 6, 25 );
    valid = valid && checkRegexp( address, /^[0-9]([0-9a-z_\s])+$/i, "Company name of a-z, 0-9, underscores, spaces and must begin with a letter." );
    
    valid = valid && checkLength( city, "City", 6, 25 );
    valid = valid && checkRegexp( city, /^[a-z]([0-9a-z_\s])+$/i, "City name of a-z, 0-9, underscores, spaces and must begin with a letter." );
    
    valid = valid && checkLength( zipcode, "Zip Code", 1, 5 );
    valid = valid && checkRegexp( zipcode, /^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/, "The zip code is not valid." );
    
    valid = valid && checkLength( phone, "Phone", 10, 10 );
    valid = valid && checkRegexp( phone, /^[0-9-()+]{3,20}/, "Phone of 0-9." );
    
    valid = valid && checkLength( usdot, "US DOT", 5, 6 );
    valid = valid && checkRegexp( usdot, /^[0-9-()+]{3,20}/, "US DOT of 0-9." );
    //exp
    
    
    if ( valid ) {
        $.post("funciones.php", { 
            accion: "add_company", 
            name: name.val() , 
            email: email.val(), 
            address: address.val(),
            city: city.val(),
            zipcode: zipcode.val(),
            country: country.val(),
            phone: phone.val(),
            usdot: usdot.val()
        },
        function(data){ 
             switch(data.error){
             case "1":   alert('Error');
                    break;
             case "0":    
                         alert("correcto");
                         $("#name").val("");
                         $("#email").val("");
                         $("#address").val("");
                         $("#city").val("");
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
        <div class="page-title">
            <h1>Account</h1>
            <h2>Update Your Company Information</h2>
        </div>
        <form method="post" action="">
        <fieldset name="CompanyInformation">
        <legend>Company Information</legend>
            <div class="field_item"> 
                <input id="name" name="companyname" type="text" placeholder="* Company Name:" maxlength="100">
            </div>
            <div class="field_item"> 
                <input id="address" name="address" type="text" placeholder="* Address:" maxlength="100">
            </div>
            <div class="field_item"> 
                <input id="city" name="city" type="text"  placeholder="* City:" maxlength="100" style="width:33%;float:left;clear:none;" required>    
                <input id="zipcode" class="numb" name="ZipCode" type="text" maxlength="5" placeholder="Zip Code:" style="width:33%;float:right;clear:none;">                
                <select id="country" name="contry" style="width:33%!important;float:right;clear:none;margin-right:5px;">
                    <option value="">Select a Country</option> 
                </select>
            </div>
            <div class="field_item"> 
                <input id="email" name="Email" type="email" placeholder="* E-mail:" maxlength="100">
            </div>
            <div class="field_item"> 
                <input id="phone" class="numb" name="phone1" type="tel" placeholder="* Primary Phone:" maxlength="10">
            </div>
            <div class="field_item"> 
                <input id="usdot" class="numb" name="usdot" type="text" placeholder="* USDOT#:" maxlength="100">
            </div>
            <button id="btn_register" type="button" class="btn-1">Create Account</button>
        </fieldset>
        </form>
    </div>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
