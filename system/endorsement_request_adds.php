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
            $('#dialog_send_email').dialog({
                modal: true,
                autoOpen: false,
                width : 420,
                height : 200,
                resizable : false,
                buttons : {
                    'YES' : function() {
                        $(this).dialog('close');
                        fn_endorsement.email.send();             
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
            orden : "A.dFechaAplicacion",
            init : function(){
                
                $('.num').keydown(fn_solotrucking.inputnumero); 
                $('.decimals').keydown(fn_solotrucking.inputdecimals);
                $('.hora').mask('00:00');
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
                
                //llenando select de estados:     
                $.post("catalogos_generales.php", { "accion": "get_country", "country": "USA"},
                function(data){ $("#frm_endorsement_information select[name=sEstado]").empty().append(data.tabla).removeAttr('disabled').removeClass('readonly');},"json");
            
                //$("#frm_driver_information #sNombre").autocomplete();
            },
            fillgrid: function(){
                   $.ajax({             
                    type:"POST", 
                    url:"endorsement_request_adds_server.php", 
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
                        fn_endorsement.edit_estatus();
                        fn_endorsement.change_estatus();
                    }
                }); 
            },
            add : function(){
               $('#endorsements_edit_form input, #endorsements_edit_form select, #endorsements_edit_form textarea').val('');
               $("#frm_endorsement_information .required-field").removeClass("error");
               $("#info_policies").hide();//ocultar grid de archivos...
               $("#info_policies tbody").empty();
               fn_solotrucking.get_date("#dFechaAplicacion.fecha");
               //fn_solotrucking.get_time("#dFechaAplicacionHora");
               fn_popups.resaltar_ventana('endorsements_edit_form'); 
            },
            edit : function (){
                $(fn_endorsement.data_grid + " tbody td .btn_edit").bind("click",function(){
                    var clave    = $(this).parent().parent().find("td:eq(0)").html();
                    var company  = $(this).parent().parent().find("td:eq(1)").text(); 
                    $('#endorsements_edit_form .p-header h2').empty().text('Endorsement request from ' + company + ': E#' + clave);
                    fn_endorsement.detalle.add();
                    fn_endorsement.get_data(clave); 
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
                    //if($(fn_endorsement.data_grid+" .flt_description").val() != ""){ fn_endorsement.filtro += "sNombre|"+$(fn_endorsement.data_grid+" .flt_description").val()+","} 
                    //f($(fn_endorsement.data_grid+" .flt_action").val() != ""){ fn_endorsement.filtro += "eAccion|"+$(fn_endorsement.data_grid+" .flt_action").val()+","}
                    if($(fn_endorsement.data_grid+" .flt_date").val() != ""){ fn_endorsement.filtro += "A.dFechaAplicacion|"+$(fn_endorsement.data_grid+" .flt_date").val()+","}
                    if($(fn_endorsement.data_grid+" .flt_status").val() != ""){ fn_endorsement.filtro += "A.eStatus|"+$(fn_endorsement.data_grid+" .flt_status").val()+","}
                    fn_endorsement.fillgrid();
            },
            get_data : function(clave,ghost_mode){
               if(ghost_mode == ""){ghost_mode = false;} 
               $.post("endorsement_request_adds_server.php",{
                    "accion"  :"get_endorsement", 
                    "clave"   : clave,
                    "domroot" : "endorsements_edit_form"
               },
               function(data){
                    if(data.error == '0'){
                       $("#info_policies").show();//mostrar grid de archivos... 
                       $('#endorsements_edit_form .general_information input, #endorsements_edit_form .general_information select, #endorsements_edit_form .general_information textarea').val('');
                       $("#frm_endorsement_information .required-field").removeClass("error");
                       $("#endorsements_edit_form #info_policies table tbody").empty().append(data.policies);   
                       eval(data.fields); 
                       //fn_endorsement.get_unidades();
                       fn_endorsement.get_policies();
                       eval(data.policies);
                       $('#endorsements_edit_form #sComentarios').val(data.sComentarios);
    
                       //CONSULTAR ARCHIVOS:
                       /*fn_endorsement.files.iConsecutivoEndoso = $('#endorsements_edit_form input[name=iConsecutivo]').val();
                       fn_endorsement.files.fillgrid(); */
                       
                       //CONSULTAR DETALLE:
                       fn_endorsement.detalle.iConsecutivoEndoso = $('#endorsements_edit_form input[name=iConsecutivo]').val();
                       fn_endorsement.detalle.fillgrid();
                       
                       //CAMPOS SOLO INHABILITADOS:
                       $("#endorsements_edit_form #iConsecutivoCompania").addClass('readonly').prop('disabled','disabled');
                       
                       if(ghost_mode || (!(ghost_mode) && $(fn_endorsement.detalle.form+" input[name=sNombreCompania]").val() != "")){fn_endorsement.detalle.save();}
                       
                       fn_popups.resaltar_ventana('endorsements_edit_form');
                        
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
               },"json");  
            },
            save : function(ghost_mode){
                
                if(ghost_mode == ""){ghost_mode = false;}
                
                var valid = true;
                var msj   = "";
                $("#frm_endorsement_information input, #frm_endorsement_information select").removeClass("error");
                
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
                  if($('#endorsements_edit_form #iConsecutivo').val() != ""){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}
                  struct_data_post.action  = "save_endorsement";
                  struct_data_post.domroot = "#frm_endorsement_information .general_information";
                  $.ajax({             
                    type  : "POST", 
                    url   : "endorsement_request_adds_server.php", 
                    data  : struct_data_post.parse(),
                    async : true,
                    dataType : "json",
                    success  : function(data){                               
                        switch(data.error){ 
                         case '0':
                            if(!(ghost_mode)){fn_solotrucking.mensaje(data.msj);}
                            fn_endorsement.fillgrid();
                            fn_endorsement.get_data(data.iConsecutivo,ghost_mode);
                         break;
                         case '1': fn_solotrucking.mensaje(data.msj); break;
                        }
                    }
                  }); 
               }
               else{fn_solotrucking.mensaje('<p>Please check the following::</p><ul>'+msj+'</ul>');}
            },
            get_company_data : function(){
              $("#frm_driver_information input, #frm_driver_information select").val('');  
              //fn_endorsement.get_drivers();
              fn_endorsement.get_policies(); 
              fn_endorsement.valid_action(); 
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
                                $("#info_policies").show();//mostrar grid de archivos... 
                                $("#info_policies table tbody").empty().append(data.policies_information);
                            }
                        }
                  });
              }   
            }, 
            /*get_drivers : function(){
                if($("#frm_endorsement_information #iConsecutivoCompania").val() != ""){
                    $.ajax({
                        type    : "POST",
                        url     : "endorsement_request_adds_server.php",
                        data    : {'accion' : 'get_drivers_autocomplete','iConsecutivoCompania':$("#frm_endorsement_information #iConsecutivoCompania").val()},
                        async   : true,
                        dataType: "text",
                        success : function(data) {
                            var datos = eval(data);
                            if($("#frm_driver_information #sNombre").autocomplete( "option", "source" ) != ""){$("#frm_driver_information #sNombre").autocomplete( "destroy" );}
                            $("#frm_driver_information #sNombre").autocomplete({source:datos});
                        }
                    });
                }   
            }, */
            set_driver : function(){
                var data = $("#frm_driver_information #sNombre").val();
                var pipe = data.indexOf("|");
                if(pipe > 0){
                    var data = data.split("|");
                    $("#frm_driver_information input[name=sNombre]").val(data[0].trim());//Nombre
                    $("#frm_driver_information input[name=iNumLicencia]").val(data[1].trim());//Numero
                    $("#frm_driver_information select[name=eTipoLicencia]").val(data[2].trim());//Tipo
                    $("#frm_driver_information input[name=dFechaNacimiento]").val(data[3].trim());//BD
                    $("#frm_driver_information input[name=dFechaExpiracionLicencia]").val(data[4].trim());//Fecha
                    $("#frm_driver_information input[name=iExperienciaYear]").val(data[5].trim());//ExpYears
                    $("#frm_driver_information input[name=iConsecutivoOperador]").val(data[6].trim());//idOperador
                }
            }, 
            //PREVIEW AND SEND EMAILS:
            edit_estatus : function(){
                $(fn_endorsement.data_grid + " tbody .btn_edit_estatus").bind("click",function(){
                   var clave    = $(this).parent().parent().find("td:eq(0)").html();
                   $.ajax({             
                        type:"POST", 
                        url:"endorsement_request_adds_server.php", 
                        data:{accion:"get_endorsement_estatus",'iConsecutivo':clave,"domroot" : "form_estatus"},
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            if(data.error == '0'){
                                
                                $('#form_estatus input, #form_estatus textarea').val('').removeClass('error');
                                if(data.fields != ""){
                                    eval(data.fields);
                                }
                                else{$('#form_estatus #iConsecutivoEndoso').val(clave);}
                                $('#form_estatus .company_policies tbody').empty().append(data.policies_information);
                                if($('#form_estatus textarea').val() == ""){
                                    var mensaje_default = "Please create new endorsement for the following insured.";
                                    $('#form_estatus textarea').val(mensaje_default);
                                }
                                fn_popups.resaltar_ventana('form_estatus');   
                            }
                            else{fn_solotrucking.mensaje(data.msj);  }  
                        }
                  });
                   
               });  
            },
            email : { 
                save: function(ghost){
                    
                    if(!(ghost)){ghost = false;}
                    
                    var valid   = true;
                    var mensaje = "";
                    $('#form_estatus input, #form_estatus textarea').removeClass('error');

                    var insurances_policy  = "";
                    $("#form_estatus .company_policies input[type=text]").each(function(){
                        if(!($(this).hasClass('no_apply_endoso'))){
                           if($(this).val() != ""){
                           var id      = $(this).prop("class").split("_");
                           var email   = $(this).val();
                               email   = email.toLowerCase();
                               $(this).val(email); 
                               if(insurances_policy == ""){insurances_policy = id[1]+"|"+$(this).val();}
                               else{insurances_policy += ";"+id[1]+"|"+$(this).val();}
                           
                           }
                           else{$(this).addClass("error"); valid = false; mensaje = "Please write the email accounts that you will want  to send the message.";}    
                        }
                    });
                    
                    if(valid){
                       $.ajax({             
                            type:"POST", 
                            url:"endorsement_request_adds_server.php", 
                            data:{
                                'accion'             : "save_email",
                                'iConsecutivoEndoso'  : $('#form_estatus #iConsecutivoEndoso').val(),
                                'insurances_policy'  : insurances_policy,
                                'sMensaje'           : $('#form_estatus #sMensajeEmail').val(),
                                'domroot'            : "#form_estatus",
                            },
                            async : true,
                            dataType : "json",
                            success : function(data){                               
                                if(!(ghost)){fn_solotrucking.mensaje(data.msj);} 
                            }
                       });             
                    }else{fn_solotrucking.mensaje(mensaje);}
                    
                },
                preview : function(iConsecutivo){
                  
                  if(!(iConsecutivo)){ 
                      var iConsecutivo  = $('#form_estatus #iConsecutivoEndoso').val();
                      var mode          = "preview";
                      fn_endorsement.email.save(true);
                  }else{var mode = "openemail";}
                  
                  $.ajax({             
                    type:"POST", 
                    url:"endorsement_request_adds_server.php", 
                    data:{'accion' : 'preview_email','iConsecutivoEndoso' : iConsecutivo},
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){
                            $("#form_preview_email .preview_email").empty().append(data.tabla); 
                            $("#form_preview_email input.mode").val(mode);
                            if(mode == "preview"){$('#form_preview_email').show();}
                            else{fn_popups.resaltar_ventana('form_preview_email');}
                        }
                    }
                  });    
                },
                send : function(){
                  var iConsecutivo  = $('#form_estatus #iConsecutivoEndoso').val();
                  fn_endorsement.email.save(true);
                  $.ajax({             
                    type:"POST", 
                    url:"endorsement_request_adds_server.php", 
                    data:{'accion' : 'send_email','iConsecutivoEndoso' : iConsecutivo},
                    async : true,
                    dataType : "json",
                    success : function(data){ 
                        fn_solotrucking.mensaje(data.msj);                              
                        if(data.error == '0'){
                              fn_endorsement.fillgrid();
                              fn_popups.cerrar_ventana('form_estatus');
                        }
                        
                    }
                  });    
                },
                send_confirm : function(){
                   $('#dialog_send_email').dialog('open');   
                }, 
            },  
            change_estatus : function(){
                    $(fn_endorsement.data_grid + " tbody td .btn_change_status").bind("click",function(){
                       var clave = $(this).parent().parent().find("td:eq(0)").html();
                       var name  = $(this).parent().parent().find("td:eq(1)").html();
                       $.ajax({             
                            type:"POST", 
                            url:"endorsement_request_adds_server.php", 
                            data:{
                                "accion"             : "get_estatus_info",
                                "iConsecutivoEndoso"  : clave,
                                "domroot"            : "form_change_estatus",
                            },
                            async : true,
                            dataType : "json",
                            success : function(data){                               
                                if(data.error == '0'){
                                      $('#form_change_estatus input,#form_change_estatus textarea ').val('');
                                      $("#form_change_estatus fieldset legend").empty().append(name); 
                                      $("#form_change_estatus .company_policies tbody").empty().append(data.html);
                                      eval(data.fields); 
                                      $('.decimals').keydown(fn_solotrucking.inputdecimals);
                                      $("#form_change_estatus .file-message").html("");
                                      $("#form_change_estatus #fileselect2").removeClass("fileupload");
                                      
                                      //inicializar archivo:
                                      /*if(window.File && window.FileList && window.FileReader) {
                                          fn_solotrucking.files.datagrid  = "#form_change_estatus";
                                          fn_solotrucking.files.fileinput = "fileselect2";
                                          fn_solotrucking.files.add(); 
                                      }*/
                                      
                                      fn_endorsement.files.iConsecutivoEndoso = clave;
                                      fn_endorsement.files.fillgrid();
                                    
                                      fn_popups.resaltar_ventana('form_change_estatus'); 
                                }
                                
                            }
                       });   
                   });  
            },
            save_estatus : function(){
                  $('#form_change_estatus input,#form_change_estatus textarea ').removeClass('error'); 
                  var valid   = true;
                  var msj     = "";
                  var polizas = "";
                  
                  $("#form_change_estatus .company_policies tbody .data_policy").each(function(){
                      var estatus  = $(this).find("select[name=eStatus]").val();
                      var comments = $(this).find("textarea[name=sComentarios]").val();
                      var NoEndoso = $(this).find("input[name=sNumeroEndosoBroker]").val();
                      var idPoliza = $(this).prop("id");
                      var ValEndoso= $(this).find("input[name=rImporteEndosoBroker]").val();
                      
                          idPoliza = idPoliza.split("dataPolicy_");
                          if(polizas == ""){polizas = idPoliza[1]+"|"+estatus+"|"+comments+"|"+NoEndoso+"|"+ValEndoso;}
                          else{polizas += ";"+idPoliza[1]+"|"+estatus+"|"+comments+"|"+NoEndoso+"|"+ValEndoso;}
                  });
                  
                  $('#form_change_estatus input[name=polizas]').val(polizas);
                  
                  if(valid){
                     
                     var form       = "#form_change_estatus .forma";
                     var dataForm   = new FormData();
                     var other_data = $(form).serializeArray();
                     dataForm.append('accion','save_estatus_info');
                     $.each($(form+' input[type=file]')[0].files,function(i, file){dataForm.append('file-'+i, file);});
                     $.each(other_data,function(key,input){dataForm.append(input.name,input.value);}); 
                      
                     $.ajax({             
                        type:"POST", 
                        url:"endorsement_request_adds_server.php", 
                        data: dataForm,
                        cache: false,
                        contentType: false,
                        processData: false,
                        dataType : "json",
                        success : function(data){                               
                            fn_solotrucking.mensaje(data.msj);
                            $("#form_change_estatus .file-message").html("");
                            $("#form_change_estatus #fileselect2").removeClass("fileupload");
                            fn_endorsement.files.fillgrid();
                        }
                     });   
                  }else{fn_solotrucking.mensaje('Please first select a status before you press save.');$('#form_change_estatus #eStatus').addClass('error');} 
            }, 
            detalle : {
                form : "#frm_additional_data",
                data_grid : "#additional_datagrid",
                iConsecutivoEndoso : "",
                add : function(){
                   $(fn_endorsement.detalle.form+" .required-field").removeClass("error");
                   $(fn_endorsement.detalle.form+" input, "+fn_endorsement.detalle.form+" select").val("");
                   fn_endorsement.detalle.valid_action(); 
                },
                save: function(){
                    //VALIDAR SI EL ENDOSO YA SE GUARDO:
                    if(fn_endorsement.detalle.iConsecutivoEndoso == ""){
                         fn_endorsement.save(true);
                    }
                    else{
                       var valid = true;
                       var msj   = "";
                       $(fn_endorsement.detalle.form+" .required-field-delete, "+fn_endorsement.detalle.form+" .required-field-add").removeClass("error"); 
                        
                       //Asignar Endoso id:
                       $(fn_endorsement.detalle.form+" input[name=iConsecutivoEndoso]").val(fn_endorsement.detalle.iConsecutivoEndoso); 
                        
                       //Validar dependiendo la accion:
                       var action = $(fn_endorsement.detalle.form+" select[name=eAccion]").val();
                       if(action != "" && (action == 'ADD' || action == 'ADDSWAP')){
                           $(fn_endorsement.detalle.form+" .required-field-add").each(function(){
                                if($(this).val() == ""){
                                   valid = false; 
                                   msj   = "<li>You must capture the required fields for an ADD OR ADD SWAP.</li>";
                                   $(this).addClass('error');
                                }
                           }); 
                       }else if(action != "" && (action == 'DELETE' || action == 'DELETESWAP')){
                           $(fn_endorsement.detalle.form+" .required-field-delete").each(function(){
                                if($(this).val() == ""){
                                   valid = false; 
                                   msj   = "<li>You must capture the required fields for an DELETE OR DELETE SWAP.</li>";
                                   $(this).addClass('error');
                                }
                           }); 
                       }else{
                          valid = false; 
                          msj   = "<li>You must first select an action.</li>";
                          $(fn_endorsement.detalle.form+" select[name=eAccion]").addClass('error'); 
                       }
                       
                       if(valid){
                              if($(fn_endorsement.detalle.form+' [name=iConsecutivoDetalle]').val() != ""){struct_data_post_new.edit_mode = "true";}else{struct_data_post_new.edit_mode = "false";}
                              struct_data_post_new.action  = "detalle_save";
                              struct_data_post_new.domroot = fn_endorsement.detalle.form;
                              $.ajax({             
                                type  : "POST", 
                                url   : "endorsement_request_adds_server.php", 
                                data  : struct_data_post_new.parse(),
                                async : true,
                                dataType : "json",
                                success  : function(data){                               
                                    switch(data.error){ 
                                     case '0':
                                        fn_solotrucking.mensaje(data.msj);
                                        if(data.error == "0"){
                                           fn_endorsement.detalle.add();
                                           fn_endorsement.detalle.fillgrid(); 
                                        }
                                        
                                     break;
                                     case '1': fn_solotrucking.mensaje(data.msj); break;
                                    }
                                }
                              }); 
                       }
                       else{fn_solotrucking.mensaje('<p>Please check the following:</p><ul>'+msj+'</ul>');}
                    }
                     
                },
                valid_action : function(){
                
                    var action = $(fn_endorsement.detalle.form+" select[name=eAccion]").val();
                    $(fn_endorsement.detalle.form+' .delete_field, '+fn_endorsement.detalle.form+' .add_field').hide();
                    
                    if(action == 'D' || action == 'DELETESWAP'){$(fn_endorsement.detalle.form+' .delete_field').show();}else 
                    if(action == 'A' || action == 'ADDSWAP')   {$(fn_endorsement.detalle.form+' .add_field').show();}
                },
                fillgrid : function(){
                    $.ajax({             
                        type:"POST", 
                        url:"endorsement_request_adds_server.php", 
                        data:{accion:"detalle_datagrid","iConsecutivoEndoso":fn_endorsement.detalle.iConsecutivoEndoso},
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $(fn_endorsement.detalle.data_grid+" tbody").empty().append(data.tabla);
                            fn_endorsement.detalle.edit(); 
                            fn_endorsement.detalle.borrar(); 
                        }
                    });     
                },
                edit : function (){
                    $(fn_endorsement.detalle.data_grid + " tbody td .btn_edit_detalle").bind("click",function(){
                        var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                            clave = clave.split('idDet_');
                            clave = clave[1];
                        
                        $.ajax({             
                            type:"POST", 
                            url:"endorsement_request_adds_server.php", 
                            data:{
                                accion               : "detalle_get",
                                "iConsecutivoEndoso" : fn_endorsement.detalle.iConsecutivoEndoso,
                                "iConsecutivoDetalle": clave,
                                "domroot"            : fn_endorsement.detalle.form
                            },
                            async : true,
                            dataType : "json",
                            success : function(data){ 
                                if(data.error == "0"){
                                   fn_endorsement.detalle.add();
                                   eval(data.fields);
                                   fn_endorsement.detalle.valid_action(); 
                                }else{fn_solotrucking.mensaje(data.msj);}                          
                                
                            }
                        });        
                  });  
                },
                borrar : function (){
                    $(fn_endorsement.detalle.data_grid + " tbody td .btn_delete_detalle").bind("click",function(){
                        var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                            clave = clave.split('idDet_');
                            clave = clave[1];
                        
                        $.ajax({             
                            type:"POST", 
                            url:"endorsement_request_adds_server.php", 
                            data:{
                                accion               : "detalle_delete",
                                "iConsecutivoEndoso" : fn_endorsement.detalle.iConsecutivoEndoso,
                                "iConsecutivoDetalle": clave,
                            },
                            async : true,
                            dataType : "json",
                            success : function(data){ 
                                fn_solotrucking.mensaje(data.msj);
                                if(data.error == "0"){
                                   fn_endorsement.detalle.fillgrid();
                                }                         
                                
                            }
                        });        
                  });  
                },
            },
            files : {
               pagina_actual : "",
               sort : "ASC",
               orden : "iConsecutivo",
               iConsecutivoEndoso : "",
               datagrid : "#files_datagrid",
               form     : "#dialog_upload_files",
               fillgrid: function(){
                    $.ajax({             
                        type:"POST", 
                        url:"endorsement_request_adds_server.php", 
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
                            $(fn_endorsement.files.datagrid+" tbody").empty().append(data.tabla);
                            $(fn_endorsement.files.datagrid+" tbody tr:even").addClass('gray');
                            $(fn_endorsement.files.datagrid+" tbody tr:odd").addClass('white');
                            $(fn_endorsement.files.datagrid+" tfoot .paginas_total").val(data.total);
                            $(fn_endorsement.files.datagrid+" tfoot .pagina_actual").val(data.pagina);
                            fn_endorsement.files.pagina_actual = data.pagina;
                            fn_endorsement.files.delete_file();
               
                        }
                    }); 
               },
               delete_file : function(){
                  $(fn_endorsement.files.datagrid+" tbody td .trash").bind("click",function(){
                        var clave = $(this).parent().parent().find("td:eq(0)").attr('id');
                            clave = clave.split("idFile_");
                            clave = clave[1];
                        $.post("endorsement_request_adds_server.php",{"accion":"elimina_archivo_endoso", "iConsecutivo": clave},
                        function(data){
                            fn_solotrucking.mensaje(data.msj);
                            if(data.error == '0'){fn_endorsement.files.fillgrid();}      
                  },"json");
                  });  
                },
               firstPage : function(){
                    if($(fn_endorsement.files.datagrid+" .pagina_actual").val() != "1"){
                        fn_endorsement.files.pagina_actual = "";
                        fn_endorsement.files.fillgrid();
                    }
                },
               previousPage : function(){
                    if($(fn_endorsement.files.datagrid+" .pagina_actual").val() != "1"){
                        fn_endorsement.files.pagina_actual = (parseInt($(fn_endorsement.files.datagrid+" .pagina_actual").val()) - 1) + "";
                        fn_endorsement.files.fillgrid();
                    }
                },
               nextPage : function(){
                    if($(fn_endorsement.files.datagrid+" .pagina_actual").val() != $(fn_endorsement.files.datagrid+" .paginas_total").val()){
                        fn_endorsement.files.pagina_actual = (parseInt($(fn_endorsement.files.datagrid+" .pagina_actual").val()) + 1) + "";
                        fn_endorsement.files.fillgrid();
                    }
                },
               lastPage : function(){
                    if($(fn_endorsement.files.datagrid+" .pagina_actual").val() != $(fn_endorsement.files.datagrid+" .paginas_total").val()){
                        fn_endorsement.files.pagina_actual = $(fn_endorsement.files.datagrid+" .paginas_total").val();
                        fn_endorsement.files.fillgrid();
                    }
                }, 
               ordenamiento : function(campo,objeto){
                    $(fn_endorsement.files.datagrid+" #grid-head2 td").removeClass('down').removeClass('up');
                    if(campo == fn_endorsement.files.orden){
                        if(fn_endorsement.files.sort == "ASC"){
                            fn_endorsement.files.sort = "DESC";
                            $(fn_endorsement.files.datagrid+" #grid-head2 td:eq("+objeto+")").addClass('up');
                        }else{
                            fn_endorsement.files.sort = "ASC";
                            $(fn_endorsement.files.datagrid+" #grid-head2 td:eq("+objeto+")").addClass('down');
                        }
                    }else{
                        fn_endorsement.files.sort = "ASC";
                        fn_endorsement.files.orden = campo;
                        $(fn_endorsement.files.datagrid+" #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                    fn_endorsement.files.fillgrid();
                    return false;
                }, 
               add : function(){
                   $(fn_endorsement.files.form+" input[name=iConsecutivoEndoso]").val(fn_endorsement.files.iConsecutivoEndoso);
                   $(fn_endorsement.files.form+" .file-message").html("");
                   $(fn_endorsement.files.form+" #fileselect").removeClass("fileupload");
                   $(fn_endorsement.files.form).dialog("open");
                   
                   fn_endorsement.files.active_file_form('#dialog_upload_files','fileselect');
                   
                   //Archivos:
                   /*if(window.File && window.FileList && window.FileReader) {
                      fn_solotrucking.files.form      = "#dialog_upload_files";
                      fn_solotrucking.files.fileinput = "fileselect";
                      fn_solotrucking.files.add();
                   }*/ 
               },
               save : function(){
                   var valid   = true;
                   var mensaje = "";
                      
                  //Validar campo para archivo:
                  if($(fn_endorsement.files.form+" #fileselect").val() == ""){
                      mensaje += '<li>No file has been loaded.</li>';
                      valid    = false;
                      $(fn_endorsement.files.form+" #fileselect").addClass('error'); 
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
                          url : "endorsement_request_adds_server.php",
                          data: dataForm,
                          cache: false,
                          contentType: false,
                          processData: false,
                          type: 'POST',
                          dataType : "json",
                          success : function(data){ 
                              fn_solotrucking.mensaje(data.mensaje);
                              if(data.error == "0"){  
                                  $(fn_endorsement.files.form).dialog('close');
                                  fn_endorsement.files.fillgrid();
                              }
                          }
                      });        
                  }else{
                      fn_solotrucking.mensaje('<p>Favor de revisar lo siguiente:</p><ul>'+mensaje+'</ul>'); 
                  } 
               },
               active_file_form : function(datagrid,fileinput){
                  //inicializar archivo:
                  if(window.File && window.FileList && window.FileReader) {
                      fn_solotrucking.files.form      = datagrid;
                      fn_solotrucking.files.fileinput = fileinput;
                      fn_solotrucking.files.add(); 
                  }
               },
            },
                
    }    
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_endorsement" class="container">
        <div class="page-title">
            <h1>ENDORSEMENTS / ENDOSOS</h1>
            <h2>LOSS PAYEE OR ADDITIONAL INSURED / PÉRDIDA BENEFICIARIO o asegurado adicional</h2>
        </div>
        <table id="data_grid_endorsement" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'>
                    <input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_company" type="text" placeholder="Company:"></td>
                <td></td>
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
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('D.sNombreCompania',this.cellIndex);">COMPANY</td>
                <td class="etiqueta_grid">Description</td>
                <!--<td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('sNombre',this.cellIndex);">TYPE</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('eAccion',this.cellIndex);">ACTION</td>-->
                <td class="etiqueta_grid up"   onclick="fn_endorsement.ordenamiento('A.dFechaAplicacion',this.cellIndex);">APPLICATION DATE</td> 
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
<div id="endorsements_edit_form" class="popup-form" style="width: 1000px;">
    <div class="p-header">
        <h2>ENDORSEMENTS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('endorsements_edit_form');fn_endorsement.filtraInformacion();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <form method="post" autocomplete="off">
            <fieldset id="frm_endorsement_information" style="padding-bottom: 0;">
                <legend>GENERAL DATA</legend>  
                <table class="general_information" style="width:100%" cellpadding="0" cellspacing="0">
                    <tr>
                    <td colspan="100%">
                        <p class="mensaje_valido">
                        &nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.<br>
                        <span style="color:#ff0000;font-size:1em;"> &nbsp;<b>Note: </b>Please check before sending to brokers, that all data in this endorsement are correct.</span></p>
                        <input id="iConsecutivo" name="iConsecutivo" type="hidden" value=""> 
                        <!--<input id="dFechaAplicacionHora" name="dFechaAplicacionHora" type="hidden" class="hora required-field">-->
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
                        <td style="width:55%;">
                            <div class="field_item">
                                <label>Company <span style="color:#ff0000;">*</span>:</label>  
                                <select tabindex="1" id="iConsecutivoCompania" onchange="fn_endorsement.get_company_data();"  name="iConsecutivoCompania" class="required-field" style="height: 25px!important;width: 99%!important;"><option value="">Select an option...</option></select>
                            </div>
                        </td>
                        <td>
                            <div class="field_item">
                                <label>Application Date <span style="color:#ff0000;">*</span>:</label> 
                                <input tabindex="2" id="dFechaAplicacion" name="dFechaAplicacion" class="txt-uppercase fecha required-field" placeholder="mm/dd/yyyy" type="text" style="width: 85%;">
                            </div>
                        </td>
                    </tr>
                </table>
                <legend>Additional Company Data</legend> 
                <table id="frm_additional_data" style="width:100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="width: 50%;">
                        <input id="iConsecutivoDetalle" name="iConsecutivoDetalle" type="hidden" value="">
                        <input id="iConsecutivoEndoso"  name="iConsecutivoEndoso" type="hidden" value=""> 
                        <div class="field_item">
                            <label>Action <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="4" id="eAccion" name="eAccion" class="required-field-add required-field-delete" onblur="fn_endorsement.detalle.valid_action();" style="height: 25px!important;width: 99%!important;">
                                <option value="">Select an option...</option> 
                                <option value="ADD">ADD</option>
                                <option value="DELETE">DELETE</option>
                                <option value="ADDSWAP">ADD SWAP</option>
                                <option value="DELETESWAP">DELETE SWAP</option>
                            </select>
                        </div> 
                        </td>
                        <td>
                        <div class="field_item">
                            <label>Type <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="4" id="eTipoEndoso" name="eTipoEndoso" class="required-field-add required-field-delete" style="height: 25px!important;width: 99%!important;">
                                <option value="">Select an option...</option> 
                                <option value="ADDITIONAL INSURED">ADDITIONAL INSURED</option>
                                <option value="LOSS PAYEE">LOSS PAYEE</option>
                            </select>
                        </div> 
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Name <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="3" id="sNombreCompania" name="sNombreCompania"  type="text" class="required-field-add required-field-delete txt-uppercase" style="width: 98%;" autocomplete="off" placeholder="Full Company or Personal Name">
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Address <span class="add_field" style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="5" id="sDireccion" name="sDireccion"  type="text" class="required-field-add txt-uppercase" style="width: 98%;" autocomplete="off" placeholder="Street Address:">
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <table style="width: 99%;" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                    <div class="field_item">
                                        <label>City <span class="add_field" style="color:#ff0000;">*</span>:</label>  
                                        <input tabindex="6" id="sCiudad" name="sCiudad" type="text" class="required-field-add txt-uppercase" placeholder="" maxlength="200" style="width: 95%;"> 
                                    </div>
                                    </td>
                                    <td>
                                    <div class="field_item">
                                        <label>Zip Code <span class="add_field" style="color:#ff0000;">*</span>:</label>   
                                        <input tabindex="7" id="sCodigoPostal" class="numb required-field-add" name="sCodigoPostal" type="text" placeholder="" style="width:96%"> 
                                    </div>
                                    </td>
                                    <td>
                                    <div class="field_item">
                                        <label>State <span class="add_field" style="color:#ff0000;">*</span>:</label>  
                                        <select tabindex="8" id="sEstado" name="sEstado" class="required-field-add" style="height: 26px!important;"></select>
                                    </div>
                                    </td>
                                </tr>
                            </table>
                        </td>    
                    </tr>
                    <tr>
                    <td colspan="100%">
                    <table id="additional_datagrid" class="popup-datagrid" style="width: 100%;margin-top: 10px;margin-bottom: 10px;" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr id="grid-head2">
                                <td class="etiqueta_grid" style="width:80px;">Action</td>
                                <td class="etiqueta_grid" style="width:150px;">Company Name</td>
                                <td class="etiqueta_grid" style="width:80px;">Type</td>
                                <td class="etiqueta_grid">Address</td>
                                <td class="etiqueta_grid" style="width: 135px;text-align: center;">
                                    <div class="btn-icon edit btn-left" title="Save Additional" onclick="fn_endorsement.detalle.save();" style="width: auto!important;"><i class="fa fa-check"></i><span style="padding-left: 5px;font-size: 0.8em;text-transform: uppercase;">Save Additional</span></div>
                                </td>
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                    </table>
                    </td>
                    </tr>
                    <tr>
                    <td colspan="100%">
                    <div class="field_item general_information">
                        <label>General Comments:</label> 
                        <textarea tabindex="9" id="sComentarios" name ="sComentarios" style="resize:none;height:30px!important;"></textarea>
                    </div>
                    </td>
                    </tr>
                </table>
            </fieldset>
            <button type="button" class="btn-1" onclick="fn_endorsement.save();">SAVE</button>
            <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('endorsements_edit_form');fn_endorsement.filtraInformacion();" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
        </form> 
    </div>
    </div>
