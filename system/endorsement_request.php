<!---- HEADER ----->
<?php include("header.php"); ?> 
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script>
var nextDriver = 1;
$(document).ready(inicio);
function inicio(){
 $("#add_chofer").click(AgregarDriver);    
 $("#btn_register").click(AgregarEndorsement);
}

function AgregarDriver(){    
    nextDriver++;
    campo = ' <br><br><br> <legend>Information for drivers #'+ nextDriver+ '</legend> <div class="field_item chofer"> '
    campo = campo + '<input tabindex="1" id="chofer" name="Chofer[]" type="text" placeholder="* Chofer Name:" maxlength="100"> </div> ';
    campo = campo + '<input tabindex="2" id="fdn" name="Fdn" type="text" placeholder="* Birth Date:" maxlength="100">'; 
    campo = campo + '<input tabindex="3" id="exp" name="Exp" type="text" placeholder="* Expiration Date:" maxlength="100">';
    campo = campo + '<input tabindex="4" class="number" id="license" name="License" type="text" placeholder="* License number:" maxlength="100">';
    campo = campo + '<div class="center txt-center"><div class="left col_2"><input name="accion" type="radio" value="addchofer" checked="checked"><label class="lbl-radio">Add</label></div><div class="left col_2"><input name="accion" type="radio" value="deletechofer"><label class="lbl-radio">Delete</label></div></div>';
    campo = campo + '<div class="uploadfile"><label>Upload license copy (.PDF)</label><input id="copylicense" tabindex="5" name="CopyLicense" type="file"></div></div><br><br><br>  ';
    $("#drivers").append(campo);
}
function AgregarEndorsement(){  
    //validacion choofer
    $('input[name="Chofer\\[\\]"]').each(function() {        
        if(this.value == ""){
             $(this).focus();            
            $(this).addClass( "error" );
        }
        
    });
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
        	<div class="left col_3"><input name="PD" type="checkbox" value="1"><label class="lbl-radio">Physical Damage</label></div>
        	<div class="left col_3"><input name="Cargo" type="checkbox" value="2"><label class="lbl-radio">Motor Truck Cargo</label></div>
			<div class="left col_3"><input name="Auto" type="checkbox" value="3"><label class="lbl-radio">Auto Liability</label></div>
        </div>
        </fieldset>
        
        
        <fieldset name="DriversInformation">
        <legend>Information for drivers</legend>
            <div class="field_item chofer"> 
                <input tabindex="1" id="chofer" name="Chofer[]" type="text" placeholder="* Chofer Name:" maxlength="100">
                <input tabindex="2" id="fdn" name="Fdn" type="text" placeholder="* Birth Date:" maxlength="100"> 
                <input tabindex="3" id="exp" name="Exp" type="text" placeholder="* Expiration Date:" maxlength="100">  
                <input tabindex="4" class="number" id="license" name="License" type="text" placeholder="* License number:" maxlength="100">
                <div class="center txt-center">
                    <div class="left col_2"><input id= "accion" name="accion" type="radio" value="addchofer" checked="checked" ><label class="lbl-radio">Add</label></div>
                    <div class="left col_2"><input id= "accion" name="accion" type="radio" value="deletechofer"><label class="lbl-radio">Delete</label></div>
                </div>
                <div class="uploadfile"><label>Upload license copy (.PDF)</label><input id="copylicense" tabindex="5" name="CopyLicense" type="file"></div>     
            </div>
            <br><br><br> 
             <div id="drivers">        
            </div>
                  
        </fieldset>
        <input type="button"  id="add_chofer" value="Add Chofer +">  
        
        <br><br><br> 
        
        
        
        <fieldset name="UnitsInformation">
        <legend>Information for Units</legend>   
            <div class="field_item unit"> 
                <input tabindex="1" id="year" name="UnitYear" type="text" placeholder="* Year:" maxlength="100">
                <input tabindex="2" id="model" name="Model" type="text" placeholder="* Model:" maxlength="100"> 
                <input tabindex="3" id="vin" name="Vin" type="text" placeholder="* VIN Number:" maxlength="100">
                <div class="center txt-center">
                    <div class="left col_2"><input name="accionunit" type="radio" value="addunit"><label class="lbl-radio">Add</label></div>
                    <div class="left col_2"><input name="accionunit" type="radio" value="deleteunit"><label class="lbl-radio">Delete</label></div>
                </div>  
                <div class="uploadfile"><label>Upload Unit title copy (.PDF)</label><input id="copytitle" tabindex="5" name="CopyTitle" type="file"></div>
            </div>
            <button id="add_unit" class="btn_3 right">Add Unit +</button> 
        </fieldset>  
        <fieldset>
        <legend>Reefer Breakdown</legend>
            <div class="center txt-center">
                <div class="left col_2"><input name="rb" type="radio" value="1"><label class="lbl-radio">Yes</label></div>
                <div class="left col_2"><input name="rb" type="radio" value="0"><label class="lbl-radio">No</label></div>
            </div>
        </fieldset>
        <fieldset name="DriversInformation">
        <legend>Trailer Interchange</legend>
        <div class="center txt-center">
            <div class="left col_4"><input name="tic" type="radio" value="1"><label class="lbl-radio">$ 15,000</label></div>
            <div class="left col_4"><input name="tic" type="radio" value="2"><label class="lbl-radio">$ 20,000</label></div>
            <div class="left col_4"><input name="tic" type="radio" value="3"><label class="lbl-radio">$ 25,000</label></div>
            <div class="left col_4"><input name="tic" type="radio" value="4"><label class="lbl-radio">$ 30,000</label></div> 
        </div>
        </fieldset>
            <br><br>             
            <input id="btn_register" type="button" class="btn_2" style="margin: 15px auto 0px;left: 50%;position: absolute;margin-left: -100px;" value="Request Endorsement">
            <br><br> 
        </form>
    </div>

<!---- FOOTER ----->
<?php include("footer.php"); ?> 
 </div> 
</body>

</html>
