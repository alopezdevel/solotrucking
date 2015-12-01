<?php session_start();    
if ( !($_SESSION["acceso"] == 'C'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 

<link rel="stylesheet" href="/../../../code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="/../../../code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="/js/jquery.blockUI.js" type="text/javascript"></script>
<script type="text/javascript"> 
$(document).ready(inicio);

function inicio(){  
        $.blockUI();
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);
        if(tipo_usuario == "C"){
            validarLoginCliente(usuario_actual);
        }  
        //$("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); }); 
        llenadoGrid();  
        $.unblockUI();
        
        //functions:
        $('.only_numbers').keyup(function(){inputnumero();}); 
        $('#add_new').click(function(){driver_add();}); 
        $('#sCountry').change(function(){get_states($(this).val());});
        
    
}  
function validapantalla(usuario){
        
        if(usuario == ""  || usuario == null){
            location.href= "login.php";
        }
        
}              
function llenadoGrid(){      
    var fn_drivers = {
        domroot:"#ct_drivers",
        data_grid: "#data_grid_drivers",
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_drivers.php", 
                data:{accion:"get_drivers"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_drivers.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_drivers.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_drivers.data_grid+" tbody tr:odd").addClass('white');
                }
            }); 
        },   
    }
    fn_drivers.fillgrid();   
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
function get_states(country){
     //llenando select de estados:     
     $.post("funciones.php", { accion: "get_country", country: country},function(data){ $("#iEntidad").empty().append(data.tabla).removeAttr('disabled').removeClass('readonly');},"json");
}
//FUNCIONES PARA EL MODULO:
function driver_add(){
    
    fn_popups.resaltar_ventana('drivers_edit');
    $('#drivers_edit input, select').val('');  
    $('#btn_save').click(function(){driver_save();});          
}
function driver_save(){
    
    //Validando fields:
    var sNombre = $("#sNombre");
    var sApellido = $('#sNombre2');
    var dFechaNacimiento = $('#dFechaNacimiento');
    var iNumLicencia = $('#iNumLicencia');
    var dFechaExpiracionLicencia = $('#dFechaExpiracionLicencia');
    var iEntidad = $('#iEntidad');
    var iExperienciaYear = $('#iExperienciaYear'); 
    var dFechaContratacion = $('#dFechaContratacion'); 
    todosloscampos = $( [] ).add( sNombre ).add( sApellido ).add( dFechaNacimiento ).add( iNumLicencia ).add( dFechaExpiracionLicencia ).add( iEntidad ).add( iExperienciaYear ).add( dFechaContratacion );
    todosloscampos.removeClass( "error" );
    actualizarMensajeAlerta("");
   
    var valid = true;
            
    //field nombre
    valid = valid && checkLength( sNombre, "Name", 1, 20 );
    valid = valid && checkRegexp( sNombre, /^[0-9a-zA-ZáéíóúàèìòùÀÈÌÒÙÁÉÍÓÚñÑüÜ_\s]+$/, "The field for the Name must contain only letters." );
    
    //field Apellido
    valid = valid && checkLength( sApellido, "Last Name", 1, 20 );
    valid = valid && checkRegexp( sApellido, /^[0-9a-zA-ZáéíóúàèìòùÀÈÌÒÙÁÉÍÓÚñÑüÜ_\s]+$/, "The field for the Last Name must contain only letters." );
            
    //field FDN
    valid = valid && checkLength( dFechaNacimiento, "Date of Birthday", 1, 10);
    valid = valid && checkRegexp( dFechaNacimiento, /^(0[1-9]|[12][0-9]|3[01])[- \/.](0[1-9]|1[012])[- \/.](19|20)\d\d$/, "The date must be in DD / MM / YY format." );
    
    //field NumLicencia
    valid = valid && checkLength( iNumLicencia, "License Number", 1, 30);
    valid = valid && checkRegexp( iNumLicencia, /^[0-9]+$/, "This field allows only numeric data." );        
    
    //field dFechaExpiracionLicencia
    valid = valid && checkLength( dFechaExpiracionLicencia, "Expiration Date", 1, 10);
    valid = valid && checkRegexp( dFechaExpiracionLicencia, /^(0[1-9]|[12][0-9]|3[01])[- \/.](0[1-9]|1[012])[- \/.](19|20)\d\d$/, "The date must be in DD / MM / YY format." );        
    
    //field Entidad
    if(iEntidad.val() == ""){
        valid = false;
        actualizarMensajeAlerta( "Please choose a state" );
        iEntidad.addClass('error');
    }
    
    //field iExperienciaYear
    valid = valid && checkLength( iExperienciaYear, "Years of Experience", 1, 30);
    valid = valid && checkRegexp( iExperienciaYear, /^[0-9]+$/, "This field allows only numeric data." );
    
    //field dFechaContratacion
    valid = valid && checkLength( dFechaContratacion, "Date of Hire", 1, 10);
    valid = valid && checkRegexp( dFechaContratacion, /^(0[1-9]|[12][0-9]|3[01])[- \/.](0[1-9]|1[012])[- \/.](19|20)\d\d$/, "The date must be in DD / MM / YY format." );
            
    if ( valid ) {   }
    
} 
/*------------------ FUNCIONES PARA VALIDACIONES -------------------------*/
function onFocus(){
     $(this).css("background-color","#FFFFC0");
 }