</div>
<!-- EMAILS FORMS -->
<div id="form_estatus" class="popup-form" style="width: 80%;">
    <div class="p-header">
        <h2>ENDORSEMENTS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('form_estatus');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container"> 
    <div>
        <form>
            <div class="field_item"> 
                <label style="margin-left:5px;">Policies in which the endorsement was applied:</label> 
                <div class="company_policies" style="padding:5px 0px;">
                    <table class="popup-datagrid">
                    <thead>
                        <tr id="grid-head2"> 
                            <td class="etiqueta_grid">Policy Number</td>
                            <td class="etiqueta_grid">Policy Type</td> 
                            <td class="etiqueta_grid">Broker</td>
                            <td class="etiqueta_grid" style="width:500px;">Email to send</td>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    </table>
                </div>
            </div>
            <fieldset>
                <legend>INFORMATION TO SEND BY E-MAIL</legend>
                <input id="iConsecutivoEndoso" type="hidden" value=""> 
                <table style="width: 100%;">
                <tr>
                    <td colspan="100%">
                    <div class="field_item"> 
                        <label>Message to send: (This message will be displayed before the endorsement information.)</label> 
                        <textarea tabindex="1" id="sMensajeEmail" maxlenght="1000" style="resize: none;" title="Max. 1000 characters."></textarea>
                    </div>
                    </td>                              
                </tr>
                </table>
            </fieldset> 
            <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('form_estatus');" style="margin-right:10px;background:#e8051b;">CLOSE</button>  
            <button type="button" class="btn-1" onclick="fn_endorsement.email.send_confirm();" style="margin-right:10px;background: #87c540;width: 140px;">SEND E-MAIL</button>
            <button type="button" class="btn-1" onclick="fn_endorsement.email.preview();" style="margin-right:10px;background:#5ec2d4;width: 140px;">PREVIEW E-MAIL</button> 
            <button type="button" class="btn-1" onclick="fn_endorsement.email.save();" style="margin-right:10px;">SAVE</button>  
        </form> 
    </div>
    </div>
