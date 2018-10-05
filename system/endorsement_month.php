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
            var dates_rp = $("#frm_edit_new input[name=dFechaInicio], #frm_edit_new input[name=dFechaFin]" ).datepicker({
                changeMonth: true,
                dateFormat: 'mm/dd/yy',
                onSelect: function( selectedDate ) {
                    var option = this.id == "dFechaInicio" ? "minDate" : "maxDate",
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
                    fn_endosos.edit();
                    //fn_endosos.delete_confirm();
                }
            }); 
        },
        add : function(){
           $('#frm_edit_new :text, #frm_edit_new select').val('').removeClass('error');
           var fechas = fn_solotrucking.obtener_fechas();
           $("#frm_edit_new input[name=dFechaInicio]").val(fechas[1]); 
           $("#frm_edit_new input[name=dFechaFin]").val(fechas[2]);
           //Habilitar campos:
           $('#dFechaInicio, #dFechaFin').removeProp('readonly');
           $('#frm_edit_new select[name=iConsecutivoCompania], #frm_edit_new select[name=iConsecutivoBroker],#frm_edit_new select[name=iTipoReporte]').removeProp('disabled'); 
           $("#frm_edit_new input[name=dFechaInicio], #frm_edit_new input[name=dFechaFin]" ).datepicker( "option", "disabled", false );
           $("#frm_edit_new textarea[name=sMensajeEmail]").val("Please create new endorsement for the following insured.");
           $('#frm_edit_new #data_grid_detalle').hide();
           fn_popups.resaltar_ventana('frm_edit_new');  
        },
        edit : function (){
            $(fn_endosos.data_grid + " tbody td .btn_edit").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                fn_endosos.get_data(clave);
          });  
        },
        get_data : function(clave){
           if(clave != ""){
                $.post("endorsement_month_server.php",{accion:"get_data", clave: clave, domroot : "frm_edit_new"},
                function(data){
                    if(data.error == '0'){
                        $('#frm_edit_new :text, #frm_edit_new select').val('').removeClass('error');
                        //Deshabilitar campos:
                        $('#dFechaInicio, #dFechaFin').prop('readonly','readonly');
                        $('#frm_edit_new select[name=iConsecutivoCompania], #frm_edit_new select[name=iConsecutivoBroker],#frm_edit_new select[name=iTipoReporte]').prop('disabled','disabled');
                        $("#frm_edit_new input[name=dFechaInicio], #frm_edit_new input[name=dFechaFin]" ).datepicker( "option", "disabled", true );
                        eval(data.fields);
                        $('#frm_edit_new #data_grid_detalle').show();
                        fn_endosos.detalle.iConsecutivo = clave;
                        fn_endosos.detalle.fillgrid(); 
                        fn_popups.resaltar_ventana('frm_edit_new');
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json");      
           } 
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
             struct_data_post.domroot = "#data_general"; 
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
            if($(fn_endosos.data_grid+" .flt_id").val() != ""){ fn_endosos.filtro += "A.iConsecutivo|"+$(fn_endosos.data_grid+" .flt_id").val()+","}
            if($(fn_endosos.data_grid+" .flt_name").val() != ""){ fn_endosos.filtro += "B.sNombreCompania|"+$(fn_endosos.data_grid+" .flt_name").val()+","} 
            if($(fn_endosos.data_grid+" .flt_broker").val() != ""){ fn_endosos.filtro += "C.sName|"+$(fn_endosos.data_grid+" .flt_broker").val()+","} 
            if($(fn_endosos.data_grid+" .flt_email").val() != ""){ fn_endosos.filtro += "A.sEmail|"+$(fn_endosos.data_grid+" .flt_email").val()+","} 
            if($(fn_endosos.data_grid+" .flt_date").val() != ""){ fn_endosos.filtro += "A.dFechaAplicacion|"+$(fn_endosos.data_grid+" .flt_date").val()+","}    
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
        },
        //Detalle:
        detalle : {
            iConsecutivo : "",
            iTipo : "",
            data_grid : "#data_grid_detalle .popup-datagrid",
            pagina_actual : "",
            filtro : "",
            orden  : "A.dFechaAplicacion",
            sort   : "DESC",
            fillgrid: function(){
                if(fn_endosos.detalle.iConsecutivo != ""){
                    fn_endosos.detalle.iTipo = $("#frm_edit_new select[name=iTipoReporte]").val();
                    $.ajax({             
                        type:"POST", 
                        url:"endorsement_month_server.php", 
                        data:{
                            accion               : "detalle_get_datagrid",
                            iConsecutivo         : fn_endosos.detalle.iConsecutivo,
                            iTipoReporte         : fn_endosos.detalle.iTipo,
                            registros_por_pagina : "15", 
                            pagina_actual        : fn_endosos.detalle.pagina_actual, 
                            filtroInformacion    : fn_endosos.detalle.filtro,  
                            ordenInformacion     : fn_endosos.detalle.orden,
                            sortInformacion      : fn_endosos.detalle.sort,
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $(fn_endosos.detalle.data_grid+" tbody").empty().append(data.tabla);
                            $(fn_endosos.detalle.data_grid+" tbody tr:even").addClass('gray');
                            $(fn_endosos.detalle.data_grid+" tbody tr:odd").addClass('white');
                            $(fn_endosos.detalle.data_grid + " tfoot .paginas_total").val(data.total);
                            $(fn_endosos.detalle.data_grid + " tfoot .pagina_actual").val(data.pagina);
                            fn_endosos.detalle.pagina_actual = data.pagina; 
                            //fn_endosos.delete_confirm();
                        }
                    });     
                }
            },
            firstPage : function(){
                if($(fn_endosos.detalle.data_grid+" .pagina_actual").val() != "1"){
                    fn_endosos.detalle.pagina_actual = "";
                    fn_endosos.detalle.fillgrid();
                }
            },
            previousPage : function(){
                if($(fn_endosos.detalle.data_grid+" .pagina_actual").val() != "1"){
                    fn_endosos.detalle.pagina_actual = (parseInt($(fn_endosos.detalle.data_grid+" .pagina_actual").val()) - 1) + "";
                    fn_endosos.detalle.fillgrid();
                }
            },
            nextPage : function(){
                if($(fn_endosos.detalle.data_grid+" .pagina_actual").val() != $(fn_endosos.detalle.data_grid+" .paginas_total").val()){
                    fn_endosos.detalle.pagina_actual = (parseInt($(fn_endosos.detalle.data_grid+" .pagina_actual").val()) + 1) + "";
                    fn_endosos.detalle.fillgrid();
                }
            },
            lastPage : function(){
                if($(fn_endosos.detalle.data_grid+" .pagina_actual").val() != $(fn_endosos.detalle.data_grid+" .paginas_total").val()){
                    fn_endosos.detalle.pagina_actual = $(fn_endosos.detalle.data_grid+" .paginas_total").val();
                    fn_endosos.detalle.fillgrid();
                }
            },  
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
                <td><input class="flt_date" type="text" placeholder="MM/DD/YYYY"></td> 
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
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('frm_edit_new');fn_endosos.fillgrid();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <form>
            <fieldset>
                <legend style="margin-bottom:0px!important;">General Information</legend>
                <table id="data_general" style="width:100%;">
                    <tr><td colspan="100%"><p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p></td></tr>
                    <tr>
                        <td style="width:50%!important;">
                            <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                            <div class="field_item">
                                <label>Company <span style="color:#ff0000;">*</span>:</label>  
                                <select tabindex="1" id="iConsecutivoCompania"  name="iConsecutivoCompania" class="required-field" style="height: 25px!important;width: 99%!important;"><option value="">Select an option...</option></select>
                            </div>
                        </td>
                        <td style="width:50%!important;">
                            <div class="field_item">
                                <label>Report Type <span style="color:#ff0000;">*</span>:</label>  
                                <select tabindex="2" id="iTipoReporte"  name="iTipoReporte" style="height: 25px!important;width: 99%!important;" class="required-field">
                                    <option value="">Select an opction...</option>
                                    <option value="1">Vehicles (Unit/Trailer)</option>
                                    <option value="2">Drivers</option>
                                </select>
                            </div> 
                        </td>
                    </tr>
                    <tr>
                        <td style="width:50%!important;">
                            <div class="field_item">
                                <label>Broker <span style="color:#ff0000;">*</span>:</label>  
                                <select tabindex="2" id="iConsecutivoBroker"  name="iConsecutivoBroker" class="required-field" style="height: 25px!important;width: 99%!important;" onchange="fn_endosos.get_broker_data();">
                                    <option value="">Select an option...</option>
                                    <option value="5">CRC INSURANCE SERVICES INC</option>
                                </select>
                            </div> 
                        </td>
                        <td style="width:50%!important;">
                            <div class="field_item">
                                <label title="If you need to write more than one email, please separate them by comma symbol (,).">Broker E-mail <span style="color:#ff0000;">*</span>:</label>  
                                <input id="sEmail"  name="sEmail" class="required-field" style="width: 99%!important;" title="If you need to write more than one email, please separate them by comma symbol (,)."/>
                            </div> 
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Message to send: <span style="color:#044c8b;font-size:10px;">(This message will be displayed before the endorsement information.)</span></label> 
                            <textarea tabindex="3" id="sMensajeEmail" name="sMensajeEmail" maxlenght="1000" style="resize: none;" title="Max. 1000 characters." style="height: 50px!important;"></textarea>
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:50%!important;vertical-align: top;">
                            <div class="field_item"> 
                                <label style="padding: 9px 0px;"><span style="color:#ff0000;">*</span> Application date from </label><br>
                                <div>
                                    <input tabindex="5" id="dFechaInicio" name="dFechaInicio" type="text"  placeholder="MM/DD/YY" style="width: auto!important;" class="required-field">
                                    <label class="check-label" style="position: relative;top: 0px;color:#000!important;padding: 15px;">To</label>
                                    <input tabindex="6" id="dFechaFin" name="dFechaFin"   type="text"  placeholder="MM/DD/YY" style="width: auto!important;" class="required-field">
                                </div>
                            </div> 
                        </td>
                        <td style="width:50%!important;vertical-align: top;">
                        <div class="field_item">
                            <label>General Comments:<span style="color:#044c8b;font-size:10px;">(This comments are not be send by email to brokers)</span></label> 
                            <textarea tabindex="4" id="sComentarios" name ="sComentarios" style="resize:none;height: 14px!important;padding: 1px;"></textarea>
                        </div>
                        </td>
                    </tr>
                </table>
                <table style="width: 100%;" cellpadding="0" cellspacing="0">
                <tr id="data_grid_detalle">
                    <td colspan="2">
                    <h4 class="popup-gridtit">Endorsement Description</h4>
                    <table class="popup-datagrid" style="margin-bottom: 10px;" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr id="grid-head2">
                                <td class="etiqueta_grid" title="ID of endorsement on internal system.">ID</td>
                                <td class="etiqueta_grid">Action</td>
                                <td class="etiqueta_grid">Policy No.</td>
                                <td class="etiqueta_grid">Descripcion</td>
                                <td class="etiqueta_grid">Application Date</td>
                                <td class="etiqueta_grid" style="width: 100px;text-align: center;"></td>
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="100%">
                                    <div class="datagrid-pages" style="display: none;">
                                        <input class="pagina_actual" type="text" readonly="readonly" size="3">
                                        <label> / </label>
                                        <input class="paginas_total" type="text" readonly="readonly" size="3">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="100%">
                                    <div class="datagrid-menu-pages" style="display: none;">
                                        <button class="pgn-inicio"    onclick="fn_endosos.detalle.firstPage();" title="First page"><span></span></button>
                                        <button class="pgn-anterior"  onclick="fn_endosos.detalle.previousPage();" title="Previous"><span></span></button>
                                        <button class="pgn-siguiente" onclick="fn_endosos.detalle.nextPage();" title="Next"><span></span></button>
                                        <button class="pgn-final"     onclick="fn_endosos.detalle.lastPage();" title="Last Page"><span></span></button>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    </td>
                </tr>
                </table> 
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('frm_edit_new');fn_endosos.fillgrid();" style="margin-right:10px;background:#e8051b;">CLOSE</button>
                <button type="button" class="btn-1" onclick="fn_endosos.save();">Save</button> 
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