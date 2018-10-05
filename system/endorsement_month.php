<?php session_start();    
if ( !($_SESSION["acceso"] != '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<!-- HEADER -->
<?php include("header.php"); ?>   
<script type="text/javascript"> 
$(document).ready(inicio);
function inicio(){  
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario   = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);  
        $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); });
        fn_endosos.init();
        //$.unblockUI();
        /*---- Dialogos ---*/
        $('#dialog_delete').dialog({
            modal: true,
            autoOpen: false,
            width : 550,
            height : 200,
            resizable : false,
            dialogClass: "without-close-button",
            buttons : {
                'CONFIRM' : function() {
                    var clave = $('#dialog_delete input[name=iConsecutivo]').val();
                    fn_endosos.borrar(clave);
                    $(this).dialog('close');
                },
                'CANCEL' : function(){$(this).dialog('close');}
            }
        });
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
var fn_endosos = {
        domroot:"#ct_endosos",
        data_grid: "#data_grid_endosos",
        filtro : "",
        pagina_actual : "",
        sort : "DESC",
        orden : "iConsecutivo",
        init : function(){
            fn_endosos.fillgrid();
            $('.num').keydown(fn_solotrucking.inputnumero); 
            //Filtrado con la tecla enter
            $(fn_endosos.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_endosos.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_endosos.filtraInformacion();
                }
            });  
            //Cargar Lista de companias:
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_companies"},
                async : true,
                dataType : "json",
                success : function(data){
                    $("#frm_edit_new select[name=iConsecutivoCompania").empty().append(data.select);
                }
            });
            
            //DATEPICKERS
            var dates_rp = $("#frm_edit_new input[name=flt_dateFrom], #frm_edit_new input[name=flt_dateTo]" ).datepicker({
                changeMonth: true,
                dateFormat: 'mm/dd/yy',
                onSelect: function( selectedDate ) {
                    var option = this.id == "flt_dateFrom" ? "minDate" : "maxDate",
                    instance = $( this ).data( "datepicker" );
                    date = $.datepicker.parseDate(
                    instance.settings.dateFormat ||
                    $.datepicker._defaults.dateFormat,
                    selectedDate, instance.settings );
                    dates_rp.not( this ).datepicker( "option", option, date );
                },
            }); 
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"endorsement_month_server.php", 
                data:{
                    accion               : "get_datagrid",
                    registros_por_pagina : "15", 
                    pagina_actual        : fn_endosos.pagina_actual, 
                    filtroInformacion    : fn_endosos.filtro,  
                    ordenInformacion     : fn_endosos.orden,
                    sortInformacion      : fn_endosos.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_endosos.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_endosos.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_endosos.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_endosos.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_endosos.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_endosos.pagina_actual = data.pagina; 
                    //fn_endosos.edit();
                    //fn_endosos.delete_confirm();
                }
            }); 
        },
        add : function(){
           $('#frm_edit_new :text, #frm_edit_new select').val('').removeClass('error');
           var fechas = fn_solotrucking.obtener_fechas();
           $("#frm_edit_new input[name=flt_dateFrom]").val(fechas[1]); 
           $("#frm_edit_new input[name=flt_dateTo]").val(fechas[2]); 
           $("#frm_edit_new textarea[name=sMensajeEmail]").val("Please create new endorsement for the following insured.");
           
           fn_popups.resaltar_ventana('frm_edit_new');  
        },
        edit : function (){
            $(fn_endosos.data_grid + " tbody td .edit").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                
                //fn_popups.resaltar_ventana("frm_edit_new");
                $.post("endorsement_month_server.php",
                {
                    accion:"get_company", 
                    clave: clave, 
                    domroot : "frm_edit_new"
                },
                function(data){
                    if(data.error == '0'){
                       $('#frm_edit_new :text, #frm_edit_new select').val('').removeClass('error'); 
                       eval(data.fields);
                       $('#frm_edit_new #sUsdot').attr('readonly','readonly'); 
                       //$('#frm_edit_new #companies_tabs').show(); 
                       fn_popups.resaltar_ventana('frm_edit_new');
                         
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json"); 
          });  
        },
        get_data : function(clave){
           $('#frm_edit_new :text, #frm_edit_new select').val('').removeClass('error');
           $('#frm_edit_new select[name=iConsecutivoCompania], #frm_edit_new select[name=iConsecutivoBroker], #flt_dateFrom, #flt_dateTo').attr('readonly','readonly'); 
        },
        save : function (){
           
           //Validate Fields:
           var valid = true;
           var msj   = "";
           $("#frm_edit_new input.required-field, #frm_edit_new select.required-field").removeClass("error");
           $("#frm_edit_new input.required-field, #frm_edit_new select.required-field").each(function(){
              if($(this).val() == ""){valid = false; $(this).addClass('error');msj = "<li>You must capture the required fields.</li>";} 
           });
           
           if(valid){
             if($('#frm_edit_new #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
             struct_data_post.action  = "save_data";
             struct_data_post.domroot = "#frm_edit_new"; 
                $.post("endorsement_month_server.php",struct_data_post.parse(),
                function(data){
                    switch(data.error){
                     case '0':
                        fn_solotrucking.mensaje(data.msj);
                        fn_endosos.get_data(data.iConsecutivo);
                     break;
                     case '1': fn_solotrucking.mensaje(data.msj); break;
                    }
                },"json");
           }
           else{fn_solotrucking.mensaje('<p>Please check the following::</p><ul>'+msj+'</ul>');} 
        },
        firstPage : function(){
            if($(fn_endosos.data_grid+" #pagina_actual").val() != "1"){
                fn_endosos.pagina_actual = "";
                fn_endosos.fillgrid();
            }
        },
        previousPage : function(){
            if($(fn_endosos.data_grid+" #pagina_actual").val() != "1"){
                fn_endosos.pagina_actual = (parseInt($(fn_endosos.data_grid+" #pagina_actual").val()) - 1) + "";
                fn_endosos.fillgrid();
            }
        },
        nextPage : function(){
            if($(fn_endosos.data_grid+" #pagina_actual").val() != $(fn_endosos.data_grid+" #paginas_total").val()){
                fn_endosos.pagina_actual = (parseInt($(fn_endosos.data_grid+" #pagina_actual").val()) + 1) + "";
                fn_endosos.fillgrid();
            }
        },
        lastPage : function(){
            if($(fn_endosos.data_grid+" #pagina_actual").val() != $(fn_endosos.data_grid+" #paginas_total").val()){
                fn_endosos.pagina_actual = $(fn_endosos.data_grid+" #paginas_total").val();
                fn_endosos.fillgrid();
            }
        }, 
        ordenamiento : function(campo,objeto){
            $(fn_endosos.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_endosos.orden){
                if(fn_endosos.sort == "ASC"){
                    fn_endosos.sort = "DESC";
                    $(fn_endosos.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_endosos.sort = "ASC";
                    $(fn_endosos.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_endosos.sort = "ASC";
                fn_endosos.orden = campo;
                $(fn_endosos.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
            }
            fn_endosos.fillgrid();

            return false;
        }, 
        filtraInformacion : function(){
            fn_endosos.pagina_actual = 0;
            fn_endosos.filtro = "";
            if($(fn_endosos.data_grid+" .flt_id").val() != ""){ fn_endosos.filtro += "iConsecutivo|"+$(fn_endosos.data_grid+" .flt_id").val()+","}
            if($(fn_endosos.data_grid+" .flt_name").val() != ""){ fn_endosos.filtro += "sNombreCompania|"+$(fn_endosos.data_grid+" .flt_name").val()+","} 
            if($(fn_endosos.data_grid+" .flt_contacto").val() != ""){ fn_endosos.filtro += "sNombreContacto|"+$(fn_endosos.data_grid+" .flt_contacto").val()+","} 
            if($(fn_endosos.data_grid+" .flt_address").val() != ""){ fn_endosos.filtro += "sDireccion|"+$(fn_endosos.data_grid+" .flt_address").val()+","} 
            if($(fn_endosos.data_grid+" .flt_country").val() != ""){ fn_endosos.filtro += "estado|"+$(fn_endosos.data_grid+" .flt_country").val()+","} 
            if($(fn_endosos.data_grid+" .flt_zip").val() != ""){ fn_endosos.filtro += "sCodigoPostal|"+$(fn_endosos.data_grid+" .flt_zip").val()+","} 
            if($(fn_endosos.data_grid+" .flt_phone").val() != ""){ fn_endosos.filtro += "sTelefonoPrincipal|"+$(fn_endosos.data_grid+" .flt_phone").val()+","}  
            if($(fn_endosos.data_grid+" .flt_usdot").val() != ""){ fn_endosos.filtro += "sUsdot|"+$(fn_endosos.data_grid+" .flt_usdot").val()+","}    
            fn_endosos.fillgrid();
        },  
        delete_confirm : function(){
          $(fn_endosos.data_grid + " tbody .btn_delete").bind("click",function(){
               var clave = $(this).parent().parent().find("td:eq(0)").html();
               var name  = $(this).parent().parent().find("td:eq(1)").text();
               $('#dialog_delete input[name=iConsecutivo]').val(clave);
               $('#dialog_delete .name').empty().html(name);
               $('#dialog_delete').dialog( 'open' );
               return false;
           });  
        },
        borrar : function(clave){
          $.post("endorsement_month_server.php",{accion:"delete_company", 'clave': clave},
           function(data){
                fn_solotrucking.mensaje(data.msj);
                fn_endosos.filtraInformacion();
           },"json");  
        },
        get_broker_data : function(){
            var broker = $("#frm_edit_new select[name=iConsecutivoBroker]").val();
            if(broker != ""){
               $.ajax({             
                    type:"POST", 
                    url:"endorsement_month_server.php", 
                    data:{accion:"get_broker_data","iConsecutivoBroker":broker},
                    async : true,
                    dataType : "text",
                    success : function(data){
                        $("#frm_edit_new input[name=sEmail").val(data);
                    }
               });  
            }
        }
}     
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_endosos" class="container">
        <div class="page-title">
            <h1>ENDORSEMENTS / ENDOSOS</h1>
            <h2>Monthly / MENSUAL</h2>
        </div>
        <table id="data_grid_endosos" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'><input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_name" type="text" placeholder="Company Name:"></td>
                <td><input class="flt_broker" type="text" placeholder="Broker Name:"></td> 
                <td><input class="flt_email" type="text" placeholder="E-mails:"></td> 
                <td><input class="flt_phone" type="text" placeholder="MM/DD/YYYY"></td> 
                <td style='width:80px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_endosos.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_endosos.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <!--<tr id="grid-head-tools">
                <td colspan="100%">
                    <ul>
                        <li><div class="btn-icon report btn-left" title="Generate a Report"><i class="fa fa-folder-open"></i></div></li>  
                    </ul>
                </td>
            </tr>-->
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_endosos.ordenamiento('iConsecutivo',this.cellIndex);">ID</td>
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('sNombreCompania',this.cellIndex);">Company</td>
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('sNombreBroker',this.cellIndex);">Broker</td>
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('sEmail',this.cellIndex);">E-mails</td>
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('dFechaAplicacion',this.cellIndex);">Application Date</td>
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
                        <button id="pgn-inicio"    onclick="fn_endosos.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_endosos.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_endosos.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_endosos.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>
        
    </div>
</div>
<!-- FORMULARIOS -->
<div id="frm_edit_new" class="popup-form">
    <div class="p-header">
        <h2>EDIT OR ADD AN Endorsement</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('frm_edit_new');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <form>
            <fieldset>
                <legend style="margin-bottom:0px!important;">General Information</legend>
                <table style="width:100%;">
                    <tr><td colspan="100%"><p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p></td></tr>
                    <tr>
                        <td colspan="100%">
                            <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                            <div class="field_item">
                                <label>Company <span style="color:#ff0000;">*</span>:</label>  
                                <select tabindex="1" id="iConsecutivoCompania"  name="iConsecutivoCompania" class="required-field" style="height: 25px!important;width: 99%!important;"><option value="">Select an option...</option></select>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Broker <span style="color:#ff0000;">*</span>:</label>  
                            <select tabindex="2" id="iConsecutivoBroker"  name="iConsecutivoBroker" class="required-field" style="height: 25px!important;width: 99%!important;" onchange="fn_endosos.get_broker_data();">
                                <option value="">Select an option...</option>
                                <option value="5">CRC INSURANCE SERVICES INC</option>
                            </select>
                        </div> 
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                       <div class="field_item">
                            <label title="If you need to write more than one email, please separate them by comma symbol (,).">Broker E-mail <span style="color:#ff0000;">*</span>:</label>  
                            <input id="sEmail"  name="sEmail" class="required-field" style="height: 25px!important;width: 99%!important;" title="If you need to write more than one email, please separate them by comma symbol (,)."/>
                        </div> 
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Message to send: (This message will be displayed before the endorsement information.)</label> 
                            <textarea tabindex="3" id="sMensajeEmail" name="sMensajeEmail" maxlenght="1000" style="resize: none;" title="Max. 1000 characters."></textarea>
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="field_item" style="padding: 15px 0px;"> 
                                <label style="float: left;padding: 10px 0px;margin-right: 10px;"><span style="color:#ff0000;">*</span> Application date from </label>
                                <div style="float: left;">
                                    <input tabindex="5" id="flt_dateFrom" name="flt_dateFrom" type="text"  placeholder="MM/DD/YY" style="width: 140px;" class="required-field">
                                    <label class="check-label" style="position: relative;top: 0px;color:#000!important;">To</label>
                                    <input tabindex="6" id="flt_dateTo" name="flt_dateTo"   type="text"  placeholder="MM/DD/YY" style="width: 140px;" class="required-field">
                                </div>
                            </div>    
                        </td> 
                        <td>
                        <div class="field_item">
                            <label>Report Type:</label>  
                            <select tabindex="2" id="iTipoReporte"  name="iTipoReporte" style="height: 25px!important;width: 99%!important;">
                                <option value="">Both</option>
                                <option value="1">Vehicles (Unit/Trailer)</option>
                                <option value="2">Drivers</option>
                            </select>
                        </div> 
                        </td>   
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <br>
                        <div class="field_item">
                            <label>General Comments: (This comments are not be send by email to brokers)</label> 
                            <textarea tabindex="4" id="sComentarios" name ="sComentarios" style="resize:none;height:30px!important;"></textarea>
                        </div>
                        </td>
                    </tr>
                </table>
                <button type="button" class="btn-1" onclick="fn_endosos.save();" style="width: 210px;">Save & Generate Report</button> 
            </fieldset>
        </form>
    </div>
    </div>
</div>
<!-- DIALOGUES -->
<div id="dialog_delete" title="Delete" style="display:none;">
  <p><span class="ui-icon ui-icon-alert" ></span> Are you sure you want to disable the company? <br><span class="name" style="color:#0a87c1;font-weight:600;padding-left:20px;"></span></p>
  <form><div><input type="hidden" name="iConsecutivo" /></div></form>  
</div>
<!-- FOOTER -->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>