</div>
<div id="form_change_estatus" class="popup-form" style="width:95%;">
    <div class="p-header">
        <h2>ENDORSEMENTS / Change the status of endorsement</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('form_change_estatus');fn_endorsement.filtraInformacion();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
        <form>
            <fieldset>
            <legend></legend> 
            <table style="width: 100%;">
            <tr class="claim_estatus">
                <td colspan="2">
                <div class="field_item">
                    <label style="margin-left:5px;margin-bottom:3px;">You can manage each endorsement status for each individual policy and add comment about it into the system:</label>
                    <table class="company_policies popup-datagrid" style="width: 100%;margin-top: 5px;">
                        <tbody></tbody>
                    </table>  
                </div>
                </td>
            </tr>
            </table>
            </fieldset>
        </form>
        <form class="forma">
            <fieldset>
                <input name="iConsecutivoEndoso" type="hidden" value=""> 
                <input name="polizas" type="hidden" value=""> 
                <table style="width: 100%;">
                <tr class="claim_estatus">
                    <td colspan="2">
                    <div class="field_item"> 
                        <label class="required-field">File to upload the endorsement broker file:</label>
                        <div class="file-container" onclick="fn_endorsement.files.active_file_form('#form_change_estatus','fileselect2');">
                            <input id="fileselect2" name="fileselect2" type="file"/>
                            <div class="file-message"></div>
                        </div>
                    </div> 
                    <div class="field_item">
                        <label>General Comments for this Endorsement: <span style="color: #5e8bd4;;">(These comments are those that will be shown to the client.)</span></label>
                        <textarea id="sComentariosEndoso" name ="sComentariosEndoso" style="resize:none;height:50px!important;"></textarea> 
                    </div> 
                    <div class="field_item">
                        <label>General Status for this Endorsement: <span style="color: #5e8bd4;;">(This Status is that will be shown in the data grid.)</span></label>
                        <select id="eStatusEndoso" name ="eStatusEndoso">
                            <option value="SB">Sent to Brokers - The endorsement has been sent to the brokers.</option>
                            <option value="P">In Process - The endorsement is being in process by the brokers.</option>
                            <option value="D">Canceled - The endorsement has been canceled.</option>
                            <option value="A">Approved - The endorsement has been approved.</option>
                        </select> 
                    </div> 
                    </td>
                </tr>
                </table>
            </fieldset> 
        </form>   
        <div>
            <button type="button" class="btn-1" onclick="fn_endorsement.save_estatus();">SAVE</button> 
            <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('form_change_estatus');fn_endorsement.filtraInformacion();" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
        </div>
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
    </div>
