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
            
            $('#dialog_upload_files').dialog({
                modal: true,
                autoOpen: false,
                width : 500,
                height : 350,
                resizable : false,
                buttons : {
                    'SAVE DATA' : function() {
                        fn_endorsement.files.save();
                    },
                     'CANCEL' : function(){
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
            orden : "A.dFechaAplicacion",
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
                //Cargar Lista de companias:
                $.ajax({             
                    type:"POST", 
                    url:"catalogos_generales.php", 
                    data:{accion:"get_companies"},
                    async : true,
                    dataType : "json",
                    success : function(data){
                        $("#frm_endorsement_information select[name=iConsecutivoCompania").empty().append(data.select);
                    }
                }); 
                //Cargar Modelos para unidades:
                $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsements.php", 
                    data:{accion:"get_unit_models"},
                    async : false,
                    dataType : "json",
                    success : function(data){                               
                        $("#frm_unit_information #iModelo").empty().append(data.select);
                         
                    }
                });
                //Cargar Radio para unidades:
                $.ajax({             
                    type:"POST", 
                    url:"catalogos_generales.php", 
                    data:{accion:"get_unit_radio"},
                    async : false,
                    dataType : "json",
                    success : function(data){                               
                        $("#frm_unit_information #iConsecutivoRadio").empty().append(data.select);
                         
                    }
                });
                //Cargar Años:
                $.ajax({             
                    type:"POST", 
                    url:"catalogos_generales.php", 
                    data:{accion:"get_years"},
                    async : false,
                    dataType : "json",
                    success : function(data){                               
                        $("#frm_unit_information #iYear").empty().append(data.select);
                         
                    }
                });
                $("#sUnitTrailer").autocomplete();
            },
            fillgrid: function(){
                   $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsement_request_units.php", 
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
            add : function(){
               $('#endorsements_edit_form input, #endorsements_edit_form select, #endorsements_edit_form textarea').val('');
               $("#frm_endorsement_information .required-field").removeClass("error");
               $("#files_datagrid").hide();//ocultar grid de archivos...
               fn_popups.resaltar_ventana('endorsements_edit_form'); 
            },
            edit : function (){
                $(fn_endorsement.data_grid + " tbody td .edit").bind("click",function(){
                    var clave    = $(this).parent().parent().find("td:eq(0)").html();
                    var idPoliza = $(this).parent().parent().find("td:eq(5)").attr('id')
                    var company  = $(this).parent().parent().find("td:eq(1)").text(); 
                    $('#endorsements_edit_form .p-header h2').empty().text('Endorsement request from ' + company + ': E#' + clave);
                    $.post("funciones_endorsement_request_units.php",
                    {
                        accion:"get_endorsement", 
                        clave: clave,
                        idPoliza : idPoliza,  
                        domroot : "endorsements_edit_form"
                    },
                    function(data){
                        if(data.error == '0'){
                           $("#files_datagrid").show();//mostrar grid de archivos... 
                           $('#endorsements_edit_form input, #endorsements_edit_form select, #endorsements_edit_form textarea').val('');
                           $("#frm_endorsement_information .required-field").removeClass("error");
                           $("#endorsements_edit_form #info_policies table tbody").empty().append(data.policies);   
                           eval(data.fields); 
                           fn_endorsement.get_unidades();
                           fn_endorsement.get_policies();
                           eval(data.policies);
                           $('#endorsements_edit_form #sComentarios').val(data.sComentarios);
                           if(data.files != ''){$('#endorsements_edit_form .data_files').empty().append(data.files);}  
                           $('#frm_unit_information').show();
                           
                           fn_endorsement.valid_action();
                           fn_endorsement.files.iConsecutivoEndoso = $('#endorsements_edit_form input[name=iConsecutivo]').val();
                           fn_endorsement.files.fillgrid();
                           
                           //CAMPOS SOLO INHABILITADOS:
                           $("#endorsements_edit_form #iConsecutivoCompania").addClass('readonly').prop('disabled','disabled');
                           $("#endorsements_edit_form #eAccion").addClass('readonly').prop('disabled','disabled');
                           
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
                   $.post("funciones_endorsement_request_units.php",{accion:"send_email_brokers", clave: clave, idPoliza : idPoliza},
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
                    if($(fn_endorsement.data_grid+" .flt_id").val() != ""){ fn_endorsement.filtro += "A.iConsecutivo|"+$(fn_endorsement.data_grid+" .flt_id").val()+","}
                    if($(fn_endorsement.data_grid+" .flt_company").val() != ""){ fn_endorsement.filtro += "D.sNombreCompania|"+$(fn_endorsement.data_grid+" .flt_company").val()+","} 
                    if($(fn_endorsement.data_grid+" .flt_description").val() != ""){ fn_endorsement.filtro += "sVIN|"+$(fn_endorsement.data_grid+" .flt_description").val()+","} 
                    if($(fn_endorsement.data_grid+" .flt_action").val() != ""){ fn_endorsement.filtro += "eAccion|"+$(fn_endorsement.data_grid+" .flt_action").val()+","}
                    if($(fn_endorsement.data_grid+" .flt_date").val() != ""){ fn_endorsement.filtro += "A.dFechaAplicacion|"+$(fn_endorsement.data_grid+" .flt_date").val()+","} 
                    //if($(fn_endorsement.data_grid+" .flt_policy").val() != ""){ fn_endorsement.filtro += "sNumeroPoliza|"+$(fn_endorsement.data_grid+" .flt_policy").val()+","}
                    //if($(fn_endorsement.data_grid+" .flt_broker").val() != ""){ fn_endorsement.filtro += "BR.sName|"+$(fn_endorsement.data_grid+" .flt_broker").val()+","} 
                    if($(fn_endorsement.data_grid+" .flt_status").val() != ""){ fn_endorsement.filtro += "A.eStatus|"+$(fn_endorsement.data_grid+" .flt_status").val()+","}
                    fn_endorsement.fillgrid();
           },
            send_confirm : function(){
               if( $('#frm_endorsement_status #eStatus').val() != 'SB'){
                    $('#dialog_endorsement_save').dialog( 'open' );   
               }
            }, 
            save : function(){
               
               var valid = true;
               var msj   = "";
               $("#frm_endorsement_information .required-field").removeClass("error");
               
               //Revisamos polizas seleccionadas:
               var polizas_list = "";
               $("#info_policies tbody input:checkbox").each(function(){
                   if($(this).prop('checked')){
                      if(polizas_list == ""){polizas_list = $(this).val();}else{polizas_list+='|'+$(this).val();} 
                   }
               });
               if(polizas_list == ""){valid = false; msj = "<li>You must select at least one policy.</li>";}
               
               //Revisamos campos obligatorios:
               $("#frm_endorsement_information .required-field").each(function(){
                   if($(this).val() == ""){valid = false; $(this).addClass('error');msj = "<li>You must capture the required fields.</li>";}
               });
               
               if(valid){ 
                  /*msg = "<p style=\"text-align:center;\">Updating endorsement data and send an e-mail to company, please wait...<br><img src=\"images/ajax-loader.gif\" alt=\"ajax-loader.gif\" style=\"margin-top:10px;\"><br></p>";
                  $('#Wait').empty().append(msg).dialog('open'); */
                  if($('#endorsements_edit_form #iConsecutivo').val() != ""){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}
                  struct_data_post.action  = "save_endorsement";
                  struct_data_post.domroot = "#frm_endorsement_information";
                  $.ajax({             
                    type  : "POST", 
                    url   : "funciones_endorsement_request_units.php", 
                    data  : struct_data_post.parse(),
                    async : true,
                    dataType : "json",
                    success  : function(data){                               
                        switch(data.error){ 
                         case '0':
                            fn_solotrucking.mensaje(data.msj);
                            fn_endorsement.fillgrid();
                            fn_popups.cerrar_ventana('endorsements_edit_form');
                         break;
                         case '1': fn_solotrucking.mensaje(data.msj); break;
                        }
                    }
                  }); 
               }
               else{fn_solotrucking.mensaje('<p>Please check the following::</p><ul>'+msj+'</ul>');} 
            },
            files : {
               pagina_actual : "",
               sort : "ASC",
               orden : "iConsecutivo",
               iConsecutivoEndoso : "",
               fillgrid: function(){
                    $.ajax({             
                        type:"POST", 
                        url:"funciones_endorsement_request_units.php", 
                        data:{
                            accion               : "get_files",
                            iConsecutivo         : fn_endorsement.files.iConsecutivoEndoso,
                            registros_por_pagina : "10", 
                            pagina_actual        : fn_endorsement.files.pagina_actual,   
                            ordenInformacion     : fn_endorsement.files.orden,
                            sortInformacion      : fn_endorsement.files.sort,
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $("#files_datagrid tbody").empty().append(data.tabla);
                            $("#files_datagrid tbody tr:even").addClass('gray');
                            $("#files_datagrid tbody tr:odd").addClass('white');
                            $("#files_datagrid tfoot .paginas_total").val(data.total);
                            $("#files_datagrid tfoot .pagina_actual").val(data.pagina);
                            fn_endorsement.files.pagina_actual = data.pagina;
                            fn_endorsement.files.delete_file();
                            
                            //Archivos:
                            if(window.File && window.FileList && window.FileReader) {
                                  fn_solotrucking.files.form      = "#dialog_upload_files";
                                  fn_solotrucking.files.fileinput = "fileselect";
                                  fn_solotrucking.files.add();
                            }
                        }
                    }); 
               },
               delete_file : function(){
                  $("#files_datagrid tbody td .trash").bind("click",function(){
                        var clave = $(this).parent().parent().find("td:eq(0)").attr('id');
                            clave = clave.split("idFile_");
                            clave = clave[1];
                        $.post("funciones_endorsements.php",{accion:"elimina_archivo_endoso", "iConsecutivo": clave},
                        function(data){
                            fn_solotrucking.mensaje(data.msj);
                            if(data.error == '0'){fn_endorsement.files.fillgrid();}      
                  },"json");
                  });  
                },
               firstPage : function(){
                    if($("#files_datagrid .pagina_actual").val() != "1"){
                        fn_endorsement.files.pagina_actual = "";
                        fn_endorsement.files.fillgrid();
                    }
                },
               previousPage : function(){
                    if($("#files_datagrid .pagina_actual").val() != "1"){
                        fn_endorsement.files.pagina_actual = (parseInt($("#files_datagrid .pagina_actual").val()) - 1) + "";
                        fn_endorsement.files.fillgrid();
                    }
                },
               nextPage : function(){
                    if($("#files_datagrid .pagina_actual").val() != $("#files_datagrid .paginas_total").val()){
                        fn_endorsement.files.pagina_actual = (parseInt($("#files_datagrid .pagina_actual").val()) + 1) + "";
                        fn_endorsement.files.fillgrid();
                    }
                },
               lastPage : function(){
                    if($("#files_datagrid .pagina_actual").val() != $("#files_datagrid .paginas_total").val()){
                        fn_endorsement.files.pagina_actual = $("#files_datagrid .paginas_total").val();
                        fn_endorsement.files.fillgrid();
                    }
                }, 
               ordenamiento : function(campo,objeto){
                    $("#files_datagrid #grid-head2 td").removeClass('down').removeClass('up');
                    if(campo == fn_endorsement.files.orden){
                        if(fn_endorsement.files.sort == "ASC"){
                            fn_endorsement.files.sort = "DESC";
                            $("#files_datagrid #grid-head2 td:eq("+objeto+")").addClass('up');
                        }else{
                            fn_endorsement.files.sort = "ASC";
                            $("#files_datagrid #grid-head2 td:eq("+objeto+")").addClass('down');
                        }
                    }else{
                        fn_endorsement.files.sort = "ASC";
                        fn_endorsement.files.orden = campo;
                        $("#files_datagrid #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                    fn_endorsement.files.fillgrid();
                    return false;
                }, 
               add : function(){
                   $("#dialog_upload_files input[name=iConsecutivoEndoso]").val(fn_endorsement.files.iConsecutivoEndoso);
                   $("#dialog_upload_files input[name=MAX_FILE_SIZE]").val(3000000);
                   $("#dialog_upload_files .file-message").html("");
                   $("#dialog_upload_files #fileselect").removeClass("fileupload");
                   $('#dialog_upload_files').dialog("open"); 
               },
               save : function(){
                   var valid   = true;
                   var mensaje = "";
                      
                  //Validar campo para archivo:
                   if($("#dialog_upload_files #fileselect").val() == ""){
                      mensaje += '<li>No file has been loaded.</li>';
                      valid    = false;
                      $("#dialog_upload_files #fileselect").addClass('error'); 
                   }
                      
                  if(valid){
                      var form       = "#dialog_upload_files form";
                      var dataForm   = new FormData();
                      var other_data = $(form).serializeArray();
                      dataForm.append('accion','guarda_pdf_endoso');
                      $.each($(form+' input[type=file]')[0].files,function(i, file){dataForm.append('file-'+i, file);});
                      $.each(other_data,function(key,input){dataForm.append(input.name,input.value);});
                          
                      $.ajax({
                          type: "POST",
                          url : "funciones_endorsements.php",
                          data: dataForm,
                          cache: false,
                          contentType: false,
                          processData: false,
                          type: 'POST',
                          dataType : "json",
                          success : function(data){ 
                              fn_solotrucking.mensaje(data.mensaje);
                              if(data.error == "0"){
                                  $("#dialog_upload_files").dialog('close');
                                  fn_endorsement.files.fillgrid();
                              }
                          }
                      });        
                  }else{
                      fn_solotrucking.mensaje('<p>Favor de revisar lo siguiente:</p><ul>'+mensaje+'</ul>'); 
                  } 
               }
            },
            get_company_data : function(){
              fn_endorsement.get_unidades();
              fn_endorsement.get_policies(); 
              fn_endorsement.valid_action(); 
            },
            get_unidades : function(){
                
                if($("#frm_endorsement_information #iConsecutivoCompania").val() != ""){
                    //Autocomplete de unidades:
                    $.ajax({
                        type    : "POST",
                        url     : "funciones_endorsement_request_units.php",
                        data    : {'accion' : 'get_units_autocomplete','iConsecutivoCompania':$("#frm_endorsement_information #iConsecutivoCompania").val()},
                        async   : true,
                        dataType: "text",
                        success : function(data) {
                           var datos = eval(data); 
                           $("#sUnitTrailer").autocomplete({source:datos});
                        }
                    }); 
                }
                
            },
            set_unidades : function(){
                var data = $("#sUnitTrailer").val();
                var pipe = data.indexOf("|");
                if(pipe > 0){
                    var data = data.split("|");
                    $("#frm_unit_information #sUnitTrailer").val(data[0].trim());//VIN
                    $("#frm_unit_information #sTipo").val(data[1].trim());//Tipo
                    $("#frm_unit_information #iYear").val(data[2].trim());//Year
                    $("#frm_unit_information #iConsecutivoRadio").val(data[3].trim());//Radio
                    $("#frm_unit_information #iModelo").val(data[4].trim());//iModelo
                    $("#frm_unit_information #iConsecutivoUnidad").val(data[5].trim());//idUnidad
                }
            },
            get_policies : function(){
              if($("#frm_endorsement_information #iConsecutivoCompania").val() != ""){  
                  $.ajax({             
                        type:"POST", 
                        url:"funciones_endorsement_request_units.php", 
                        data:{accion:"get_policies",'iConsecutivoCompania':$("#frm_endorsement_information #iConsecutivoCompania").val()},
                        async : false,
                        dataType : "json",
                        success : function(data){                               
                            if(data.error == '0'){
                                if(data.pd_data == 'true'){
                                    $("#endorsements_edit_form .pd_information").show();
                                }
                                else{
                                    $("#endorsements_edit_form .pd_information").hide();
                                    $("#endorsements_edit_form :input[name=iPDAmount]").prop('readonly','readonly').addClass('readonly');
                                }
                                $("#info_policies table tbody").empty().append(data.policies_information);
                            }
                        }
                  });
              }   
            }, 
            valid_action : function(){
                var action = $("#frm_endorsement_information #eAccion").val();
                $('#endorsements_edit_form .delete_field, #endorsements_edit_form .add_field, #endorsements_edit_form .pd_information').hide();
                if(action == 'D'){
                    $('#endorsements_edit_form .delete_field').show();
                }else if(action == 'A'){
                    $('#endorsements_edit_form .add_field, #endorsements_edit_form .pd_information').show();
                }
            },   
    }    
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_endorsement" class="container">
        <div class="page-title">
            <h1>REQUESTS</h1>
            <h2>ENDORSEMENTS / UNIT AND TRAILERS </h2>
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
                    <div class="btn-icon-2 btn-left" title="New Endorsement +"  onclick="fn_endorsement.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('B.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('D.sNombreCompania',this.cellIndex);">COMPANY</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('sVIN',this.cellIndex);">Description</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('eAccion',this.cellIndex);">ACTION</td>
                <td class="etiqueta_grid up"   onclick="fn_endorsement.ordenamiento('B.dFechaAplicacion',this.cellIndex);">APPLICATION DATE</td> 
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
        <br>
        <form>
            <fieldset id="frm_endorsement_information">
                <legend>GENERAL DATA</legend>
                <table style="width:100%" cellpadding="0" cellspacing="0">
                    <tr>
                    <td colspan="100%">
                        <p class="mensaje_valido"><span style="color:#ff0000;font-size:1em;">&nbsp;<b>*</b>Please check before sending to brokers, that all data in this endorsement are correct.</span></p>
                        <input id="iConsecutivo" name="iConsecutivo" type="hidden" value=""> 
                    </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div id="info_policies">
                            <table class="popup-datagrid" style="margin-bottom: 10px;width: 100%;" cellpadding="0" cellspacing="0">
                                <thead>
                                    <tr id="grid-head2"><td class="etiqueta_grid">Policy Number</td><td class="etiqueta_grid">Broker</td><td class="etiqueta_grid">Type</td></tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="field_item">
                                <label>Company <span style="color:#ff0000;">*</span>:</label>  
                                <select id="iConsecutivoCompania" onchange="fn_endorsement.get_company_data();"  name="iConsecutivoCompania" class="required-field" style="height: 25px!important;width: 99%!important;"><option value="">Select an option...</option></select>
                            </div>
                        </td>
                        <td>
                            <div class="field_item">
                                <label>Action <span style="color:#ff0000;">*</span>:</label> 
                                <Select id="eAccion" name"eAccion" class="required-field" onblur="fn_endorsement.valid_action();" style="height: 25px!important;">
                                    <option value="">Select an option...</option> 
                                    <option value="A">ADD</option>
                                    <option value="D">DELETE</option>
                                </select>
                            </div> 
                        </td>
                    </tr>
                </table>
                <legend>Unit Information</legend>
                <table id="frm_unit_information" style="width:100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                        <div class="field_item">
                            <input id="iConsecutivoUnidad" type="hidden" value="">
                            <label>Type <span style="color:#ff0000;">*</span>: </label>
                            <Select id="sTipo" style="width:99%!important;height: 25px!important;" class="required-field">
                                <option value="">Select an option...</option>
                                <option value="UNIT">Unit</option>
                                <option value="TRAILER">Trailer</option>
                                <option value="TRACTOR">Tractor</option>
                            </select>
                        </div>
                        </td>
                        <td>
                        <div class="field_item">
                            <label>Year <span style="color:#ff0000;">*</span>: </label>
                            <Select id="iYear" style="width:99%!important;height: 25px!important;" class="required-field"><option value="">Select an option...</option></select> 
                        </div>
                        </td>
                        <td>
                        <div class="field_item">
                            <label>Make: </label>
                            <Select id="iModelo" style="width:99%!important;height: 25px!important;"><option value="">Select an option...</option></select>
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="field_item">
                                <label>VIN <span style="color:#ff0000;">*</span>:</label> 
                                <input id="sUnitTrailer" class="txt-uppercase required-field" type="text" placeholder="Write the VIN or system id of the Unit or Trailer" style="width: 97%;" onblur="fn_endorsement.set_unidades();">
                            </div>
                        </td>
                        <td>
                        <div class="field_item">
                            <label>Radius <span style="color:#ff0000;" class="add_field">*</span>: </label>
                            <Select id="iConsecutivoRadio" style="width:99%!important;height: 25px!important;"><option value="">Select an option...</option></select>
                        </div>
                        </td>
                        <!-- only if the company has a PD Policy -->
                        <td>
                        <div class="field_item pd_information">
                            <label>PD Amount $ <span style="color:#ff0000;">*</span>:</label>
                            <input id="iPDAmount" name="iPDAmount" type="text" class="decimals readonly" readonly="readonly"> 
                        </div>
                        </td>
                    </tr>
                    <tr>
                    <td colspan="100%">
                    <div class="field_item">
                        <label>General Comments:</label> 
                        <textarea id="sComentarios" name ="sComentarios" style="resize:none;height:50px;"></textarea>
                    </div>
                    </td>
                </tr>
                </table>
            </fieldset>
            <table style="width: 100%;" cellpadding="0" cellspacing="0">
                <tr>
                    <td colspan="2">
                    <table id="files_datagrid" class="popup-datagrid" style="width: 100%;margin-top: 10px;margin-bottom: 10px;" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr id="grid-head2">
                                <td class="etiqueta_grid">File Name</td>
                                <td class="etiqueta_grid">Type</td>
                                <td class="etiqueta_grid">Size</td>
                                <td class="etiqueta_grid" style="width: 100px;text-align: center;">
                                    <div class="btn-icon edit btn-left" title="Upload files" onclick="fn_endorsement.files.add();" style="width: auto!important;"><i class="fa fa-upload"></i><span style="    padding-left: 5px;font-size: 0.8em;text-transform: uppercase;">upload</span></div>
                                </td>
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No uploaded files.</td></tr></tbody>
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
                                        <button class="pgn-inicio"    onclick="fn_endorsement.files.firstPage();" title="First page"><span></span></button>
                                        <button class="pgn-anterior"  onclick="fn_endorsement.files.previousPage();" title="Previous"><span></span></button>
                                        <button class="pgn-siguiente" onclick="fn_endorsement.files.nextPage();" title="Next"><span></span></button>
                                        <button class="pgn-final"     onclick="fn_endorsement.files.lastPage();" title="Last Page"><span></span></button>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    </td>
                </tr>
            </table> 
            <button type="button" class="btn-1" onclick="fn_endorsement.save();">SAVE</button>
            <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('endorsements_edit_form');" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
        </form> 
    </div>
    </div>
</div>
<div id="dialog_upload_files" title="Upload files to endorsement" style="display:none;padding: .5em 1em .5em 0em!important;">
    <form class="frm_upload_files" action="" method="POST" enctype="multipart/form-data">
        <fieldset>
        <input name="iConsecutivoEndoso" type="hidden" value="">
        <div>
            <label>File Category <span style="color:#ff0000;">*</span>: </label>
            <Select name="eArchivo" style="height: 27px!important;">
                <option value="OTHERS">Other</option>
                <option value="DA">Delease Agreement</option>   
                <option value="BS">Bill of Sale</option>   
                <option value="NOR">Non-Op Registration</option>   
                <option value="PTL">Proof of Total Loss</option>   
            </select> 
        </div>
        <div class="field_item"> 
            <label class="required-field">Archivo para subir:</label>
            <div class="file-container">
                <!-- MAX_FILE_SIZE debe preceder al campo de entrada del fichero -->
                <input type="hidden"   name="MAX_FILE_SIZE" value="" />
                <input id="fileselect" name="fileselect" type="file"/>
                <div class="file-message"></div>
            </div>
        </div> 
        </fieldset>
    </form>   
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