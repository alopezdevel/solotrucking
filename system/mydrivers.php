<?php session_start();    
if ( !($_SESSION["acceso"] == '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<!---- HEADER ----->
<?php include("header.php"); ?>  
<script type="text/javascript"> 
$(document).ready(inicio);
function inicio(){  
        $.blockUI();
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);
        //if(tipo_usuario != "1"){validarLoginCliente(usuario_actual);}
        fn_drivers.init();
        fn_drivers.fillgrid();
        $.unblockUI();
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}  }                   
var fn_drivers = {
        domroot:"#ct_drivers",
        data_grid: "#data_grid_drivers",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "sNombre",
        init : function(){
            $('.num').keydown(fn_solotrucking.inputnumero); 
            $('.decimals').keydown(fn_solotrucking.inputdecimals);
            //Filtrado con la tecla enter
            $(fn_drivers.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_drivers.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_drivers.filtraInformacion();
                }
            });    
            
            $(".flt_fecha").mask("99-99-9999");
            //INICIALIZA DATEPICKER PARA CAMPOS FECHA
            $(".fecha").datepicker({
                showOn: 'button',
                buttonImage: 'images/layout.png',
                dateFormat : 'mm/dd/yy',
                buttonImageOnly: true
            });
            $(".fecha").mask("99/99/9999"); 
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_mydrivers.php", 
                data:{
                    accion:"get_drivers",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_drivers.pagina_actual, 
                    filtroInformacion : fn_drivers.filtro,  
                    ordenInformacion : fn_drivers.orden,
                    sortInformacion : fn_drivers.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_drivers.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_drivers.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_drivers.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_drivers.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_drivers.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_drivers.pagina_actual = data.pagina;
                    fn_drivers.view_policies();
                    fn_drivers.edit();
                }
            }); 
        },
        firstPage : function(){
            if($(fn_drivers.data_grid+" #pagina_actual").val() != "1"){
                fn_drivers.pagina_actual = "";
                fn_drivers.fillgrid();
            }
        },
        previousPage : function(){
                if($(fn_drivers.data_grid+" #pagina_actual").val() != "1"){
                    fn_drivers.pagina_actual = (parseInt($(fn_drivers.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_drivers.fillgrid();
                }
        },
        nextPage : function(){
                if($(fn_drivers.data_grid+" #pagina_actual").val() != $(fn_drivers.data_grid+" #paginas_total").val()){
                    fn_drivers.pagina_actual = (parseInt($(fn_drivers.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_drivers.fillgrid();
                }
        },
        lastPage : function(){
                if($(fn_drivers.data_grid+" #pagina_actual").val() != $(fn_drivers.data_grid+" #paginas_total").val()){
                    fn_drivers.pagina_actual = $(fn_drivers.data_grid+" #paginas_total").val();
                    fn_drivers.fillgrid();
                }
        }, 
        ordenamiento : function(campo,objeto){
                $(fn_drivers.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_drivers.orden){
                    if(fn_drivers.sort == "ASC"){
                        fn_drivers.sort = "DESC";
                        $(fn_drivers.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_drivers.sort = "ASC";
                        $(fn_drivers.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_drivers.sort = "ASC";
                    fn_drivers.orden = campo;
                    $(fn_drivers.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_drivers.fillgrid();

                return false;
        }, 
        filtraInformacion : function(){
                fn_drivers.pagina_actual = 0;
                fn_drivers.filtro = "";
                if($(fn_drivers.data_grid+" .flt_nombre").val() != ""){ fn_drivers.filtro += "sNombre|"+$(fn_drivers.data_grid+" .flt_nombre").val()+","}
                if($(fn_drivers.data_grid+" .flt_fecha").val() != ""){ fn_drivers.filtro += "dFechaNacimiento|"+$(fn_drivers.data_grid+" .flt_fecha").val()+","} 
                if($(fn_drivers.data_grid+" .flt_licencia").val() != ""){ fn_drivers.filtro += "iNumLicencia|"+$(fn_drivers.data_grid+" .flt_licencia").val()+","} 
                if($(fn_drivers.data_grid+" .flt_tipoLicencia").val() != ""){ fn_drivers.filtro += "eTipoLicencia|"+$(fn_drivers.data_grid+" .flt_tipoLicencia").val()+","} 
                if($(fn_drivers.data_grid+" .flt_fechaExpiracion").val() != ""){ fn_drivers.filtro += "dFechaExpiracionLicencia|"+$(fn_drivers.data_grid+" .flt_fechaExpiracion").val()+","}    
                if($(fn_drivers.data_grid+" .flt_experiencia").val() != ""){ fn_drivers.filtro += "iExperienciaYear|"+$(fn_drivers.data_grid+" .flt_experiencia").val()+","} 
                //if($(fn_drivers.data_grid+" .flt_status").val() != ""){ fn_drivers.filtro += "inPoliza|"+$(fn_drivers.data_grid+" .flt_status").val()+","} 
                fn_drivers.fillgrid();
       },
        view_policies : function(){
           $(fn_drivers.data_grid + " tbody .btn_view_policies").bind("click",function(){
               var clave  = $(this).parent().parent().find("td:eq(0)").attr('id');
               var nombre = $(this).parent().parent().find("td:eq(0)").html(); 
               $.ajax({
                    type:"POST",url:"funciones_mydrivers.php",data:{accion:"get_drivers_policies",iConsecutivo : clave},async : true,dataType : "json",
                    success : function(data){ 
                        if(data.error == '0'){
                            $("#frm_view_policies #sNombre").val(nombre);                              
                            $("#policies_info tbody").empty().append(data.tabla);
                            $("#policies_info tbody tr:even").addClass('gray');
                            $("#policies_info tbody tr:odd").addClass('white');
                            fn_popups.resaltar_ventana('frm_view_policies'); 
                        }else{
                            
                        }
                    }
               }); 
           });   
        },
        edit : function(){
           $(fn_drivers.data_grid + " tbody .btn_edit").bind("click",function(){
               var clave  = $(this).parent().parent().find("td:eq(0)").attr('id'); 
               $.ajax({
                    type:"POST",url:"funciones_mydrivers.php",
                    data:{accion:"get_driver_data",clave : clave, domroot:"frm_edit_driver"},
                    async : true,
                    dataType : "json",
                    success : function(data){ 
                        if(data.error == '0'){
                            $("#frm_edit_driver :text, #frm_edit_driver select").val("");
                            eval(data.fields);  
                            fn_popups.resaltar_ventana('frm_edit_driver'); 
                        }else{
                            
                        }
                    }
               }); 
           }); 
        },
        validate_age : function(){ 
            fecha = $('#frm_edit_driver #dFechaNacimiento').val();
            var hoy = new Date();
            var sFecha = fecha || (hoy.getDate() + "/" + (hoy.getMonth() +1) + "/" + hoy.getFullYear());
            var sep = sFecha.indexOf('/') != -1 ? '/' : '-'; 
            var aFecha = sFecha.split(sep);
            var fecha = aFecha[2]+'/'+aFecha[1]+'/'+aFecha[0];
            fecha= new Date(fecha);
              
            ed = parseInt((hoy-fecha)/365/24/60/60/1000);
            if(ed >= 65){$('#frm_edit_driver .file_longterm').show();}
            else{$('#frm_edit_driver .file_longterm').hide();}
        },
        validate_country : function(){

           if($('#frm_edit_driver #eTipoLicencia').val() == 'COMMERCIAL/CDL-A'){
                $('#frm_edit_driver .file_mvr').show(); 
                $('#frm_edit_driver .file_psp').hide();   
           }else if($('#frm_edit_driver #eTipoLicencia').val() == 'FEDERAL/B1'){
                $('#frm_edit_driver .file_psp').show();
                $('#frm_edit_driver .file_mvr').hide();
           }      
        },
        save : function(){
            
        }
        /*delete_confirm : function(){
          $(fn_endorsement_co.data_grid + " tbody .btn_delete").bind("click",function(){
               var clave = $(this).parent().parent().find("td:eq(0)").html();
               $('#dialog_delete_endorsement_co #id_endorsement_co').val(clave);
               $('#dialog_delete_endorsement_co').dialog( 'open' );
               return false;
           });  
        },
        delete_endorsement_co : function(id){
          $.post("funciones_endorsements.php",{accion:"delete_endorsement_co", 'clave': id},
           function(data){
                fn_solotrucking.mensaje(data.msj);
                fn_endorsement_co.fillgrid();
           },"json");  
        },  */    
}    

 
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_drivers" class="container">
        <div class="page-title">
            <h1>POLICIES</h1>
            <h2 style="margin-bottom: 5px;">MY DRIVERS' LIST</h2>
        </div>
        <table id="data_grid_drivers" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style="width:300px"><input class="flt_nombre" type="text" placeholder="Name:"></td> 
                <td><input class="flt_fecha" type="text" placeholder="MM-DD-YYYY"></td>
                <td><input class="flt_licencia txt-uppercase" type="text" placeholder="License #:"></td> 
                <td>
                    <select class="flt_tipoLicencia">
                        <option value="">Select an option...</option>
                        <option value="FEDERAL/B1">FEDERAL / B1</option>
                        <option value="COMMERCIAL/CDL-A">COMMERCIAL / CDL-1</option>
                    </select>
                </td> 
                <td><input class="flt_fechaExpiracion" type="text" placeholder="MM-DD-YY"></td>  
                <td><input class="flt_experiencia" type="text" placeholder="##" maxlength="2"></td>  
                <td style='width:80px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_drivers.filtraInformacion();"><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_drivers.ordenamiento('sNombre',this.cellIndex);">NAME</td> 
                <td class="etiqueta_grid"      onclick="fn_drivers.ordenamiento('dFechaNacimiento',this.cellIndex);">DOB</td>
                <td class="etiqueta_grid"      onclick="fn_drivers.ordenamiento('iNumLicencia',this.cellIndex);">LICENSE #</td>
                <td class="etiqueta_grid"      onclick="fn_drivers.ordenamiento('eTipoLicencia',this.cellIndex);">LICENSE TYPE </td> 
                <td class="etiqueta_grid"      onclick="fn_drivers.ordenamiento('dFechaExpiracionLicencia',this.cellIndex);">EXPIRE DATE</td> 
                <td class="etiqueta_grid"      onclick="fn_drivers.ordenamiento('iExperienciaYear',this.cellIndex);">EXPERIENCE YEARS</td> 
                <td class="etiqueta_grid"></td>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
            <tr>
                <td colspan="100%">
                    <div id="datagrid-pages">
                        <input id="pagina_actual" type="text" readonly="readonly" size="3">
                        <label> / </label>
                        <input id="paginas_total" type="text" readonly="readonly" size="3">
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="100%">
                    <div id="datagrid-menu-pages">
                        <button id="pgn-inicio"    onclick="fn_drivers.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_drivers.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_drivers.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_drivers.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>    
    </div>
</div>
<!---- FORMULARIOS ------> 
<div id="frm_view_policies" class="popup-form">
    <div class="p-header">
        <h2>What policies is it active in?</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('frm_view_policies');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <p class="msg-error error" style="padding:10px;margin-bottom:10px;display:none;"></p>
    <div>
        <form>
            <fieldset>
                <legend>Driver Information: <input id="sNombre" name ="sNombre"  type = "text" readonly="readonly" class="readonly"></legend>
                <table id="policies_info" class="data_grid">
                   <thead>
                        <tr id="grid-head2">
                            <td class="etiqueta_grid">Policy Number</td>
                            <td class="etiqueta_grid">Type</td>
                            <td class="etiqueta_grid">EFFECTIVE DATE </td> 
                            <td class="etiqueta_grid">Expiration Date</td> 
                        </tr>
                   </thead>
                   <tbody></tbody>
                </table>
            </fieldset>
            <br>  
            <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('frm_view_policies');" style="margin-right:10px;background:#e8051b;">CLOSE</button>
        </form> 
    </div>
    </div>
</div>
<!--- FRM-EDIT --->
<div id="frm_edit_driver" class="popup-form">
    <div class="p-header">
        <h2>EDIT DRIVER DATA FORM</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('frm_edit_driver');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
        <p class="msg-error error" style="padding:10px;margin-bottom:10px;display:none;"></p>
        <form id="frm_driver_information">
            <fieldset>
                <legend>Driver Information</legend> 
                <div class="field_item"> 
                    <input id="iConsecutivo" type="hidden">
                    <label>Name <span style="color:#ff0000;">*</span>: </label><input id="sNombre" type="text" placeholder="Please write a name..." class="txt-uppercase">
                </div>
                <div class="field_item"> 
                    <label>Birthdate <span style="color:#ff0000;">*</span>: </label><input id="dFechaNacimiento" type="text" class="txt-uppercase fecha" onblur="fn_drivers.validate_age();">
                </div>
                <div class="field_item"> 
                      <label>Licence Type <span style="color:#ff0000;">*</span>: </label>
                      <Select id="eTipoLicencia" onblur="fn_drivers.validate_country();">
                        <option value="">Select an option...</option>
                        <option value="FEDERAL/B1">FEDERAL / B1</option>
                        <option value="COMMERCIAL/CDL-A">COMMERCIAL / CDL-1</option>
                      </select>
                </div>
                <div class="field_item"> 
                    <label>License Number <span style="color:#ff0000;">*</span>: </label>
                    <input id="iNumLicencia" class="txt-uppercase" maxlength="10" type="text" placeholder="Please write the license number...">
                </div>
                <div class="field_item"> 
                    <label>Expiration Date <span style="color:#ff0000;">*</span>: </label>
                    <input id="dFechaExpiracionLicencia" type="text" class="txt-uppercase fecha">
                </div>
                <div class="field_item"> 
                    <label>Experience Years <span style="color:#ff0000;">*</span>: </label>
                    <input id="iExperienciaYear" class="num txt-uppercase" type="text" placeholder="Please write only the number.">
                </div>
                <div class="file_dl files"> 
                    <label>Driver License <span style="color:#ff0000;">*</span>: <span style="color:#9e2e2e;">Please upload a copy of driver's license in PDF or JPG format.</span></label>
                    <input type="text" id="txtsLicenciaPDF" readonly="readonly" value="" size="40" style="width:85%;" />
                    <button id="btnsLicenciaPDF" type="button">Upload File</button>
                    <input id="iConsecutivoLicenciaPDF" class="id_file" type="hidden">
                </div>
                <div class="file_mvr files" style="display:none;"> 
                    <label>MVR (Motor Vehicle Record) <span style="color:#ff0000;">*</span>: <span style="color:#9e2e2e;">Please upload a copy of the record of the driver in PDF or JPG format.</span></label>
                    <input  id="txtsMVRPDF" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                    <button id="btnsMVRPDF" type="button">Upload File</button>
                    <input  id="iConsecutivoMVRPDF" class="id_file" type="hidden">
                    <div style="display:none;">
                        <label style="display: block;float: left;margin: 8px 5px 0px;">If you don't have the Driver MVR, We can process it for you: </label><div class="btn-text buy btn-left" title="Order processing of MVR / $15 Dlls + TAX"  onclick="" style="width: auto!important;"><i class="fa fa-usd"></i><span>Order MVR</span></div>
                    </div>
                </div>
                <div class="file_longterm files" style="display:none;"> 
                    <label>Long Term Medical Form <span style="color:#ff0000;">*</span>: <span style="color:#9e2e2e;">Please upload a copy of the form in PDF or JPG format.</span></label> 
                    <input  id="txtsLTMPDF" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                    <button id="btnsLTMPDF" type="button">Upload File</button>
                    <input  id="iConsecutivoLTMPDF" class="id_file" type="hidden">
                </div> 
                <div class="file_psp files" style="display:none;"> 
                    <label title="(This file is only required if you have a policy with Northern Star)">PSP: <span style="color:#9e2e2e;">Please upload a copy of Pre-employment Screening Program in PDF or JPG format.</span></label>
                    <input  id="txtsPSPFile" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                    <button id="btnsPSPFile" type="button">Upload File</button>
                    <input  id="iConsecutivoPSPFile" class="id_file" type="hidden">
                </div> 
            </fieldset>
            <button type="button" class="btn-1" onclick="fn_drivers.save();">SAVE</button>  
            <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('frm_edit_driver');" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
        </form>
    </div>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>