</div>
<!-- preview email -->
<div id="form_preview_email" class="popup-form" style="width: 80%;">
    <div class="p-header">
        <h2>ENDORSEMENTS / Preview E-mail to send</h2>
        <div class="btn-close" title="Close Window" onclick="if($('#form_preview_email input.mode').val() == 'openemail'){fn_popups.cerrar_ventana('form_preview_email');}else{$('#form_preview_email').hide();}"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container"> 
        <input class="mode" type="hidden" value="">
        <div class="preview_email"></div>
    <div>
        <button type="button" class="btn-1" onclick="if($('#form_preview_email input.mode').val() == 'openemail'){fn_popups.cerrar_ventana('form_preview_email');}else{$('#form_preview_email').hide();}" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
    </div>
    </div>
</div>
<!-- DIALOGUES -->
<div id="dialog_endorsement_save" title="SYSTEM ALERT" style="display:none;">
    <p>We will be sending a notice to the company about the status of endorsements. Are you sure want to continue?</p> 
</div> 
<div id="dialog_upload_files" title="Upload files to endorsement" style="display:none;padding: .5em 1em .5em 0em!important;">
    <form class="frm_upload_files" action="" method="POST" enctype="multipart/form-data">
        <fieldset>
        <input name="iConsecutivoEndoso" type="hidden" value="">
        <div>
            <label>File Category <span style="color:#ff0000;">*</span>: </label>
            <Select name="eArchivo" style="height: 27px!important;">
                <option value="OTHERS">Other</option>
                <option value="ENDORSEMENT">Endorsement</option>
            </select> 
        </div>
        <div class="field_item"> 
            <label class="required-field">File to upload:</label>
            <div class="file-container">
                <input id="fileselect" name="fileselect" type="file"/>
                <div class="file-message"></div>
            </div>
        </div> 
        </fieldset>
    </form>   
</div> 
<div id="dialog_send_email" title="SYSTEM ALERT" style="display:none;">
    <p>Are you sure that want to send the endorsement to the broker(s)?</p>
</div>
<!-- FOOTER -->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>
