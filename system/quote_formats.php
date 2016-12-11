<?php session_start();    
if ( !($_SESSION["acceso"] != '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<!---- HEADER ----->
<?php include("header.php"); ?>   
<script type="text/javascript"> 
$(document).ready(inicio);
function inicio(){  
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);  
        //$("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); });
        fn_formats.init();
        $.unblockUI();
        
        //DRIVERS
        $('#dialog_driver_options').dialog({
            modal: true,
            autoOpen: false,
            width : 500,
            height : 200,
            resizable : false,
            buttons : {
                'CONTINUE' : function() {
                    var parameter = $('#dialog_driver_options #driver_option').val();
                    var form      = $('#dialog_driver_options #form_select').val();
                    fn_formats.cargar_driver_option(parameter,form);             
                },
                 'CANCEL' : function(){
                    $(this).dialog('close');
                }
            }
        });
        $('#dialog_driver_list').dialog({
            modal: true,
            autoOpen: false,
            width : 600,
            height : 500,
            resizable : false,
            buttons : {
                'CONTINUE' : function() {
                    
                    var list = "";
                    var type = "driver";
                    var form      = $('#dialog_driver_list #form_select').val();
                    $("#dialog_driver_list table .driverlist_id" ).each(function( index ){
                        if(this.checked){if(list != ''){list += "|" + this.value;}else{list += this.value;} }
                    });
                    fn_formats.save_list(list,form,type);               
                },
                 'CANCEL' : function(){
                    $(this).dialog('close');
                }
            }
        });
        $('#dialog_driver_form').dialog({
            modal: true,
            autoOpen: false,
            width : 550,
            height : 450,
            resizable : false,
            buttons : {
                'SAVE DRIVER' : function() {
                   
                   var name = $('#dialog_driver_form #sNombre').val();
                   var experience = $('#dialog_driver_form #iExperienciaYear').val();
                   var license = $('#dialog_driver_form #iNumLicencia').val();
                   var accident = $('#dialog_driver_form #sAccidentesNum').val();
                   var form      = $('#dialog_driver_form #form_select').val();
                   var iConsecutivoCompania =  $('#dialog_driver_form #iConsecutivoCompania').val();
                   
                   if($('#dialog_driver_form #iConsecutivo').val() != ""){var edit_mode = 'true';}else{var edit_mode = 'false';} 
                   
                   if(name != "" && license != ""){
                        $.post("funciones_quote_formats.php",
                        {
                            accion:"save_driver", 
                            sNombre: name, 
                            iExperienciaYear : experience,
                            iNumLicencia : license,
                            sAccidentesNum : accident,
                            iConsecutivoCompania : iConsecutivoCompania,
                            edit_mode : edit_mode,
                            token : $('#'+form+' #driver_token').val()
                        },
                        function(data){
                            if(data.error == '0'){
                                $('#'+form+' #driver_token').val(data.token); 
                                $('#dialog_driver_form,#dialog_driver_options').dialog('close');
                                fn_formats.get_list_drivers(form);  
                            }else{
                               fn_solotrucking.mensaje(data.msj);  
                            }       
                        },"json");             
                       
                   }else{
                      fn_solotrucking.mensaje('Please verify if the name and license have a valid value.'); 
                   }
                },
                 'CANCEL' : function(){
                    $(this).dialog('close');
                }
            }
        });
        //UNIT AND TRAILER:
        $('#dialog_unit_trailer_options').dialog({
            modal: true,
            autoOpen: false,
            width : 500,
            height : 200,
            resizable : false,
            buttons : {
                'CONTINUE' : function() {
                    var parameter = $('#dialog_unit_trailer_options #option').val();
                    var form      = $('#dialog_unit_trailer_options #form_select').val();
                    var tipo      = $('#dialog_unit_trailer_options #sTipo').val(); 
                    fn_formats.cargar_unit_trailer_option(parameter,form,tipo);             
                },
                 'CANCEL' : function(){
                    $(this).dialog('close');
                }
            }
        });
        $('#dialog_unit_trailer_list').dialog({
            modal: true,
            autoOpen: false,
            width : 600,
            height : 500,
            resizable : false,
            buttons : {
                'CONTINUE' : function() {
                    
                    var list = "";
                    var type = $('#dialog_unit_trailer_list #sTipo').val();
                    var form = $('#dialog_unit_trailer_list #form_select').val();
                    $("#dialog_unit_trailer_list table .utlist_id" ).each(function( index ){
                        if(this.checked){if(list != ''){list += "|" + this.value;}else{list += this.value;} }
                    });
                    fn_formats.save_list(list,form,type);               
                },
                 'CANCEL' : function(){
                    $(this).dialog('close');
                }
            }
        });
        $('#dialog_unit_trailer_form').dialog({
            modal: true,
            autoOpen: false,
            width : 580,
            height : 450,
            resizable : false,
            buttons : {
                'SAVE DRIVER' : function() {
                   
                   var vin = $('#dialog_unit_trailer_form #sVIN').val();
                   var year = $('#dialog_unit_trailer_form #iYear').val();
                   var make = $('#dialog_unit_trailer_form #iModelo').val();
                   var deductible = $('#dialog_unit_trailer_form #iTotalPremiumPD').val();
                   var form = $('#dialog_unit_trailer_form #form_select').val();
                   var tipo = $('#dialog_unit_trailer_form #sTipo').val(); 
                   var iConsecutivoCompania =  $('#dialog_unit_trailer_form #iConsecutivoCompania').val();
                   
                   if($('#dialog_unit_trailer_form #iConsecutivo').val() != ""){var edit_mode = 'true';}else{var edit_mode = 'false';} 
                   
                   if(vin != ""){
                        $.post("funciones_quote_formats.php",
                        {
                            accion:"save_ut", 
                            sVIN: vin, 
                            iYear : year,
                            iModelo : make,
                            iTotalPremiumPD : deductible,
                            sTipo : tipo,
                            iConsecutivoCompania : iConsecutivoCompania,
                            edit_mode : edit_mode,
                            token : $('#'+form+' #'+tipo+'_token').val()
                        },
                        function(data){
                            if(data.error == '0'){
                                $('#'+form+' #'+tipo+'_token').val(data.token); 
                                $('#dialog_unit_trailer_form,#dialog_unit_trailer_options').dialog('close');
                                fn_formats.get_list_ut(form,tipo);  
                            }else{
                               fn_solotrucking.mensaje(data.msj);  
                            }       
                        },"json");             
                       
                   }else{fn_solotrucking.mensaje('Please verify if the VIN# have a valid value.'); } 
                },
                 'CANCEL' : function(){
                    $(this).dialog('close');
                }
            }
        });
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
var fn_formats = {
        domroot:"#ct_companies",
        data_grid: "#data_grid_companies",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "iConsecutivo",
        init : function(){
            fn_formats.fillgrid();
            $('.num').keydown(fn_solotrucking.inputnumero()); 
            //Filtrado con la tecla enter
            $(fn_formats.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_formats.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_formats.filtraInformacion();
                }
            }); 
            //Cargar Modelos para unidades:
            $.ajax({             
                type:"POST", 
                url:"funciones_endorsements.php", 
                data:{accion:"get_unit_models"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $("#dialog_unit_trailer_form #iModelo").empty().append(data.select);
                     
                }
            });
            //Cargar Radio para unidades:
            /*$.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_unit_radio"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $("#frm_unit_information #iConsecutivoRadio_unit").empty().append(data.select);
                     
                }
            });*/
            //Cargar Años:
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_years"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $("#dialog_unit_trailer_form #iYear").empty().append(data.select);
                     
                }
            }); 
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_quote_formats.php", 
                data:{
                    accion:"get_formats",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_formats.pagina_actual, 
                    filtroInformacion : fn_formats.filtro,  
                    ordenInformacion : fn_formats.orden,
                    sortInformacion : fn_formats.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_formats.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_formats.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_formats.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_formats.data_grid+" tfoot #paginas_total").val(data.total);
                    $(fn_formats.data_grid+" tfoot #pagina_actual").val(data.pagina);
                    fn_formats.pagina_actual = data.pagina; 
                }
            }); 
        },
        firstPage : function(){
            if($(fn_formats.data_grid+" #pagina_actual").val() != "1"){
                fn_formats.pagina_actual = "";
                fn_formats.fillgrid();
            }
        },
        previousPage : function(){
            if($(fn_formats.data_grid+" #pagina_actual").val() != "1"){
                fn_formats.pagina_actual = (parseInt($(fn_formats.data_grid+" #pagina_actual").val()) - 1) + "";
                fn_formats.fillgrid();
            }
        },
        nextPage : function(){
            if($(fn_formats.data_grid+" #pagina_actual").val() != $(fn_formats.data_grid+" #paginas_total").val()){
                fn_formats.pagina_actual = (parseInt($(fn_formats.data_grid+" #pagina_actual").val()) + 1) + "";
                fn_formats.fillgrid();
            }
        },
        lastPage : function(){
            if($(fn_formats.data_grid+" #pagina_actual").val() != $(fn_formats.data_grid+" #paginas_total").val()){
                fn_formats.pagina_actual = $(fn_formats.data_grid+" #paginas_total").val();
                fn_formats.fillgrid();
            }
        }, 
        ordenamiento : function(campo,objeto){
            $(fn_formats.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_formats.orden){
                if(fn_formats.sort == "ASC"){
                    fn_formats.sort = "DESC";
                    $(fn_formats.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_formats.sort = "ASC";
                    $(fn_formats.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_formats.sort = "ASC";
                fn_formats.orden = campo;
                $(fn_formats.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
            }
            fn_formats.fillgrid();

            return false;
        }, 
        filtraInformacion : function(){
            fn_formats.pagina_actual = 0;
            fn_formats.filtro = "";
            if($(fn_formats.data_grid+" .flt_id").val() != ""){ fn_formats.filtro += "iConsecutivo|"+$(fn_formats.data_grid+" .flt_id").val()+","}
            if($(fn_formats.data_grid+" .flt_name").val() != ""){ fn_formats.filtro += "sTituloArchivoEmpresa|"+$(fn_formats.data_grid+" .flt_name").val()+","} 
            if($(fn_formats.data_grid+" .flt_filename").val() != ""){ fn_formats.filtro += "sNombreArchivoEmpresa|"+$(fn_formats.data_grid+" .flt_filename").val()+","}     
            fn_formats.fillgrid();
        },
        //DRIVERS...
        cargar_driver_option : function(parameter,form){
            
          var iConsecutivoCompania = $('#'+form+' #iConsecutivoCompania').val(); 
          var token = $('#'+form+' #driver_token').val();
          
          if(parameter == "NEW"){
              $("#dialog_driver_form input").val('');
              $('#dialog_driver_form #form_select').val(form);
              $('#dialog_driver_form #iConsecutivoCompania').val(iConsecutivoCompania);
              $("#dialog_driver_form").dialog( 'open' );
              
          }else if(parameter == "EXISTING"){
              $.post("funciones_quote_formats.php", {accion: "get_drivers_list",iConsecutivoCompania : iConsecutivoCompania,token:token},
               function(data){ 
                   if(data.error == '0' && data.total != '0'){
                       $("#dialog_driver_list table tbody").empty().append(data.tabla);
                       $('#dialog_driver_list #form_select').val(form);
                       $("#dialog_driver_list").dialog( 'open' );
                   }else{fn_solotrucking.mensaje(data.mensaje);}
                   
               },"json");
          }else{fn_solotrucking.mensaje('Please first select an option before to continue.');}  
        },
        save_list : function(list,form,type){
            if(list != ""){
                var token = $('#'+form+' #'+type+'_token').val();
                $.post("funciones_quote_formats.php", {accion: "save_list",list:list,token:token},
                function(data){ 
                   if(data.error == '0'){
                       $('#'+form+' #'+type+'_token').val(data.token);
                       if(type == 'driver'){fn_formats.get_list_drivers(form);}
                       else{fn_formats.get_list_ut(form,type);}
                       
                   }else{fn_solotrucking.mensaje(data.mensaje);}   
                },"json");
            }else{fn_solotrucking.mensaje('Please first select a driver before to continue.');}
        },
        get_list_drivers : function(form){
           $.post("funciones_quote_formats.php", {accion: "get_list_drivers",token: $('#'+form+' #driver_token').val()},
           function(data){ 
                   if(data.error == '0'){
                       $('#'+form+' #driver_list tbody').empty().append(data.tabla);
                       $('#dialog_driver_list,#dialog_driver_options').dialog('close');
                   }else{fn_solotrucking.mensaje(data.msj);}   
           },"json"); 
        },
        //UNIT AND TRAILERS:
        cargar_unit_trailer_option : function(parameter,form,tipo){
           
           var iConsecutivoCompania = $('#'+form+' #iConsecutivoCompania').val(); 
           var token = $('#'+form+' #'+tipo+'_token').val();
          
           if(parameter == "NEW"){
              $("#dialog_unit_trailer_form input").val('');
              $('#dialog_unit_trailer_form #form_select').val(form);
              $('#dialog_unit_trailer_form #sTipo').val(tipo);
              $('#dialog_unit_trailer_form #iConsecutivoCompania').val(iConsecutivoCompania);
              $("#dialog_unit_trailer_form").dialog( 'open' );
           }else if(parameter == "EXISTING"){
              $.post("funciones_quote_formats.php", {accion: "get_unit_trailer_list",iConsecutivoCompania : iConsecutivoCompania,token:token,tipo:tipo},
               function(data){ 
                   if(data.error == '0' && data.total != '0'){
                       $("#dialog_unit_trailer_list table tbody").empty().append(data.tabla);
                       $('#dialog_unit_trailer_list #form_select').val(form);
                       $('#dialog_unit_trailer_list #sTipo').val(tipo);
                       $("#dialog_unit_trailer_list").dialog( 'open' );
                   }else{fn_solotrucking.mensaje(data.mensaje);}
               },"json");
           }else{fn_solotrucking.mensaje('Please first select an option before to continue.');}
        },
        get_list_ut : function(form,type){
           $.post("funciones_quote_formats.php", {accion: "get_list_ut",token: $('#'+form+' #'+type+'_token').val()},
           function(data){ 
                   if(data.error == '0'){
                       $('#'+form+' #'+type+'_list tbody').empty().append(data.tabla);
                       $('#dialog_unit_trailer_list,#dialog_unit_trailer_options').dialog('close');
                   }else{fn_solotrucking.mensaje(data.msj);}   
           },"json"); 
        },
        //FORMULARIOS
        form_commercial_auto_quick : {
           init : function(){
               //Cargar Companies:
               $.post("catalogos_generales.php", { accion: "get_companies"},function(data){ $("#form_commercial_auto_quick #iConsecutivoCompania").empty().append(data.select);},"json");
               //Limpiar form:
               $('#form_commercial_auto_quick input:text, #form_commercial_auto_quick select').val('');
               $('#form_commercial_auto_quick input:radio').prop('checked','');
               //fn_solotrucking.get_date(".fecha"); 
               fn_popups.resaltar_ventana('form_commercial_auto_quick');  
           },
           get_company_info : function(id_company){
               $.post("funciones_quote_formats.php", {accion: "get_companies_info",iConsecutivo : id_company,domroot:"form_commercial_auto_quick"},
               function(data){ 
                   if(data.error == '0'){
                       eval(data.fields);
                   }//else{fn_solotrucking.mensaje(data.msj);}
                   
               },"json");
           },
           open_driver_dialog : function(){
               if($('#form_commercial_auto_quick #iConsecutivoCompania').val() != ""){
                  $('#dialog_driver_options input,#dialog_driver_options select ').val('');
                  $('#dialog_driver_options #form_select').val('form_commercial_auto_quick');
                  $('#dialog_driver_options').dialog( 'open' );
                  return false; 
               }else{fn_solotrucking.mensaje('Please first select a company.');}
           },
           open_unit_trailer_dialog : function(tipo){
              if($('#form_commercial_auto_quick #iConsecutivoCompania').val() != ""){
                  $('#dialog_unit_trailer_options input ,#dialog_driver_options select ').val('');
                  $('#dialog_unit_trailer_options #form_select').val('form_commercial_auto_quick');
                  $('#dialog_unit_trailer_options #sTipo').val(tipo);
                  $('#dialog_unit_trailer_options').dialog( 'open' );
                  return false; 
              }else{fn_solotrucking.mensaje('Please first select a company.');} 
           },
           open_pdf : function(){
               //variables:
               var iConsecutivoCompania = $('#form_commercial_auto_quick #iConsecutivoCompania').val();
               var sYearsExperiencia    = $('#form_commercial_auto_quick #sYearsExperiencia').val();
               var iFEINNumb            = $('#form_commercial_auto_quick #iFEINNumb').val();
               var sCommodities         = $('#form_commercial_auto_quick #sCommodities').val();
               var iFillings            = $('#form_commercial_auto_quick input:radio[name=iFillings]').val();
               var iRadius              = $('#form_commercial_auto_quick input:radio[name=iRadius]').val();
               var iTokenDriver         = $('#form_commercial_auto_quick #driver_token').val(); 
               var iTokenUnit           = $('#form_commercial_auto_quick #unit_token').val(); 
               var iTokenTrailer        = $('#form_commercial_auto_quick #trailer_token').val(); 
               var iAutoLiability       = $('#form_commercial_auto_quick input:radio[name=iAutoLiability]').val();  
               var iAutoLiabilityD      = $('#form_commercial_auto_quick input:radio[name=iAutoLiabilityD]').val();
               var iInsuredMBI          = $('#form_commercial_auto_quick input:radio[name=iInsuredMBI]').val();
               var iCargo               = $('#form_commercial_auto_quick input:radio[name=iCargo]').val();
               //var iOtherCoverage1      = $('#form_commercial_auto_quick #iOtherCoverage1').val(); 
               
               window.open('PDF_universal_quick_quotes_1.php?consecutivo_doc=1&id_compania='+iConsecutivoCompania+'&consecutivo_drivers='+iTokenDriver+'&consecutivo_equipment'+iTokenUnit+'&consecutivo_trailer='+iTokenTrailer+'&year_in_bussines='+sYearsExperiencia+'&fein='+iFEINNumb+'&commodities_hauled='+sCommodities+'&filing_required='+iFillings);
           } 
        } 
}     
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_companies" class="container">
        <div class="page-title">
            <h1>QUOTES</h1>
            <h2>APPLICATION & AGREEMENTS FORMATS</h2>
        </div>
        <table id="data_grid_companies" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'><input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_name"     type="text" placeholder="Format Name:"></td>
                <td><input class="flt_filename" type="text" placeholder="File Name:"  ></td>
                <td style='width:100px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_formats.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <!---<div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_formats.add();"><i class="fa fa-plus"></i></div>-->
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_formats.ordenamiento('iConsecutivo',this.cellIndex);">ID</td>
                <td class="etiqueta_grid"      onclick="fn_formats.ordenamiento('sTituloArchivoEmpresa',this.cellIndex);">FORMAT NAME</td>
                <td class="etiqueta_grid"      onclick="fn_formats.ordenamiento('sNombreArchivoEmpresa',this.cellIndex);">ORIGINAL FILE NAME</td>
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
                        <button id="pgn-inicio"    onclick="fn_formats.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_formats.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_formats.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_formats.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>
        
    </div>
</div>
<!---- FORMULARIOS ------>
<div id="form_commercial_auto_quick" class="popup-form" style="width:1000px">
    <div class="p-header">
        <h2>QUOTE FORMAT - COMMERCIAL AUTO QUICK QUOTE FORM</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('form_commercial_auto_quick');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div id="company_information">
        <form>
            <fieldset>
                <p class="mensaje_valido">&nbsp;The fields containing an (*) are required.</p> 
                <table style="width:100%">
                    <tr>
                        <td>
                            <div class="field_item">
                                <input id="iConsecutivo" name="iConsecutivo" type="hidden" value="">
                                <label>Named Insured <span style="color:#ff0000;">*</span>:</label> 
                                <select tabindex="1" id="iConsecutivoCompania" onblur="fn_formats.form_commercial_auto_quick.get_company_info(this.value);"><option value="">Select an option...</option></select>
                            </div>
                        </td>
                        <td>
                            <div class="field_item"> 
                                <label>Ph#:</label>  
                                <input tabindex="2" id="sTelefonoPrincipal" class="numb" name="sTelefonoPrincipal" type="text" readonly="readonly">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Garaging Address:</label> 
                                <input tabindex="3" id="sDireccion" name="sDireccion" type="text" readonly="readonly">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:80%">
                            <div class="field_item">
                                <label>No. Of Years In Business (With own insurance):</label> 
                                <input tabindex="4" id="sYearsExperiencia" class="num" name="sYearsExperiencia" type="text" maxlength="2" style="width: 99%;"> 
                            </div>
                        </td>
                        <td>
                            <div class="field_item">
                                <label>FEIN#:</label> 
                                <input tabindex="5" id="iFEINNumb" name="iFEINNumb" type="text" maxlength="15"> 
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Commodities Hauled (Be specific about percent of time):</label> 
                                <input tabindex="6" id="sCommodities" name="sCommodities" type="text">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Filings Required:</label> 
                                <label class="lbl-check"><input tabindex="7"  name="iFillings" type="radio" value="0"> None</label>
                                <label class="lbl-check"><input tabindex="8"  name="iFillings" type="radio" value="1"> YES</label>
                                                         <!--<input tabindex="9" id="" name="" type="text" style="height: 27px;width: auto;">  
                                <label class="lbl-check"><input tabindex="10"  id="" name="" type="checkbox"> DMV</label>
                                                         <input tabindex="11" id="" name="" type="text" style="height: 27px;width: auto;"> 
                                <label class="lbl-check"><input tabindex="12" id="" name="" type="checkbox"> OTHER</label>
                                                         <input tabindex="13" id="" name="" type="text" style="height: 27px;width: auto;">-->   
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Radius:</label>
                                <label class="lbl-check"><input tabindex="15"  name="iRadio" type="radio" value="250"> -250 Miles</label>
                                <label class="lbl-check"><input tabindex="16"  name="iRadio" type="radio" value="500"> -500 Miles</label>
                                <label class="lbl-check"><input tabindex="17"  name="iRadio" type="radio" value="500p">  +500 Miles</label> 
                                <!---<label class="lbl-check"><input tabindex="14"  id="" name="" type="checkbox"> Intrastate (CA Only)</label>
                                <label class="lbl-check"><input tabindex="15"  id="" name="" type="checkbox"> 0-100 Miles</label>
                                <label class="lbl-check"><input tabindex="16"  id="" name="" type="checkbox"> 101-200 Miles</label>
                                <label class="lbl-check"><input tabindex="17" id="" name="" type="checkbox"> 201-300 Miles</label>
                                <label class="lbl-check"><input tabindex="18" id="" name="" type="checkbox"> 301-500 Miles</label> 
                                <label class="lbl-check"><input tabindex="19" id="" name="" type="checkbox"> Interstate - Exactly Where?</label>
                                <input tabindex="20" id="" name="" type="text" style="height: 27px;width: 120px;">--->    
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Driver(s):</label>
                                <input id="driver_token" name="driver_token" type="hidden" value="">  
                                <table id="driver_list" style="width:100%;" class="popup-datagrid">
                                <thead>
                                    <tr id="grid-head2">
                                        <td class="etiqueta_grid">Name</td>
                                        <td class="etiqueta_grid">YRS EXP</td>
                                        <td class="etiqueta_grid">ACCIDENTS</td>
                                        <td class="etiqueta_grid" style="width: 100px;text-align: center;">
                                            <div class="btn-icon add btn-left" title="Add +"  onclick="fn_formats.form_commercial_auto_quick.open_driver_dialog();"><i class="fa fa-plus"></i></div>
                                        </td>
                                    </tr>
                                </thead>
                                <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                                </table>
                                <p style="font-size: 10px;">*Specify the number of year's commercial driving experience each driver as. If there are any drivers with a "not at foult" accident, please provide a copy of the policy report with your submission.</p>    
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Equipment:</label>
                                <input id="unit_token" name="unit_token" type="hidden" value="">  
                                <table id="unit_list" style="width:100%;" class="popup-datagrid">
                                <thead>
                                    <tr id="grid-head2">
                                        <td class="etiqueta_grid">YEAR</td>
                                        <td class="etiqueta_grid">MAKE</td>
                                        <td class="etiqueta_grid">BODY TYPE</td>
                                        <td class="etiqueta_grid">GBW</td>
                                        <td class="etiqueta_grid">STATED VALUE</td> 
                                        <td class="etiqueta_grid">DEDUCTIBLE</td> 
                                        <td class="etiqueta_grid" style="width: 100px;text-align: center;">
                                            <div class="btn-icon add btn-left" title="Add +"  onclick="fn_formats.form_commercial_auto_quick.open_unit_trailer_dialog('unit');"><i class="fa fa-plus"></i></div> 
                                        </td>
                                    </tr>
                                </thead>
                                <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                                </table>
                                <p style="font-size: 10px;">*If there are 5 or more power units, please provide a completed ACORD or completed company application instead of this form for quoting.</p>    
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Trailer(s):</label>
                                <input id="trailer_token" name="trailer_token" type="hidden" value=""> 
                                <table id="trailer_list" style="width:100%;" class="popup-datagrid">
                                <thead>
                                    <tr id="grid-head2">
                                        <td class="etiqueta_grid">YEAR</td>
                                        <td class="etiqueta_grid">MAKE</td>
                                        <td class="etiqueta_grid">BODY TYPE</td>
                                        <td class="etiqueta_grid">GBW</td>
                                        <td class="etiqueta_grid">STATED VALUE</td> 
                                        <td class="etiqueta_grid">DEDUCTIBLE</td> 
                                        <td class="etiqueta_grid" style="width: 100px;text-align: center;">
                                            <div class="btn-icon add btn-left" title="Add +"  onclick="fn_formats.form_commercial_auto_quick.open_unit_trailer_dialog('trailer');"><i class="fa fa-plus"></i></div>
                                        </td>
                                    </tr>
                                </thead>
                                <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                                </table>
                                <p style="font-size: 10px;">*Please specify if applicant is pulling non-owned trailers and if applicant is pulling doubles.</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Coverages:</label>
                            </div>
                            <div class="field_item">
                                <label style="float: left;padding: 12px 0px 0px;display: block;width: 150px;">Auto Liability:</label> 
                                <label class="lbl-check"><input tabindex="21"  name="iAutoLiability" type="radio" value="100"> $100K CSL</label>
                                <label class="lbl-check"><input tabindex="22"  name="iAutoLiability" type="radio" value="300"> $300K CSL</label>
                                <label class="lbl-check"><input tabindex="23"  name="iAutoLiability" type="radio" value="500"> $500K CSL</label>
                                <label class="lbl-check"><input tabindex="24"  name="iAutoLiability" type="radio" value="750"> $750 CSL</label>
                                <label class="lbl-check"><input tabindex="25"  name="iAutoLiability" type="radio" value="1m"> $1M CSL</label> 
                                <label class="lbl-check"><input tabindex="26"  name="iAutoLiability" type="radio" value="other"> Other</label>
                                <input tabindex="27" id="iAutoLiability" type="text" style="height: 27px;width: 120px;">
                            </div> 
                            <div class="field_item"> 
                                <label style="float: left;padding: 12px 0px 0px;display: block;width: 150px;">Auto Liability Deductible:</label> 
                                <label class="lbl-check"><input tabindex="28" name="iAutoLiabilityD" type="radio" value="500"> $500</label> 
                            </div> 
                            <div class="field_item">
                                <label style="float: left;padding: 12px 0px 0px;display: block;width: 150px;">Ininsured Motorist BI:</label> 
                                <label class="lbl-check"><input tabindex="29" name="iInsuredMBI" type="radio" value="15"> $15,000/30,000</label>
                                <label class="lbl-check"><input tabindex="23" name="iInsuredMBI" type="radio" value="25"> $25,000/50,000</label> 
                                <label class="lbl-check"><input tabindex="31" name="iInsuredMBI" type="radio" value="30"> $30,000/60,000</label> 
                            </div> 
                            <div class="field_item">
                                <label style="float: left;padding: 12px 0px 0px;display: block;width: 150px;">Cargo:</label> 
                                <label class="lbl-check"><input tabindex="32"  name="iCargo" type="radio" value="25"> $25,000</label> 
                                <label class="lbl-check"><input tabindex="33"  name="iCargo" type="radio" value="50"> $50,000</label> 
                                <label class="lbl-check"> Deductible <input tabindex="34" id="iCargo"type="text" style="height: 27px;width: 120px;"></label>   
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Other Coverage:</label><br> 
                                <input tabindex="27" id="iOtherCoverage1" type="text" style="height: 27px;width: 120px;">  
                                <label class="lbl-check"><input tabindex="15" name="OtherCoverage" type="radio"></label>
                                <input tabindex="27" id="iOtherCoverage2" type="text" style="height: 27px;width: 120px;">  
                                <label class="lbl-check"><input tabindex="17" name="OtherCoverage" type="radio"></label>
                                <input tabindex="27" id="iOtherCoverage3" type="text" style="height: 27px;width: 120px;">  
                                <label class="lbl-check"> Deductible</label> 
                                <input tabindex="34" id="iOtherCoverage4" type="text" style="height: 27px;width: 120px;">    
                            </div>
                        </td>
                    </tr>
                </table>
                <button type="button" class="btn-1" onclick="fn_formats.form_commercial_auto_quick.open_pdf();">GENERATE PDF FORMAT</button> 
            </fieldset>
        </form>
    </div>
    </div>
</div>
<!--- DIALOGUES DRIVERS--->
<div id="dialog_driver_options" title="SELECT AN OPTION">
  <p>Please select an of following options:</p>
  <input  id="form_select" type="hidden" value="">
  <select id="driver_option">
    <option value="">Select an option...</option> 
    <option value="EXISTING">Select an of existing driver(s) in the list</option>
    <option value="NEW">Add a new driver on the list.</option>
  </select>
</div>
<div id="dialog_driver_list" title="SELECT DRIVERS TO ADD">
  <p>Please select the drivers of company list:</p>
  <input  id="form_select" type="hidden" value=""> 
  <table style="width: 100%;">
   <thead>
    <tr id="grid-head2">
        <td class="etiqueta_grid" style="width:50px;text-align: center;"></td>
        <td class="etiqueta_grid">Name</td>
        <td class="etiqueta_grid">YRS EXP</td>
        <td class="etiqueta_grid">ACCIDENTS</td>
    </tr>
   </thead>
   <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
  </table>
</div>
<div id="dialog_driver_form" title="DRIVER INFORMATION">
  <p>Please select an of following options:</p>
  <form class="p-container">
  <input id="form_select" type="hidden" value="">
  <input id="iConsecutivo" type="hidden" value="">
  <input id="iConsecutivoCompania" type="hidden" value="">  
  <table style="width: 100%;">
    <tr><td><br><br></td></tr>
    <tr><td><div class="field_item"> <label>Name:</label><input id="sNombre" type="text" placeholder="last name + first name" class="txt-uppercase"></div></td></tr>
    <tr><td><div class="field_item"> <label>Experience Years:</label><input id="iExperienciaYear" type="text" class="num"></div></td></tr>
    <tr><td><div class="field_item"> <label>License#:</label><input id="iNumLicencia" type="text" class="txt-uppercase"></div></td></tr> 
    <tr><td><div class="field_item"> <label>Accidents:</label><input id="sAccidentesNum" type="text" class="num"></div></td></tr>
  </table>
  </form>  
</div>
<!--- DIALOGUES UNITS AND TRAILERS --->
<div id="dialog_unit_trailer_options" title="SELECT AN OPTION">
  <p>Please select an of following options:</p>
  <input  id="form_select" type="hidden" value="">
  <input  id="sTipo" type="hidden" value="">
  <select id="option">
    <option value="">Select an option...</option> 
    <option value="EXISTING">Select an of existing data in the list</option>
    <option value="NEW">Add a new data on the list.</option>
  </select>
</div>
<div id="dialog_unit_trailer_list" title="SELECT DATA TO ADD">
  <p>Please select the data of company list:</p>
  <input  id="form_select" type="hidden" value="">
  <input  id="sTipo" type="hidden" value="">  
  <table style="width: 100%;">
   <thead>
    <tr id="grid-head2">
        <td class="etiqueta_grid" style="width:50px;text-align: center;"></td>
        <td class="etiqueta_grid">YEAR</td>
        <td class="etiqueta_grid">MAKE</td>
        <td class="etiqueta_grid">VIN#</td> 
        <td class="etiqueta_grid">BODY TYPE</td> 
        <td class="etiqueta_grid">DEDUCTIBLE</td> 
    </tr>
   </thead>
   <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
  </table>
</div>
<div id="dialog_unit_trailer_form" title="DRIVER INFORMATION">
  <p>Please select an of following options:</p>
  <form class="p-container">
  <input id="form_select" type="hidden" value="">
  <input id="sTipo" type="hidden" value=""> 
  <input id="iConsecutivo" type="hidden" value="">
  <input id="iConsecutivoCompania" type="hidden" value="">  
  <table style="width: 100%;">
    <tr><td><br><br></td></tr>
    <tr><td><div class="field_item"> <label>VIN#:</label><input  id="sVIN" type="text"  class="txt-uppercase"></div></td></tr>
    <tr><td><div class="field_item"> <label>Year:</label><select id="iYear" type="text" placeholder=""><option value="" >Select an option...</option></select></div></td></tr>
    <tr><td><div class="field_item"> <label>Make:</label><select id="iModelo" type="text" placeholder=""><option value="" >Select an option...</option></select></div></td></tr> 
    <tr><td><div class="field_item"> <label>Deductible:</label><input id="iTotalPremiumPD" type="text" class="num"></div></td></tr>
  </table>
  </form>  
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>