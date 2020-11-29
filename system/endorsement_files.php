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
        //$.blockUI();
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']).";";?>        
        var tipo_usuario   = <?php echo json_encode($_SESSION['acceso']).";";?>
        var clave          = <?php if(isset($_GET["idEndorsement"])   &&  $_GET["idEndorsement"] != ""){echo "'".$_GET["idEndorsement"]."';";}else{ echo "'';";} ?> 
        var company        = <?php if(isset($_GET["sNombreCompania"]) &&  $_GET["sNombreCompania"] != ""){echo "'".$_GET["sNombreCompania"]."';";}else{ echo "'';";} ?> 
        
        
        validapantalla(usuario_actual);
        //if(tipo_usuario != "1"){validarLoginCliente(usuario_actual);}
        fn_endorsement.init(clave,company);
        fn_endorsement.fillgrid();
        //$.unblockUI();
        
        $('#dialog_delete_file').dialog({
            modal: true,
            autoOpen: false,
            width : 300,
            height : 200,
            resizable : false,
            buttons : {
                'YES' : function() {
                    clave = $('#id_file').val();
                    $(this).dialog('close');                   
                    fn_endorsement.files.delete_file(clave);             
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
        orden : "dFechaAplicacion",
        init : function(clave,company){
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
            
            if(clave != ""){
                fn_endorsement.get_data(clave,company);    
            }     
           
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_endorsement_files.php", 
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
                    $(fn_endorsement.data_grid+" > tbody").empty().append(data.tabla);
                    $(fn_endorsement.data_grid+" > tbody > tr:even").addClass('gray');
                    $(fn_endorsement.data_grid+" > tbody > tr:odd").addClass('white');
                    $(fn_endorsement.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_endorsement.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_endorsement.pagina_actual = data.pagina;
                    fn_endorsement.edit();
                    fn_solotrucking.btn_tooltip();
                    
                }
            }); 
        },
        edit : function (){
            $(fn_endorsement.data_grid + " tbody td .btn_open_files").bind("click",function(){
                var clave    = $(this).parent().parent().find(" > td:eq(0)").html();
                var type     = $(this).parent().parent().find(" > td:eq(0)").prop('class'); 
                var decr     = $(this).parent().parent().find(" > td:eq(2)").html();
                var company  = $(this).parent().parent().find(" > td:eq(1)").text();
                
                if(type.trim() == 'DRIVER'){var category = '2'}else{var category = '1';}
                
                $('#endorsements_edit_form .p-header h2').empty().text('FILES OF ENDORSEMENT FROM COMPANY ' + company);
                $('#endorsements_edit_form .info-endoso > legend').empty().html('ENDORSEMENT FROM COMPANY ' + company);
                $('#endorsements_edit_form .info-endoso > div').empty().html(decr);
                $('#endorsements_edit_form .info-endoso > div table').css({"width": "98%","margin": "0 auto"});
                
                fn_endorsement.files.id_endorsement = clave;
                fn_endorsement.files.tipo           = category;
                fn_endorsement.files.init();
                fn_popups.resaltar_ventana('endorsements_edit_form'); 
          });  
        },
        get_data : function(clave,company){
            $('#endorsements_edit_form .p-header h2').empty().text('FILES OF ENDORSEMENT FROM COMPANY ' + company);
            $('#endorsements_edit_form .info-endoso > legend').empty().html('ENDORSEMENT FROM COMPANY ' + company);
            $('#endorsements_edit_form .info-endoso > div').empty().html('');
            $('#endorsements_edit_form .info-endoso > div table').css({"width": "98%","margin": "0 auto"});
            
            fn_endorsement.files.id_endorsement = clave;
            fn_endorsement.files.tipo           = '1';
            fn_endorsement.files.init();
            fn_popups.resaltar_ventana('endorsements_edit_form');      
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
                if($(fn_endorsement.data_grid+" .flt_id").val() != ""){ fn_endorsement.filtro += "A.iConsecutivo|"+$(fn_endorsement.data_grid+" .flt_id").val()+","}
                if($(fn_endorsement.data_grid+" .flt_company").val() != ""){ fn_endorsement.filtro += "E.sNombreCompania|"+$(fn_endorsement.data_grid+" .flt_company").val()+","} 
                //if($(fn_endorsement.data_grid+" .flt_description").val() != ""){ fn_endorsement.filtro += "sNombre|"+$(fn_endorsement.data_grid+" .flt_description").val()+","} 
                //if($(fn_endorsement.data_grid+" .flt_descunit").val() != ""){ fn_endorsement.filtro += "sVIN|"+$(fn_endorsement.data_grid+" .flt_descunit").val()+","}
                if($(fn_endorsement.data_grid+" .flt_type").val() != ""){ fn_endorsement.filtro += "B.sDescripcion|"+$(fn_endorsement.data_grid+" .flt_type").val()+","} 
                fn_endorsement.fillgrid();
       },
        files : {
            id_endorsement : "",
            tipo : "",
            filtro : "",
            pagina_actual : "",
            sort : "ASC",
            orden : "iConsecutivo",
            init : function(){
                fn_endorsement.files.fillgrid();
                new AjaxUpload('#btn_upload_file_driver', {
                    action   : 'funciones_endorsements.php',
                    onSubmit : function(file , ext){
                        if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext) || /^(jpeg)$/i.test(ext) || /^(png)$/i.test(ext) || /^(doc)$/i.test(ext) || /^(docx)$/i.test(ext)))){  
                            var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The file format is not valid.</p>';
                            fn_solotrucking.mensaje(mensaje);
                            return false;
                        }else{
                            var valid = true; 
                             //validar name file
                            if($('#eArchivo_driver').val() != ''){
                                if($('#eArchivo_driver').val() == 'OTHERS' && $('#sNombreArchivo_driver').val() == '' ){
                                   fn_solotrucking.mensaje('Error: Please write the file name.'); 
                                   $('#sNombreArchivo_driver').addClass('error');
                                   valid = false; 
                                }
                                if (valid){
                                    //verificar si es edicion o insert:
                                    //if($('#iConsecutivo_driver').val() == ''){var edit_mode = 'false';}else{var edit_mode = 'true'; }
                                    this.setData({
                                        'accion'             : 'guarda_pdf_endoso',
                                        'eArchivo'           : $('#drivers_files #eArchivo_driver').val(),
                                        'iConsecutivoEndoso' : fn_endorsement.files.id_endorsement,
                                        'iConsecutivo'       : '',
                                        /*'edit_mode'     : edit_mode,
                                        'sNombreArchivo': $('#sNombreArchivo_driver').val(),
                                        'iConsecutivo'  : $('#iConsecutivo_driver').val(),
                                        'Categoria'     : fn_endorsement.files.tipo,*/
                                    });
                                    this.disable();  
                                }
                                
                                
                            }else{
                               fn_solotrucking.mensaje('Error: Please Select a file category.'); 
                               $('#eArchivo_driver').addClass('error');
                            } 
                        }
                    },
                    onComplete : function(file,response){  
                        var respuesta = JSON.parse(response);
                        switch(respuesta.error){
                            case '0':
                                this.enable();
                                fn_solotrucking.mensaje(respuesta.mensaje);
                                $('#add_file_form').hide();
                                fn_endorsement.files.fillgrid();
                            break;
                            case '1':
                               fn_solotrucking.mensaje(respuesta.mensaje); 
                               this.enable();
                            break;
                        }   
                    }        
                });
                //UNIT:
                new AjaxUpload('#btn_upload_file_unit', {
                    action   : 'funciones_endorsements.php',
                    onSubmit : function(file , ext){
                        if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext) || /^(jpeg)$/i.test(ext) || /^(png)$/i.test(ext) || /^(doc)$/i.test(ext) || /^(docx)$/i.test(ext)))){  
                            var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The file format is not valid.</p>';
                            fn_solotrucking.mensaje(mensaje);
                            return false;
                        }else{
                            var valid = true; 
                             //validar name file
                            if($('#eArchivo_unit').val() != ''){
                                if($('#eArchivo_unit').val() == 'OTHERS' && $('#sNombreArchivo_unit').val() == '' ){
                                   fn_solotrucking.mensaje('Error: Please write the file name.'); 
                                   $('#sNombreArchivo_unit').addClass('error');
                                   valid = false; 
                                }
                                if (valid){
                                    //verificar si es edicion o insert:
                                    //if($('#iConsecutivo_unit').val() == ''){var edit_mode = 'false';}else{var edit_mode = 'true'; }
                                    this.setData({
                                        'accion'             : 'guarda_pdf_endoso',
                                        'eArchivo'           : $('#units_files #eArchivo_unit').val(),
                                        'iConsecutivoEndoso' : fn_endorsement.files.id_endorsement,
                                        'iConsecutivo'       : '',
                                        /*'edit_mode'     : edit_mode,
                                        'sNombreArchivo':$('#sNombreArchivo_unit').val(),
                                        'iConsecutivo':$('#iConsecutivo_unit').val(),
                                        'Categoria' : fn_endorsement.files.tipo, */
                                    });
                                    this.disable();  
                                }
                                
                                
                            }else{
                               fn_solotrucking.mensaje('Error: Please Select a file category.'); 
                               $('#eArchivo_driver').addClass('error');
                            } 
                        }
                    },
                    onComplete : function(file,response){  
                        var respuesta = JSON.parse(response);
                        switch(respuesta.error){
                            case '0':
                                this.enable();
                                fn_solotrucking.mensaje(respuesta.mensaje);
                                $('#add_file_form').hide();
                                fn_endorsement.files.fillgrid();
                            break;
                            case '1':
                               fn_solotrucking.mensaje(respuesta.mensaje); 
                               this.enable();
                            break;
                        }   
                    }        
                });
            },
            fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_endorsement_files.php", 
                data:{
                    accion:"get_endorsement_files",
                    iConsecutivo :  fn_endorsement.files.id_endorsement,
                    tipo : fn_endorsement.files.tipo,
                    registros_por_pagina : "15", 
                    pagina_actual : fn_endorsement.files.pagina_actual, 
                    filtroInformacion : fn_endorsement.files.filtro,  
                    ordenInformacion : fn_endorsement.files.orden,
                    sortInformacion : fn_endorsement.files.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $("#data_files_grid tbody").empty().append(data.tabla);
                    $("#data_files_grid tbody tr:even").addClass('gray');
                    $("#data_files_grid tbody tr:odd").addClass('white');
                    $("#data_files_grid tfoot .paginas_total").val(data.total);
                    $("#data_files_grid tfoot .pagina_actual").val(data.pagina);
                    fn_endorsement.files.pagina_actual = data.pagina;
                    fn_endorsement.files.delete_confirm();
                    
                }
               }); 
            },
            filtraInformacion : function(){
                fn_endorsement.files.pagina_actual = 0;
                fn_endorsement.files.filtro = "";
                if($("#data_files_grid .flt_filename").val() != ""){ fn_endorsement.files.filtro += "sNombreArchivo|"+$("#data_files_grid .flt_filename").val()+","}
                if($("#data_files_grid .flt_filecategory").val() != ""){ fn_endorsement.files.filtro += "eArchivo|"+$("#data_files_grid .flt_filecategory").val()+","} 
                fn_endorsement.files.fillgrid();
            },
            firstPage : function(){
                if($("#data_files_grid .pagina_actual").val() != "1"){
                    fn_endorsement.files.pagina_actual = "";
                    fn_endorsement.files.fillgrid();
                }
            },
            previousPage : function(){
                if($("#data_files_grid .pagina_actual").val() != "1"){
                    fn_endorsement.files.pagina_actual = (parseInt($("#data_files_grid .pagina_actual").val()) - 1) + "";
                    fn_endorsement.files.fillgrid();
                }
            },
            nextPage : function(){
                if($("#data_files_grid .pagina_actual").val() != $("#data_files_grid .paginas_total").val()){
                    fn_endorsement.files.pagina_actual = (parseInt($("#data_files_grid .pagina_actual").val()) + 1) + "";
                    fn_endorsement.files.fillgrid();
                }
            },
            lastPage : function(){
                if($("#data_files_grid .pagina_actual").val() != $("#data_files_grid .paginas_total").val()){
                    fn_endorsement.files.pagina_actual = $("#data_files_grid .paginas_total").val();
                    fn_endorsement.files.fillgrid();
                }
            },
            add : function(){
                  $('#add_file_form .p-header h2').empty().text('ENDORSEMENTS FILES - ADD NEW FILE TO ENDORSEMENT ' + fn_endorsement.files.id_endorsement);
                  $('#drivers_files, #units_files ').hide(); 
                  if(fn_endorsement.files.tipo == '2'){
                      $('#add_file_form #drivers_files').show();
                      $('#drivers_files input, #drivers_files select').val('');
                      fn_endorsement.files.validate_file($('#eArchivo_driver').val());
                  }else if(fn_endorsement.files.tipo == '1'){
                      $('#add_file_form #units_files').show();
                      $('#units_files input, #units_files select').val('');
                      fn_endorsement.files.validate_file($('#eArchivo_unit').val());
                  } 
                  $('#add_file_form').show();
            },
            validate_file : function(filetype){
                if(filetype =='OTHERS'){
                   if(fn_endorsement.files.tipo == '2'){  
                        $('#drivers_files .file_name_field').show(); 
                   }else if(fn_endorsement.files.tipo == '1'){$('#units_files .file_name_field').show();  }
                }else if (filetype != ''){
                    fn_solotrucking.mensaje("If the currently selected file already exists it will be replaced by that you upload to the system.");
                }
            },
            delete_confirm : function(){
              $("#data_files_grid tbody .btn_delete_file").bind("click",function(){
                   var clave = $(this).parent().parent().find("td:eq(0)").attr('id');
                   $('#dialog_delete_file #id_file').val(clave);
                   $('#dialog_delete_file').dialog( 'open' );
                   return false;
               });  
            },
            delete_file : function(id){
              $.post("funciones_endorsements.php",{accion:"elimina_archivo_endoso", 'iConsecutivo': id },
               function(data){
                    fn_solotrucking.mensaje(data.msj);
                    fn_endorsement.files.fillgrid();
               },"json");  
            },
            /*edit : function (){
            $("#data_files_grid tbody td .btn_edit_file").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").attr('id');
                $('#drivers_files, #units_files ').hide();
                $('#endorsements_edit_form .p-header h2').empty().text('Endorsement request from ' + company + ': E#' + clave);
                $.post("funciones_endorsement_request.php",
                {
                    accion:"get_file", 
                    clave: clave,
                    idPoliza : idPoliza, 
                    domroot : "endorsements_edit_form"
                },
                function(data){
                    if(data.error == '0'){
                        
                    }else{
                        
                    }
                }); 
                if(fn_endorsement.files.tipo == '2'){
                      $('#add_file_form #drivers_files').show();
                      $('#drivers_files input, #drivers_files select').val('');
                      fn_endorsement.files.validate_file($('#eArchivo_driver').val());
                }else if(fn_endorsement.files.tipo == '1'){
                      $('#add_file_form #units_files').show();
                      $('#units_files input, #units_files select').val('');
                      fn_endorsement.files.validate_file($('#eArchivo_unit').val());
                } 
                $('#add_file_form').show();
          });  
        }, */    
       }          
}    

 
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_endorsement" class="container">
        <div class="page-title">
            <h1>ENDORSEMENTS</h1>
            <h2>UPLOAD ENDORSEMENT FILES </h2>
        </div>
        <table id="data_grid_endorsement" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'>
                    <input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_company" type="text" placeholder="Company:"></td>
                <td style="width:330px">
                    <!--<input class="flt_description" type="text" placeholder="Driver Name:" style="width:46%;float:left;">
                    <input class="flt_descunit" type="text" placeholder="VIN#:" style="float:right;width: 46%;">-->
                </td>  
                <td><input class="flt_type" type="text" placeholder="Type:"></td> 
                <td style='width:160px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_endorsement.filtraInformacion();"><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('E.sNombreCompania',this.cellIndex);">COMPANY</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('',this.cellIndex);">Description</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('categoria',this.cellIndex);">TYPE</td>
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
<!---- FORMULARIOS ------>
<div id="endorsements_edit_form" class="popup-form">
    <div class="p-header">
        <h2>ENDORSEMENTS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('endorsements_edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
    <fieldset class="info-endoso">
    <legend></legend>
        <div></div>
        <br>
        <h3 class="popup-gridtit">FILES OF ENDORSEMENT</h3>
        <table id="data_files_grid" class="popup-datagrid">
            <thead>
                <tr id="grid-head1">
                        <td><input class="flt_filename" type="text" placeholder="Name:"></td>  
                        <td><input class="flt_filecategory" type="text" placeholder="Category:"></td> 
                        <td style='width:120px;'>
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_endorsement.files.filtraInformacion();"><i class="fa fa-search"></i></div>
                            <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_endorsement.files.add();"><i class="fa fa-plus"></i></div>
                        </td> 
                </tr>
                <tr id="grid-head2">
                    <td class="etiqueta_grid">FILE NAME</td>
                    <td class="etiqueta_grid">Category</td>
                    <td class="etiqueta_grid"></td> 
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <td colspan="100%">
                        <div class="datagrid-pages">
                            <input class="pagina_actual" type="text" readonly="readonly" size="3">
                            <label> / </label>
                            <input class="paginas_total" type="text" readonly="readonly" size="3">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="100%">
                        <div class="datagrid-menu-pages">
                            <button class="pgn-inicio"    onclick="fn_endorsement.files.firstPage();" title="First page"><span></span></button>
                            <button class="pgn-anterior"  onclick="fn_endorsement.files.previousPage();" title="Previous"><span></span></button>
                            <button class="pgn-siguiente" onclick="fn_endorsement.files.nextPage();" title="Next"><span></span></button>
                            <button class="pgn-final"     onclick="fn_endorsement.files.lastPage();" title="Last Page"><span></span></button>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
        <br>
    </fieldset>
    </div>
    </div>
</div>
<div id="add_file_form" class="popup-form">
    <div class="p-header">
        <h2>ENDORSEMENTS</h2>
        <div class="btn-close" title="Close Window" onclick="$('#add_file_form').hide();fn_endorsement.files.fillgrid();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
    <form>  
    <fieldset id="drivers_files" style="display:none;">
        <div class="field_item">
            <input id="iConsecutivo_driver"  name="iConsecutivo"  type="hidden">
            <label>Category <span style="color:#ff0000;">*</span>:</label>  
            <select tabindex="1" id="eArchivo_driver"  name="eArchivo_driver" onblur="//fn_endorsement.files.validate_file(this.value);">
                <option value="">Select an option...</option>
                <option value="ENDORSEMENT">Endorsement</option> 
                <option value="LICENSE">License</option>
                <option value="LONGTERM">Long Term</option> 
                <option value="PSP">PSP</option> 
                <option value="MVR">MVR</option> 
                <option value="OTHERS">Others</option> 
            </select>
        </div>
        <div class="field_item file_name_field" style="display:none;">
            <label>File Name <span style="color:#ff0000;">*</span>:</label> 
            <input class="txt-lowcase" tabindex="2" id="sNombreArchivo_driver" name="sNombreArchivo_driver" type="text" placeholder="Example: file_name" maxlength="15">
        </div>
        <div class="field_item">
            <label>NOTE: <span style="color:#9e2e2e;">Please upload the files in PDF or JPG format.</span></label>     
        </div>
        <button id="btn_upload_file_driver" class="btn-1" type="button" style="width: 200px;">Upload file and save</button>
        <button type="button" class="btn-1" onclick="$('#add_file_form').hide();fn_endorsement.files.fillgrid();" style="margin-right:10px;background:#e8051b;">CLOSE</button>   
    </fieldset>
    <fieldset id="units_files" style="display:none;">
        <div class="field_item">
            <input id="iConsecutivo_unit"  name="iConsecutivo_unit"  type="hidden">
            <label>Category <span style="color:#ff0000;">*</span>:</label>  
            <select tabindex="1" id="eArchivo_unit"  name="eArchivo_unit" onblur="//fn_endorsement.files.validate_file(this.value);">
                <option value="">Select an option...</option>
                <option value="ENDORSEMENT">Endorsement</option>
                <option value="TITLE">Title</option>
                <option value="PTL">Proof of Total Loss</option> 
                <option value="DA">Delease Agreement</option> 
                <option value="NOR">NON-OP Registration</option> 
                <option value="BS">Bill of Sale</option>   
                <option value="OTHERS">Others</option> 
            </select>
        </div>
        <div class="field_item file_name_field" style="display:none;">
            <label>File Name <span style="color:#ff0000;">*</span>:</label> 
            <input class="txt-lowcase" tabindex="2" id="sNombreArchivo_unit" name="sNombreArchivo_unit" type="text" placeholder="Example: file_name" maxlength="15">
        </div>
        <div class="field_item">
            <label>NOTE: <span style="color:#9e2e2e;">Please upload the files in PDF or JPG format.</span></label>     
        </div>
        <button id="btn_upload_file_unit" class="btn-1" type="button" style="width: 200px;">Upload file and save</button>
            <button type="button" class="btn-1" onclick="$('#add_file_form').hide();fn_endorsement.files.fillgrid();" style="margin-right:10px;background:#e8051b;">CLOSE</button>   
    </fieldset>
    </form>  
    </div>
</div>
<!--- DIALOGUES --->
<div id="dialog_delete_file" title="SYSTEM ALERT" style="display:none;">
    <p>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
    <form><input type="hidden" name="id_file" id="id_file"></form>  
</div>  
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>