function onBlur(){
    $(this).css("background-color","#FFFFFF");
 }
function actualizarMensajeAlerta(t) {
    mensaje = $('.mensaje_valido');
    mensaje.text(t).addClass( "alertmessage" );
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
function inputnumero(){
    if(event.shiftKey){event.preventDefault();}
    if (event.keyCode != 46 || event.keyCode != 8 || event.keyCode != 9){
            if (event.keyCode < 95) {
                    if (event.keyCode < 48 || event.keyCode > 57) {
                        event.preventDefault();
                    }
                } 
                else {
                    if (event.keyCode < 96 || event.keyCode > 105) {
                        event.preventDefault();
                    }
                }
        }
}    
</script> 
<!---- HEADER ----->
<?php include("header.php"); ?> 
<div id="layer_content" class="main-section">
    <div id="ct_drivers" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
            <h2>Drivers</h2>
        </div>
        <table id="data_grid_drivers" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'><input id="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input id="flt_name" type="text" placeholder="Name:"></td>
                <td><input id="flt_dob" type="date" placeholder="Date of Birth:"></td>
                <td><input id="flt_license" type="text" placeholder="License Number:"></td>
                <td><input id="flt_expirationdate" type="text" placeholder="Expiration Date:"></td> 
                <td><input id="flt_state" type="text" placeholder="State:"></td>
                <td><input id="flt_YOE" type="text" placeholder="Years of Experience:"></td> 
                <td><input id="flt_DOH" type="date" placeholder="Date of Hire:"></td>
                <td><input id="flt_Documents" type="text" placeholder="Document:"></td>  
                <td style='width:90px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick=""><i class="fa fa-search"></i></div>
                    <div id="add_new" class="btn-icon-2 add btn-left" title="Add +" onclick=""><i class="fa fa-plus-circle"></i></div> 
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid">ID</td>
                <td class="etiqueta_grid">Name</td>
                <td class="etiqueta_grid">DOB</td>
                <td class="etiqueta_grid">License Number</td>
                <td class="etiqueta_grid">Expiration Date</td>
                <td class="etiqueta_grid">State</td>
                <td class="etiqueta_grid">Years of Experience</td> 
                <td class="etiqueta_grid">Date of Hire</td>
                <td class="etiqueta_grid">Documents</td>
                <td class="etiqueta_grid"></td> 
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
            <tr>
                <td colspan="100%"></td>
            </tr>
        </tfoot>
        </table>
        
    </div>
</div>
<div id="dialog-confirm" title="Delete">
  <p><span class="ui-icon ui-icon-alert" ></span>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 
<!---- FORMULARIOS ------>
<div id="drivers_edit" class="popup-form">
    <div class="p-header">
        <h2>Add new Driver</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('drivers_edit');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
        <form>
            <fieldset>
                <legend>Driver Information</legend>
                <p class="mensaje_valido">&nbsp;All form fields are required.</p> 
                <div> 
                    <label>First Name: </label><input id="sNombre" type="text" placeholder="Please write a first name...">
                </div>
                <div> 
                    <label>Last Name: </label><input id="sNombre2" type="text" placeholder="Please write a last name...">
                </div>
                <div> 
                    <label>Date of Birthday: </label><input id="dFechaNacimiento" type="date" placeholder="">
                </div>
                <div> 
                    <label>License Number: </label><input id="iNumLicencia" class="only_numbers" type="text" placeholder="Please write the license number...">
                </div>
                <div> 
                    <label>Expiration Date: </label><input id="dFechaExpiracionLicencia" type="date" placeholder="">
                </div>
                <div> 
                    <div>
                      <label>State: </label>
                      <Select id="sCountry">
                        <option value="">Select an option...</option>
                        <option value="MEX">Mexico</option>
                        <option value="USA">United State</option>
                      </select>
                    </div>
                    <div>
                        <label>State: </label>
                        <Select id="iEntidad" disabled="disabled" class="readonly"><option value="">Select a country first...</option> </select>
                    </div>
                </div>
                <div> 
                    <label>Years of Experience: </label><input id="iExperienciaYear" class="only_numbers" type="text" placeholder="Please write the number of years.">
                </div>
                <div> 
                    <label>Date of Hire: </label><input id="dFechaContratacion" type="date" placeholder="">
                </div>
                <div> 
                    <label>Documents: (Please upload the file in PDF copy of driver's license.) </label>
                </div>
                <input id="id_driver" type="hidden">
                <button id="btn_save" type="button" class="btn-1" >Guardar</button> 
            </fieldset>
        </form>
    </div>
</div>
</body>

</html>
<?php } ?>