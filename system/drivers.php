<?php session_start();    
if ( !($_SESSION["acceso"] == 'C'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/cupertino/jquery-ui.css">          
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
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
        $('.only_numbers').keydown(inputnumero());
        $('#filter_now').click(function(){filter_data();}); 
        $('#add_new').click(function(){driver_add();}); 
        $('#btn_save').click(function(){driver_save();});
        $('.btn_delete').click(function(){driver_delete($(this).attr('id'));}); 
        $('#sCountry').change(function(){get_states($(this).val());});
        
        //Filtrado con la tecla enter
        $('#data_grid_drivers #grid-head1 input').keyup(function(event){
            if (event.keyCode == '13') {
                    event.preventDefault();
                    filter_data();
            }
            if(event.keyCode == '27'){
                event.preventDefault();
                $(this).val(''); 
                filter_data();
            }
        });
       
}  
function validapantalla(usuario){
        
        if(usuario == ""  || usuario == null){
            location.href= "login.php";
        }
        
}              
function llenadoGrid(filters){      
    var domroot = "#ct_drivers";
    var data_grid = "#data_grid_drivers";  
    $.ajax({             
        type:"POST", 
        url:"funciones_drivers.php", 
        data:{
            accion:"get_drivers",
            filters: filters 
        },
        async : true,
        dataType : "json",
        success : function(data){                               
            $(data_grid + " tbody").empty().append(data.tabla);
            $(data_grid + " tbody tr:even").addClass('gray');
            $(data_grid + " tbody tr:odd").addClass('white');
        }
    }); 
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
     $.post("funciones.php", { accion: "get_country", country: country},function(data){ $("#iEntidad").empty().append(data.tabla).removeAttr('disabled').removeClass('readonly');validar_nacionalidad(country);},"json");
}
function validar_nacionalidad(country){
     if(country == "USA"){
        $('#drivers_edit .file_mvr').show('fast'); 
     }else{
       $('#drivers_edit .file_mvr').hide('fast');
       $('#drivers_edit .file_mvr #sMVRPDF').val('');   
     }
}
//FUNCIONES PARA EL MODULO:
function driver_add(){
    
    fn_popups.resaltar_ventana('drivers_edit');
    $('#drivers_edit input, select').val('');           
}
function driver_save(){
    
    //Validando fields:
    var id_driver = $("#drivers_edit #id_driver").val();
    var sNombre = $("#sNombre");
    var dFechaNacimiento = $('#dFechaNacimiento');
    var iNumLicencia = $('#iNumLicencia');
    var dFechaExpiracionLicencia = $('#dFechaExpiracionLicencia');
    var iEntidad = $('#iEntidad');
    var iExperienciaYear = $('#iExperienciaYear'); 
    var dFechaContratacion = $('#dFechaContratacion'); 
    todosloscampos = $( [] ).add( sNombre ).add( dFechaNacimiento ).add( iNumLicencia ).add( dFechaExpiracionLicencia ).add( iEntidad ).add( iExperienciaYear ).add( dFechaContratacion );
    todosloscampos.removeClass( "error" );
    actualizarMensajeAlerta("");
   
    var valid = true;
            
    //field nombre
    valid = valid && checkLength( sNombre, "Name", 1, 20 );
    valid = valid && checkRegexp( sNombre, /^[0-9a-zA-ZáéíóúàèìòùÀÈÌÒÙÁÉÍÓÚñÑüÜ_\s]+$/, "The field for the Name must contain only letters." );
            
    //field FDN
    valid = valid && checkLength( dFechaNacimiento, "Date of Birthday", 1, 10);
    //valid = valid && checkRegexp( dFechaNacimiento, /^(0[1-9]|[12][0-9]|3[01])[- \/.](0[1-9]|1[012])[- \/.](19|20)\d\d$/, "The date must be in DD / MM / YY format." );
    
    //field NumLicencia
    valid = valid && checkLength( iNumLicencia, "License Number", 1, 30);
    valid = valid && checkRegexp( iNumLicencia, /^[0-9]+$/, "This field allows only numeric data." );        
    
    //field dFechaExpiracionLicencia
    valid = valid && checkLength( dFechaExpiracionLicencia, "Expiration Date", 1, 10);
    //valid = valid && checkRegexp( dFechaExpiracionLicencia, /^(0[1-9]|[12][0-9]|3[01])[- \/.](0[1-9]|1[012])[- \/.](19|20)\d\d$/, "The date must be in DD / MM / YY format." );        
    
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
    //valid = valid && checkRegexp( dFechaContratacion, /^(0[1-9]|[12][0-9]|3[01])[- \/.](0[1-9]|1[012])[- \/.](19|20)\d\d$/, "The date must be in DD / MM / YY format." );
            
    if ( valid ) { 
       $.post("funciones_drivers.php", 
       { 
           accion: "save_driver", 
           id_driver : id_driver,
           sNombre : sNombre.val(),
           dFechaNacimiento : dFechaNacimiento.val(),
           iNumLicencia : iNumLicencia.val(),
           dFechaExp : dFechaExpiracionLicencia.val(),
           iEntidad : iEntidad.val(),
           iExpYear : iExperienciaYear.val(),
           dFechaContratacion :  dFechaContratacion.val()  
       },
       function(data){ 
           switch(data.error){
             case '0':
                alert(data.msj);
                llenadoGrid();
                fn_popups.cerrar_ventana('drivers_edit');
             break;
             case '1': alert(data.msj); break;  
               
           }       
       },"json");       
    }
    
} 
function driver_edit(id){
    
    if(id != ""){
        $.post("funciones_drivers.php", 
        {accion: "load_driver", id_driver : id},
           function(data){ 
               switch(data.error){
                 case '0':
                    eval(data.fields);
                    $.post("funciones.php", {accion: "get_country", country: data.country},
                    function(response){ 
                        $("#iEntidad").empty().append(response.tabla).removeAttr('disabled').removeClass('readonly');
                         $("#iEntidad").val(data.state);
                    },"json");
                    fn_popups.resaltar_ventana('drivers_edit');  
                 break;
                 case '1': alert(data.msj); break;  
                   
               }       
           },"json"); 
    }
    
}
function confirmarBorrar(registro,id) {
    $( "#dialogo_confirmar_eliminar" ).dialog({
      resizable: false,
      height:200,
      width:500,
      show: {effect: 'fade', speed: 2000},
      modal: true,
      buttons: {
        "Delete item": function() {
          driver_delete(id) ;
          $(this).dialog( "close" );  
        },
        "Cancel": function() {
          $(this).dialog( "close" );
          return false;
        }
      }
    });
}
function driver_delete(id){
    if(id != ""){
        $.post("funciones_drivers.php", 
        {accion: "delete_driver", id_driver : id},
           function(data){ 
               switch(data.error){
                 case '0':
                    //alert(data.msj);
                    llenadoGrid(); 
                 break;
                 case '1': alert(data.msj); break;    
               }       
           },"json"); 
    }
}
function filter_data(){
    //fn_bactualizaciones.pagina_actual = 0;
    var filters = "";
    if($("#flt_license").val() != ""){ filters += "iNumLicencia|"+$("#flt_license").val()+","}
    if($("#flt_name").val() != ""){ filters += "sNombre|"+$("#flt_name").val()+","}
    if($("#flt_dob").val() != ""){ filters += "dFechaNacimiento|"+$("#flt_dob").val()+","}
    if($("#flt_expirationdate").val() != ""){ filters += "dFechaExpiracion]|"+$("#flt_expirationdate").val()+","}
    if($("#flt_state").val() != ""){ filters += "ct_entidad.sDescEntidad|"+$("#flt_state").val()+","}
    if($("#flt_country").val() != ""){ filters += "ct_entidad.sCvePais|"+$("#flt_country").val()+","}  
    if($("#flt_YOE").val() != ""){ filters += "iExperienciaYear|"+$("#flt_YOE").val()+","}
    if($("#flt_DOH").val() != ""){ filters += "dFechaContratacion|"+$("#flt_DOH").val()+","} 
    //if($("#flt_Documents").val() != ""){ filters += "iNumLicencia|"+$("#flt_Documents").val()+","} 

    llenadoGrid(filters); 
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
                <td><input id="flt_license" type="text" placeholder="License Number:"></td>             
                <td><input id="flt_name" type="text" placeholder="Name:"></td>
                <td><input id="flt_dob" type="date" placeholder="Date of Birth:"></td>
                <td><input id="flt_expirationdate" type="text" placeholder="Expiration Date:"></td> 
                <td><input id="flt_state" type="text" placeholder="State:"></td>
                <td><input id="flt_country" type="text" placeholder="Country:"></td>
                <td><input id="flt_YOE" type="text" placeholder="Years of Experience:"></td> 
                <td><input id="flt_DOH" type="date" placeholder="Date of Hire:"></td>
                <td><input id="flt_Documents" type="text" placeholder="Document:"></td>  
                <td style='width:90px;'>
                    <div id="filter_now" class="btn-icon-2 btn-left" title="Search"><i class="fa fa-search"></i></div>
                    <div id="add_new" class="btn-icon-2 add btn-left" title="Add +"><i class="fa fa-plus-circle"></i></div> 
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid">License Number</td> 
                <td class="etiqueta_grid">Name</td>
                <td class="etiqueta_grid">DOB</td>
                <td class="etiqueta_grid">Expiration Date</td>
                <td class="etiqueta_grid">State</td>
                <td class="etiqueta_grid">Country</td>  
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
            </fieldset> 
            <fieldset>
            <legend>Driver Documents</legend>
                <div class="file_dl"> 
                    <label>Driver License: Please upload a copy of driver's license in PDF format.</label>
                    <input id="sLicenciaPDF" name="sLicenciaPDF" type="file" /> 
                </div>
                <div class="file_mvr" style="display:none;"> 
                    <label>MVR (Motor Vehicle Record): Please upload a copy of the record of the driver in PDF format.</label>
                    <input id="sMVRPDF" name="sMVRPDF" type="file" /> 
                </div>
                <input id="id_driver" type="hidden"> 
            </fieldset>
            <button id="btn_save" type="button" class="btn-1" >Guardar</button>   
        </form>
    </div>
</div>
<!---- DIALOGOS ---->
<div id="dialogo_confirmar_eliminar" title="Delete">
  <p><span class="ui-icon ui-icon-alert" ></span>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
</div>
</body>
</html>
<?php } ?>