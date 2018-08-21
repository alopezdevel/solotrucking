<?php 
    session_start();    
    if ( !($_SESSION["acceso"] != '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
        header("Location: login.php");
        exit;
    }else{ 
?>
<!-- HEADER -->
<?php include("header.php"); ?>  
<script type="text/javascript"> 
    $(document).ready(inicio);
    function inicio(){  
            $.blockUI();
            var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
            var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
            validapantalla(usuario_actual);
            //if(tipo_usuario != "1"){validarLoginCliente(usuario_actual);}
            fn_endorsement.init();
            fn_endorsement.fillgrid();
            $.unblockUI();
            
            $('#dialog_endorsement_save').dialog({
                modal: true,
                autoOpen: false,
                width : 420,
                height : 200,
                resizable : false,
                buttons : {
                    'YES' : function() {
                        $(this).dialog('close');
                        fn_endorsement.save();             
                    },
                     'NO' : function(){
                        $(this).dialog('close');
                    }
                }
            }); 
               
    }  
    function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}  }                   
    var fn_endorsement = {
            domroot:"#ct_endorsement",
            data_grid: "#data_grid_endorsement",
            filtro : "",
            pagina_actual : "",
            sort : "DESC",
            orden : "B.dFechaAplicacion",
            init : function(){
                $('.num').keydown(fn_solotrucking.inputnumero); 
                $('.decimals').keydown(fn_solotrucking.inputdecimals);
                //Filtrado con la tecla enter
                $(fn_endorsement.data_grid + ' #grid-head1 input').keyup(function(event){
                    if (event.keyCode == '13') {
                        event.preventDefault();
                        fn_endorsement.filtraInformacion();
                    }
                    if(event.keyCode == '27'){
                       event.preventDefault();
                       $(this).val(''); 
                       fn_endorsement.filtraInformacion();
                    }
                });      
                //INICIALIZA DATEPICKER PARA CAMPOS FECHA
                $(".fecha").datepicker({
                    showOn: 'button',
                    buttonImage: 'images/layout.png',
                    dateFormat : 'mm/dd/yy',
                    buttonImageOnly: true
                });
                $(".fecha,.flt_fecha").mask("99/99/9999"); 
            },
            fillgrid: function(){
                   $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsement_request.php", 
                    data:{
                        accion:"get_endorsements",
                        registros_por_pagina : "15", 
                        pagina_actual : fn_endorsement.pagina_actual, 
                        filtroInformacion : fn_endorsement.filtro,  
                        ordenInformacion : fn_endorsement.orden,
                        sortInformacion : fn_endorsement.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_endorsement.data_grid+" tbody").empty().append(data.tabla);
                        $(fn_endorsement.data_grid+" tbody tr:even").addClass('gray');
                        $(fn_endorsement.data_grid+" tbody tr:odd").addClass('white');
                        $(fn_endorsement.data_grid + " tfoot #paginas_total").val(data.total);
                        $(fn_endorsement.data_grid + " tfoot #pagina_actual").val(data.pagina);
                        fn_endorsement.pagina_actual = data.pagina;
                        fn_endorsement.edit();
                        fn_endorsement.endorsement_email_send();
                    }
                }); 
            },
            edit : function (){
                $(fn_endorsement.data_grid + " tbody td .edit").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").html();
                    var idPoliza = $(this).parent().parent().find("td:eq(5)").attr('id');
                    var company = $(this).parent().parent().find("td:eq(1)").text(); 
                    $('#endorsements_edit_form .p-header h2').empty().text('Endorsement request from ' + company + ': E#' + clave);
                    $.post("funciones_endorsement_request.php",
                    {
                        accion:"get_endorsement", 
                        clave: clave,
                        idPoliza : idPoliza, 
                        domroot : "endorsements_edit_form"
                    },
                    function(data){
                        if(data.error == '0'){
                           $('#endorsements_edit_form input, #endorsements_edit_form select, #endorsements_edit_form textarea').val('');
                           $("#endorsements_edit_form #info_policies table tbody").empty().append(data.policies);
                           $('#endorsements_edit_form #pd_information, #frm_driver_information, #frm_unit_information').hide();
                           $('#endorsements_edit_form .data_files').empty(); 
                           eval(data.fields); 
                           if(data.pd_information != ''){$('#endorsements_edit_form #pd_information').empty().append(data.pd_information);}
                           if(data.files != ''){$('#endorsements_edit_form .data_files').empty().append(data.files);}  
                           if(data.kind == '2'){ // Drivers
                               $('#frm_driver_information').show();
                           }else if(data.kind == '1'){ // Units
                                $('#frm_unit_information').show();
                                if($('#eAccion').val() == 'DELETE'){
                                    $('#endorsements_edit_form .delete_field').show();
                                    $('#endorsements_edit_form .add_field').hide();
                                }else{  
                                    $('#endorsements_edit_form .add_field').show();
                                    $('#endorsements_edit_form .delete_field').hide();
                                    //mostrar pd:
                                    if($('#pd_information #iPDApply')){
                                        $('#endorsements_edit_form #pd_information').show(); 
                                    }
                                }  
                                
                           }
                           
                           if(data.status != 'A'){
                               //Activar campos para editar estatus:
                               if(data.status == 'S' || data.status == 'SB'){$('#eStatus').val('');}
                               
                               if(data.status == 'D'){
                                  $("#eStatus option[value=A], #eStatus option[value=P]").hide(); 
                               }else{
                                   $("#eStatus option[value=A], #eStatus option[value=P]").show();
                               }
                               
                               $('#frm_endorsement_status').show(); 
                               fn_endorsement.valida_status();
                               if(data.sComentarios != ""){$('#endorsements_edit_form #sComentarios').val(data.sComentarios);}
                           }else{
                               $('#frm_endorsement_status').hide();
                               $('#eStatus').val('');
                           }
                
                           fn_popups.resaltar_ventana('endorsements_edit_form');
                            
                        }else{
                           fn_solotrucking.mensaje(data.msj);  
                        }       
                    },"json"); 
              });  
            },
            endorsement_email_send : function(){
                $(fn_endorsement.data_grid + " tbody .btn_send_email").bind("click",function(){
                   var clave = $(this).parent().parent().find("td:eq(0)").html();
                   var idPoliza = $(this).parent().parent().find("td:eq(5)").attr('id');
                   msg = "<p style=\"text-align:center;\">Sending Company Endorsement data to Brokers, please wait....<br><img src=\"images/ajax-loader.gif\" alt=\"ajax-loader.gif\" style=\"margin-top:10px;\"><br></p>";
                   $('#Wait').empty().append(msg).dialog('open');              
                   $.post("funciones_endorsement_request.php",{accion:"send_email_brokers", clave: clave, idPoliza : idPoliza},
                    function(data){
                        $('#Wait').empty().dialog('close'); 
                        fn_solotrucking.mensaje(data.msj);
                        if(data.error == '0'){fn_endorsement.fillgrid();}     
                    },"json");
               });  
            },
            firstPage : function(){
                if($(fn_endorsement.data_grid+" #pagina_actual").val() != "1"){
                    fn_endorsement.pagina_actual = "";
                    fn_endorsement.fillgrid();
                }
            },
            previousPage : function(){
                    if($(fn_endorsement.data_grid+" #pagina_actual").val() != "1"){
                        fn_endorsement.pagina_actual = (parseInt($(fn_endorsement.data_grid+" #pagina_actual").val()) - 1) + "";
                        fn_endorsement.fillgrid();
                    }
                },
            nextPage : function(){
                    if($(fn_endorsement.data_grid+" #pagina_actual").val() != $(fn_endorsement.data_grid+" #paginas_total").val()){
                        fn_endorsement.pagina_actual = (parseInt($(fn_endorsement.data_grid+" #pagina_actual").val()) + 1) + "";
                        fn_endorsement.fillgrid();
                    }
                },
            lastPage : function(){
                    if($(fn_endorsement.data_grid+" #pagina_actual").val() != $(fn_endorsement.data_grid+" #paginas_total").val()){
                        fn_endorsement.pagina_actual = $(fn_endorsement.data_grid+" #paginas_total").val();
                        fn_endorsement.fillgrid();
                    }
                }, 
            ordenamiento : function(campo,objeto){
                    $(fn_endorsement.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                    if(campo == fn_endorsement.orden){
                        if(fn_endorsement.sort == "ASC"){
                            fn_endorsement.sort = "DESC";
                            $(fn_endorsement.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                        }else{
                            fn_endorsement.sort = "ASC";
                            $(fn_endorsement.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                        }
                    }else{
                        fn_endorsement.sort = "ASC";
                        fn_endorsement.orden = campo;
                        $(fn_endorsement.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                    fn_endorsement.fillgrid();

                    return false;
                }, 
            filtraInformacion : function(){
                    fn_endorsement.pagina_actual = 0;
                    fn_endorsement.filtro = "";
                    if($(fn_endorsement.data_grid+" .flt_id").val() != ""){ fn_endorsement.filtro += "B.iConsecutivo|"+$(fn_endorsement.data_grid+" .flt_id").val()+","}
                    if($(fn_endorsement.data_grid+" .flt_company").val() != ""){ fn_endorsement.filtro += "D.sNombreCompania|"+$(fn_endorsement.data_grid+" .flt_company").val()+","} 
                    if($(fn_endorsement.data_grid+" .flt_description").val() != ""){ fn_endorsement.filtro += "sNombre|"+$(fn_endorsement.data_grid+" .flt_description").val()+","} 
                    if($(fn_endorsement.data_grid+" .flt_action").val() != ""){ fn_endorsement.filtro += "eAccion|"+$(fn_endorsement.data_grid+" .flt_action").val()+","}
                    if($(fn_endorsement.data_grid+" .flt_date").val() != ""){ fn_endorsement.filtro += "B.dFechaAplicacion|"+$(fn_endorsement.data_grid+" .flt_date").val()+","}
                    if($(fn_endorsement.data_grid+" .flt_policy").val() != ""){ fn_endorsement.filtro += "sNumeroPoliza|"+$(fn_endorsement.data_grid+" .flt_policy").val()+","}
                    if($(fn_endorsement.data_grid+" .flt_broker").val() != ""){ fn_endorsement.filtro += "BR.sName|"+$(fn_endorsement.data_grid+" .flt_broker").val()+","} 
                    if($(fn_endorsement.data_grid+" .flt_status").val() != ""){ fn_endorsement.filtro += "A.eStatus|"+$(fn_endorsement.data_grid+" .flt_status").val()+","}
                    fn_endorsement.fillgrid();
           },
           valida_status : function(){
               if($('#eStatus').val() == 'D'){
                  $('.comentarios_endoso').show(); 
               }else{
                  $('.comentarios_endoso').hide();
               }
           },
           save_confirm : function(){
               if( $('#frm_endorsement_status #eStatus').val() != 'SB'){
                    $('#dialog_endorsement_save').dialog( 'open' );   
               }
           },
           save : function(){
               var eStatus = $('#frm_endorsement_status #eStatus');
               var sComentarios = $('#frm_endorsement_status #sComentarios');
               var idPoliza = $('#iConsecutivoPoliza').val();
               todosloscampos = $('#eStatus, #sComentarios');
               todosloscampos.removeClass( "error" );
               valid = true;
               if(eStatus.val() == ""){
                   fn_solotrucking.mensaje('Please select an option valid to change de endorsement status.');
                   eStatus.addClass('error');
                   valid = false;
               }
               if(eStatus.val() == "D" && sComentarios.val() == ""){fn_solotrucking.mensaje('Please write the reasons for the denied. To send them at company.');sComentarios.addClass('error');valid = false;}
               
               if(valid){ 
                  msg = "<p style=\"text-align:center;\">Updating endorsement data and send an e-mail to company, please wait...<br><img src=\"images/ajax-loader.gif\" alt=\"ajax-loader.gif\" style=\"margin-top:10px;\"><br></p>";
                  $('#Wait').empty().append(msg).dialog('open');
                  $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsement_request.php", 
                    data:{
                        accion       : "update_endorsement_status",
                        iConsecutivo : $('#iConsecutivo').val(),
                        eStatus      : eStatus.val(),
                        idPoliza : idPoliza,
                        sComentarios : sComentarios.val() 
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        switch(data.error){ 
                         case '0':
                            fn_solotrucking.mensaje(data.msj);
                            fn_endorsement.fillgrid();
                            fn_popups.cerrar_ventana('endorsements_edit_form');
                         break;
                         case '1': fn_solotrucking.mensaje(data.msj); break;
                        }
                        $('#Wait').empty().dialog('close');   
                    }
                  }); 
               } 
           }
                
    }    
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_endorsement" class="container">
        <div class="page-title">
            <h1>REQUESTS</h1>
            <h2>ENDORSEMENTS / DRIVERS </h2>
        </div>
        <table id="data_grid_endorsement" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'>
                    <input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_company" type="text" placeholder="Company:"></td>
                <td><input class="flt_description" type="text" placeholder="Descripcion:"></td>
                <td style="width:110px">
                    <select class="flt_action" onblur="fn_endorsement.filtraInformacion();">
                        <option value="">Select an action ..</option>
                        <option value="A">ADD</option>
                        <option value="D">DELETE</option>
                    </select>
                </td>  
                <td><input class="flt_date" type="text" placeholder="Application Date:"></td> 
                <td><input class="flt_policy" type="text" placeholder="Policy #:"></td>
                <td><input class="flt_broker" type="text" placeholder="Broker:"></td>
                <td>
                    <select class="flt_status" onblur="fn_endorsement.filtraInformacion();">
                        <option value="">Select an option...</option>
                        <option value="S">NEW APLICATION</option>
                        <option value="SB">SENT TO BROKERS</option>
                        <option value="D">DENIED</option>
                        <option value="P">IN PROGRESS</option>
                        <option value="A">APPROVED</option> 
                    </select></td>  
                <td style='width:160px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_endorsement.filtraInformacion();"><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('B.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('D.sNombreCompania',this.cellIndex);">COMPANY</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('sNombre',this.cellIndex);">Description</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('eAccion',this.cellIndex);">ACTION</td>
                <td class="etiqueta_grid up"   onclick="fn_endorsement.ordenamiento('B.dFechaAplicacion',this.cellIndex);">APPLICATION DATE</td> 
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('sNumeroPoliza',this.cellIndex);">POLICY #</td> 
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('broker',this.cellIndex);">BROKER</td> 
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('A.eStatus',this.cellIndex);">Status</td>
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
                        <button id="pgn-inicio"    onclick="fn_endorsement.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_endorsement.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_endorsement.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_endorsement.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>    
    </div>
</div>
<!-- FORMULARIOS -->
<div id="endorsements_edit_form" class="popup-form">
    <div class="p-header">
        <h2>ENDORSEMENTS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('endorsements_edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <div id="info_policies">
        <table class="popup-datagrid">
            <thead>
                <tr id="grid-head2"><td class="etiqueta_grid">Policy Number</td><td class="etiqueta_grid">Broker</td><td class="etiqueta_grid">Type</td></tr>
            </thead>
            <tbody></tbody>
        </table>
        </div>
        <br>
        <form>
            <fieldset id="frm_endorsement_information">
                <legend>Endorsement Information</legend>
                <table style="width:100%" cellpadding="0" cellspacing="0">
                    <tr><td colspan="100%">
                        <p class="mensaje_valido">&nbsp;Please check that all data in this endorsement are correct to be sent to Brokers</p>
                        <input id="iConsecutivo" name="iConsecutivo" type="hidden" value="">
                        <input id="iConsecutivoPoliza" name="iConsecutivoPoliza" type="hidden" value="">
                    </td></tr>
                    <tr>
                        <td>
                        <div class="field_item">
                            <label>Type:</label> 
                            <input id="sDescripcion" name ="sDescripcion"  type = "text" readonly="readonly" class="readonly" style="width: 97%;">
                        </div>
                        </td>
                        <td>
                        <div class="field_item">
                            <label>Action:</label> 
                            <input id="eAccion" name ="eAccion"  type = "text" readonly="readonly" class="readonly" style="width: 97%;">
                        </div>
                        </td>
                    </tr>
                    <tr><td colspan="100%"><div id="pd_information" class="field_item" style="display:none;"></div></td></tr>
                </table>
            </fieldset>
            <fieldset id="frm_driver_information" style="display:none;">
                <legend>Driver Information</legend> 
                <table style="width:100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td colspan="2">
                        <div class="field_item">
                            <label>Name:</label> <input id="sNombre" name ="sNombre"  type = "text" readonly="readonly" class="readonly" style="width: 97%;">
                        </div>
                        </td>
                        <td colspan="2">
                        <div class="field_item">
                            <label>Birthdate:</label> <input id="dFechaNacimiento" name ="dFechaNacimiento"  type = "text" readonly="readonly" class="readonly" style="width: 97%;">
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td><div class="field_item"><label>Licence Data:</label></div></td>
                        <td><div class="field_item"><input id="eTipoLicencia" name ="eTipoLicencia"  type = "text" readonly="readonly" class="readonly" style="width: 95%;"></div></td>
                        <td><div class="field_item"><input id="iNumLicencia"  name ="iNumLicencia"  type = "text" readonly="readonly" class="readonly" style="width: 95%;"></div></td>
                        <td><div class="field_item"><input id="dFechaExpiracionLicencia" name ="dFechaExpiracionLicencia"  type = "text" readonly="readonly" class="readonly" style="width: 95%;"></div></td>
                    </tr>
                    <tr>
                        <td><div class="field_item"><label>Experience Years:</label></div></td>
                        <td><div class="field_item"><input id="iExperienciaYear" name ="iExperienciaYear"  type = "text" readonly="readonly" class="readonly" style="width:100px;"></div></td>
                    </tr>
                </table>
            </fieldset>
            <fieldset id="frm_unit_information" style="display:none;">
                <legend>Unit Information</legend> 
                <div class="field_item">
                    <label>Year:</label> 
                    <input id="iYear" name ="iYear"  type = "text" readonly="readonly" class="readonly">
                </div>
                <div class="field_item">
                    <label>Make:</label> 
                    <input id="Modelo" name ="Modelo"  type = "text" readonly="readonly" class="readonly">
                </div>
                <div class="field_item">
                    <label>VIN:</label> 
                    <input id="sVIN" name ="sVIN"  type = "text" readonly="readonly" class="readonly">
                </div>
                <div class="field_item">
                    <label>Radius:</label> 
                    <input id="Radius" name ="Radius"  type = "text" readonly="readonly" class="readonly">
                </div>
            </fieldset>
            <table class="data_files" style="font-size: 12px!important;width: 50%;margin: -15px auto 20px;"></table>
            <fieldset id="frm_endorsement_status" style="display:none;">
                <legend>Endorsement Status Information</legend>
                <div class="field_item">
                    <label>Change Endorsement Status:</label> 
                    <select id="eStatus" name ="eStatus" onblur="fn_endorsement.valida_status();">
                        <option value="">Select an option...</option>
                        <option value="P">IN PROCESS</option> 
                        <option value="A">APPROVED</option> 
                        <option value="D">DENIED</option> 
                    </select>
                </div>
                <div class="field_item comentarios_endoso" style="display:none;">
                    <label>Please write the reason of denied:</label> 
                    <textarea id="sComentarios" name ="sComentarios"></textarea>
                </div>
                <button type="button" class="btn-1" onclick="fn_endorsement.save_confirm();">SAVE</button>
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('endorsements_edit_form');" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
            </fieldset> 
        </form> 
    </div>
    </div>
</div>
<!-- DIALOGUES -->
<div id="dialog_endorsement_save" title="SYSTEM ALERT" style="display:none;">
    <p>We will be sending a notice to the company about the status of endorsements. Are you sure want to continue?</p> 
</div> 
<!-- FOOTER -->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>
