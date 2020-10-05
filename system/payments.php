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
            var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
            var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
            validapantalla(usuario_actual);  
            $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); });
            fn_payments.init();
            $.unblockUI();
            
            $('#dialog-confirm-delete-invoice').dialog({
                modal: true,
                autoOpen: false,
                width : 550,
                height : 200,
                resizable : false,
                dialogClass: "without-close-button",
                buttons : {
                    'CONFIRM' : function() {
                        var clave = $('#dialog-confirm-delete-invoice input[name=iConsecutivo]').val();
                        fn_payments.eliminar(clave);
                        $(this).dialog('close');
                    },
                    'CANCEL' : function(){$(this).dialog('close');}
                }
            });
        
    }  
    function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
    var fn_payments = {
            domroot:"#ct_payments",
            data_grid: "#data_grid_payments",
            form : "#edit_form_payment",
            filtro : "",
            pagina_actual : "",
            sort : "DESC",
            orden : "LEFT(A.dFechaPago,10)",
            init : function(){
                fn_payments.fillgrid();
                $('.num').keydown(fn_solotrucking.inputnumero);
                $('.inputdecimals').keydown(fn_solotrucking.inputdecimals); 
                //Filtrado con la tecla enter
                $(fn_payments.data_grid + ' #grid-head1 input').keyup(function(event){
                    if (event.keyCode == '13') {
                        event.preventDefault();
                        fn_payments.filtraInformacion();
                    }
                    if(event.keyCode == '27'){
                       event.preventDefault();
                       $(this).val(''); 
                       fn_payments.filtraInformacion();
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
                //Cargar Catalogos:
                $.ajax({             
                    type:"POST", 
                    url:"catalogos_generales.php", 
                    data:{accion:"get_companies"},
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){$("#edit_form_payment select[name=iConsecutivoCompania]").empty().append(data.select); }
                    }
                });
                
                //Archivos:
                if(window.File && window.FileList && window.FileReader) {
                      fn_solotrucking.files.form      = "#edit_form_payment";
                      fn_solotrucking.files.fileinput = "fileselect";
                      fn_solotrucking.files.add();
                }
                
            },
            fillgrid: function(){
                   $.ajax({             
                    type:"POST", 
                    url:"payments_server.php", 
                    data:{
                        accion:"get_payments",
                        registros_por_pagina : "15", 
                        pagina_actual : fn_payments.pagina_actual, 
                        filtroInformacion : fn_payments.filtro,  
                        ordenInformacion : fn_payments.orden,
                        sortInformacion : fn_payments.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_payments.data_grid+" tbody").empty().append(data.tabla);
                        $(fn_payments.data_grid+" tbody tr:even").addClass('gray');
                        $(fn_payments.data_grid+" tbody tr:odd").addClass('white');
                        $(fn_payments.data_grid + " tfoot #paginas_total").val(data.total);
                        $(fn_payments.data_grid + " tfoot #pagina_actual").val(data.pagina);
                        fn_payments.pagina_actual = data.pagina; 
                        fn_payments.edit();
                        fn_payments.confirmar_eliminar();
                        fn_solotrucking.btn_tooltip();
                    }
                }); 
            },
            add : function(){
               $(fn_payments.form+' :text, '+fn_payments.form+' select , '+fn_payments.form+' input').val('').removeClass('error');
               $(fn_payments.form+' .mensaje_valido').empty().append('The fields containing an (<span style="color:#ff0000;">*</span>) are required.');
               $(fn_payments.form+' .p-header h2').empty().append('PAYMENTS - NEW PAYMENT');
               $(fn_payments.form+' input[name=sNoPago]').removeProp('readonly').removeClass("readonly");
               $(fn_payments.form+' select[name=sCveMoneda]').val('USD');
               
               fn_solotrucking.get_date("#dFechaPago");
               
               //Limpiar campo file:
               $(fn_payments.form+" .file-message").html("");
               $(fn_payments.form+" #fileselect").val(null);
               $(fn_payments.form+" #fileselect").removeClass("fileupload");
               
               fn_popups.resaltar_ventana('edit_form_payment');  
            },
            edit : function (){
                $(fn_payments.data_grid + " tbody td .btn_edit").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    
                    $(fn_payments.form+' .p-header h2').empty().append('INVOICES - EDIT PAYMENT #' + $(this).parent().parent().find("td:eq(0)").html()); 
                    $.post("payments_server.php",{accion:"get_data", clave: clave, domroot : fn_payments.form},
                    function(data){
                        if(data.error == '0'){
                            
                           $(fn_payments.form+' :text, '+fn_payments.form+' select').val('').removeClass('error'); 
                           $(fn_payments.form+' input[name=sNoPago]').prop('readonly','readonly').addClass("readonly");
                           
                           //Limpiar campo file:
                           $(fn_payments.form+" .file-message").html("");
                           $(fn_payments.form+" #fileselect").val(null);
                           $(fn_payments.form+" #fileselect").removeClass("fileupload");
               
                           eval(data.fields);
                           
                           fn_popups.resaltar_ventana('edit_form_payment');
                        }
                        else{fn_solotrucking.mensaje(data.msj);}       
                    },"json"); 
                }); 
              
                $(fn_payments.data_grid + " tbody td .btn_delete").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(1)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    
                    $.post("payments_server.php",{accion:"apply_data", clave: clave},
                    function(data){
                        fn_solotrucking.mensaje(data.msj);
                        if(data.error == '0'){fn_payments.filtraInformacion();}
                    },"json"); 
                }); 
                
            }, 
            save : function (){
               
               //Validate Fields:
               var valid = true;
               var message = "";
               $(fn_payments.form+" .required-field").removeClass("error");
               $(fn_payments.form+" .required-field").each(function(){
                   if($(this).val() == ""){
                       valid   = false;
                       message = '<li> You must write all required fields.</li>';
                       $(this).addClass('error');
                   }
               });
               
               if(valid){
                    var form       = "#edit_form_payment form";
                    var dataForm   = new FormData();
                    var other_data = $(form).serializeArray();
                    dataForm.append('accion','save_data');
                    $.each($(form+' input[type=file]')[0].files,function(i, file){dataForm.append('file-'+i, file);});
                    $.each(other_data,function(key,input){dataForm.append(input.name,input.value);});
                    
                    $.ajax({
                      type: "POST",
                      url : "payments_server.php",
                      data: dataForm,
                      cache: false,
                      contentType: false,
                      processData: false,
                      type: 'POST',
                      dataType : "json",
                      success : function(data){ 
                          switch(data.error){
                              case '0':  
                                fn_solotrucking.mensaje(data.msj);
                                fn_payments.filtraInformacion();
                                fn_popups.cerrar_ventana('edit_form_payment');
                              break;
                              case '1': fn_solotrucking.mensaje(data.msj); break;
                          }
                      }
                    });   
                 
               }else{
                   fn_solotrucking.mensaje('<p>Please check the following:</p><ul style="padding: 10px;">'+message+'</ul>');
               }
                
            },
            firstPage : function(){
                if($(fn_payments.data_grid+" #pagina_actual").val() != "1"){
                    fn_payments.pagina_actual = "";
                    fn_payments.fillgrid();
                }
            },
            previousPage : function(){
                if($(fn_payments.data_grid+" #pagina_actual").val() != "1"){
                    fn_payments.pagina_actual = (parseInt($(fn_payments.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_payments.fillgrid();
                }
            },
            nextPage : function(){
                if($(fn_payments.data_grid+" #pagina_actual").val() != $(fn_payments.data_grid+" #paginas_total").val()){
                    fn_payments.pagina_actual = (parseInt($(fn_payments.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_payments.fillgrid();
                }
            },
            lastPage : function(){
                if($(fn_payments.data_grid+" #pagina_actual").val() != $(fn_payments.data_grid+" #paginas_total").val()){
                    fn_payments.pagina_actual = $(fn_payments.data_grid+" #paginas_total").val();
                    fn_payments.fillgrid();
                }
            }, 
            ordenamiento : function(campo,objeto){
                $(fn_payments.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_payments.orden){
                    if(fn_payments.sort == "ASC"){
                        fn_payments.sort = "DESC";
                        $(fn_payments.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_payments.sort = "ASC";
                        $(fn_payments.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_payments.sort = "ASC";
                    fn_payments.orden = campo;
                    $(fn_payments.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_payments.fillgrid();

                return false;
            }, 
            filtraInformacion : function(){
                fn_payments.pagina_actual = 0;
                fn_payments.filtro = "";
                if($(fn_payments.data_grid+" .flt_ref").val() != "")   { fn_payments.filtro += "sNoPago|"+$(fn_payments.data_grid+" .flt_ref").val()+","}
                if($(fn_payments.data_grid+" .flt_name").val() != "")  { fn_payments.filtro += "sNombreCompania|"+$(fn_payments.data_grid+" .flt_name").val()+","}  
                if($(fn_payments.data_grid+" .flt_date").val() != "")  { fn_payments.filtro += "dFechaPago|"+$(fn_payments.data_grid+" .flt_date").val()+","}  
                if($(fn_payments.data_grid+" .flt_amount").val() != ""){ fn_payments.filtro += "iMonto|"+$(fn_payments.data_grid+" .flt_amount").val()+","}    
                if($(fn_payments.data_grid+" .flt_desc").val() != ""){ fn_payments.filtro += "sDescripcion|"+$(fn_payments.data_grid+" .flt_desc").val()+","}  
                if($(fn_payments.data_grid+" .flt_meth").val() != ""){ fn_payments.filtro += "sMetodoPago|"+$(fn_payments.data_grid+" .flt_meth").val()+","}  
                fn_payments.fillgrid();
            },
            valid_type : function(service){
                if(service == '1'){ //ENDORSEMENTS:
                  // 1 - Cargar opciones para invoice de endoso:
                  fn_payments.valid_financing($('#iFinanciamiento').val());
                  $('#endoso_invoice').show();
                    
                }else{
                    
                }
            },
            valid_financing : function(financing){
                if(financing == '1'){
                    $.ajax({             
                        type:"POST", 
                        url:"catalogos_generales.php", 
                        data:{accion:"get_financieras"},
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            if(data.error == '0'){$("#iConsecutivoFinanciera").empty().append(data.select); }
                        }
                    });
                    $('.financing_fields').show();  
                }
            },
            actualiza_totales : function(){
                $.ajax({
                    type: "POST",
                    url : "payments_server.php",
                    data: {
                        "accion"      : "actualiza_totales",
                        "iConsecutivo": $("#edit_form_payment input[name=iConsecutivo]").val(),
                        "dSubtotal"   : $("#edit_form_payment input[name=dSubtotal]").val(),
                        "dTax"        : $("#edit_form_payment input[name=dTax]").val(),
                        "dTotal"      : $("#edit_form_payment input[name=dTotal]").val(),
                        "dBalance"    : $("#edit_form_payment input[name=dBalance]").val(),
                        "domroot"     : "#invoice_detalle",
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){
                        if(data.error == "0"){eval(data.fields);}
                        else{
                            fn_solotrucking.mensaje(data.msj);
                            fn_popups.cerrar_ventana('edit_form_payment');    
                        }
                    }
                });
            },
            confirmar_eliminar : function(){
                $(fn_payments.data_grid + " tbody td .btn_delete").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(1)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    $("#dialog-confirm-delete-invoice input[name=iConsecutivo]").val(clave);
                    $("#dialog-confirm-delete-invoice").dialog('open');
                });    
            },
            eliminar : function(clave){
                if(clave != ""){
                    $.ajax({             
                        type:"POST", 
                        url:"payments_server.php", 
                        data:{'accion' : "delete_data",'iConsecutivo': clave,},
                        async : true,
                        dataType : "json",
                        success : function(data){ 
                            fn_solotrucking.mensaje(data.msj);
                            if(data.error == '0'){fn_payments.fillgrid();}    
                        }
                    });    
                }    
            },
            summary : {
               invoice_id : "", 
               company_id : "",
               data_grid  : "#invoice_detalle .popup-datagrid",
               form : "#edit_form_summary", 
               fillgrid: function(){
                   if(fn_payments.summary.invoice_id != ""){
                        $.ajax({             
                            type:"POST", 
                            url:"payments_server.php", 
                            data:{accion:"ps_get_dataset","iConsecutivoInvoice":fn_payments.summary.invoice_id},
                            async : true,
                            dataType : "json",
                            success : function(data){                               
                                $(fn_payments.summary.data_grid+" tbody").empty().append(data.tabla);
                                fn_payments.summary.edit();
                                fn_payments.summary.eliminar(); 
                            }
                        });     
                   }
               },
               add : function(){
                  $("#edit_form_summary input, #edit_form_summary select, #edit_form_summary textarea").val('').removeClass('error'); 
                  $("#edit_form_summary input:checkbox").prop('checked',false);
                  //Valores Default:
                  $("#edit_form_summary #iCantidad").val(1);
                  $("#edit_form_summary input[name=iConsecutivoInvoice]").val(fn_payments.summary.invoice_id);
                  
                  //Limpiar endorsement:
                  $("#edit_form_summary .data-endorsements").hide();
                  $("#edit_form_summary .data-endorsements #invoice_endorsement_grid tbody").empty().append('<tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr>');
                  
                  $("#edit_form_summary").show(); 
               },
               edit : function (){
                  $(fn_payments.summary.data_grid+" tbody td .edit").bind("click",function(){
                        var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                            clave = clave.split("_");
                            clave = clave[1];
                        
                        $.post("payments_server.php",{accion:"detalle_get_data", clave: clave, domroot : fn_payments.summary.form},
                        function(data){
                            if(data.error == '0'){
                               $("#edit_form_summary input, #edit_form_summary select, #edit_form_summary textarea").val('').removeClass('error'); 
                               eval(data.fields);
                               
                               fn_payments.summary.valida_endorsements();
                               $("#edit_form_summary").show();
                            }
                            else{fn_solotrucking.mensaje(data.msj);}       
                        },"json"); 
                  });  
               },
               cerrar_ventana : function(){
                   $("#edit_form_summary").hide(); 
               },
               get_services : function(){
                   $.ajax({             
                        type:"POST", 
                        url:"catalogos_generales.php", 
                        data:{accion:"get_services"},
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            if(data.error == '0'){$("#edit_form_summary #iConsecutivoServicio").empty().append(data.select);}
                        }
                   });  
               },
               get_service_data : function(){
                  var service = $("#edit_form_summary #iConsecutivoServicio").val();
                  if(service != ""){
                      $.ajax({             
                        type:"POST", 
                        url:"payments_server.php", 
                        data:{accion:"get_service_data","iConsecutivo":service,"domroot":"edit_form_summary"},
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            if(data.error == '0'){eval(data.fields);fn_payments.summary.get_total();}
                        }
                      });  
                  } 
               },
               get_total : function(){
                   var cantidad        = $("#edit_form_summary .detalle_formulario input[name=iCantidad]").val();
                   var iPrecioUnitario = $("#edit_form_summary .detalle_formulario input[name=iPrecioUnitario]").val();
                   var iPctImpuesto    = $("#edit_form_summary .detalle_formulario input[name=iPctImpuesto]").val();
                   
                   if(cantidad > 0 && iPrecioUnitario > 0){
                       cantidad        = fn_solotrucking.calcular_decimales(cantidad,2);
                       iPrecioUnitario = fn_solotrucking.calcular_decimales(iPrecioUnitario,2);
                       iPctImpuesto    = parseFloat(iPctImpuesto);
                       
                       var impXcan = fn_solotrucking.calcular_decimales((cantidad*iPrecioUnitario),2); //Importe Unit por Cantidad.
                       var tax     = fn_solotrucking.calcular_decimales((impXcan*(iPctImpuesto/100)),2); //Calcular Tax.
                       var total   = fn_solotrucking.calcular_decimales((impXcan+tax),2);
                       
                       $("#edit_form_summary .detalle_formulario input[name=iImpuesto]").val(tax);
                       $("#edit_form_summary .detalle_formulario input[name=iPrecioExtendido]").val(total);
                   }
               },
               save : function(ghost_mode){
                   
                   if(ghost_mode == '' || ghost_mode == undefined){ghost_mode = false;} 
                   
                   //Validate Fields:
                   var valid   = true;
                   var message = "";
                   $(fn_payments.summary.form+" .required-field").removeClass("error");
                   $(fn_payments.summary.form+" .required-field").each(function(){
                       if($(this).val() == ""){
                           valid   = false;
                           message = '<li> You must write all required fields.</li>';
                           $(this).addClass('error');
                       }
                   });
                   
                   //Validar si selecciono que aplica a endosos:
                   var endososApply   = $("#edit_form_summary #iEndorsementsApply");  
                   var endosos_select = '';
                   if(endososApply.is(':checked')){
                        $("#invoice_endorsement_added input[name=chk_endorsement_invoice]").each(function(){
                           if($(this).is(':checked')){
                               if(endosos_select == ""){endosos_select = $(this).val();}else{endosos_select += "|"+$(this).val();}
                           }
                       }); 
                       
                       if(endosos_select != ""){$("#edit_form_summary input[name=iConsecutivoEndosos]").val(endosos_select);}       
                   }
                   
                   if(valid){ 
                     if($(fn_payments.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
                        
                        struct_data_post.action  = "detalle_save_data";
                        struct_data_post.domroot = fn_payments.summary.form+" .detalle_formulario"; 
                        
                        $.post("payments_server.php",struct_data_post.parse(),
                        function(data){
                            switch(data.error){
                             case '0':
                                if(ghost_mode == false){
                                    fn_solotrucking.mensaje(data.msj);
                                    fn_payments.summary.fillgrid();
                                    $("#edit_form_summary input, #edit_form_summary select, #edit_form_summary textarea").val('').removeClass('error');
                                    $("#edit_form_summary .data-endorsements").hide();
                                    $("#edit_form_summary .data-endorsements #invoice_endorsement_grid tbody").empty().append('<tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr>');
                                    $("#edit_form_summary").hide(); 
                                    fn_payments.actualiza_totales();
                                }
                             break;
                             case '1': fn_solotrucking.mensaje(data.msj); break;
                            }        
                        },"json");
                        
                        if(ghost_mode && valid){return true;}
                   }
                   else{
                       fn_solotrucking.mensaje('<p>Please check the following:</p><ul style="padding: 10px;">'+message+'</ul>');
                   }    
               },
               eliminar : function(){
                $(fn_payments.summary.data_grid+" tbody td .trash").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    $.ajax({             
                        type:"POST", 
                        url:"payments_server.php", 
                        data:{
                            'accion' : "detalle_delete",
                            'iConsecutivoDetalle': clave,
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){ 
                            fn_solotrucking.mensaje(data.msj);
                                                       
                            if(data.error == '0'){
                                fn_payments.summary.fillgrid();  
                                fn_payments.actualiza_totales();   
                            }    
                        }
                    });    
                });    
               },
               //ENDOSOS
               valida_endorsements : function(){
                   var valida = $("#edit_form_summary #iEndorsementsApply");  
                   
                   if(valida.is(':checked')){
                       $("#edit_form_summary .data-endorsements").show();
                       //Revisamos si es edicion, consultar si ya tiene endosos almacenados:
                       if($(fn_payments.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val() != ''){
                           fn_payments.summary.endorsements.get_endorsements();
                       }
                       $("#edit_form_summary .data-endorsements select[name='iMostrarEndorsements']").val('0');
                   }
                   else{
                       $("#edit_form_summary .data-endorsements").hide();
                       $("#edit_form_summary .data-endorsements #invoice_endorsement_grid tbody").empty().append('<tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr>');
                   }
               },
               endorsements : {
                   filtro: "",
                   sort  : "DESC",
                   orden : "LEFT(A.dFechaAplicacion,10)",
                   data  : "#edit_form_endorsements .data-endorsements",
                   data_grid : "#edit_form_endorsements #invoice_endorsement_grid",
                   filtraInformacion : function(){
                        var valid = true;
                        fn_payments.summary.endorsements.filtro = ""; 
                        $(fn_payments.summary.endorsements.data+" .flt_endosos").removeClass("error");
                        
                        if($(fn_payments.summary.endorsements.data+" select[name=flt_tipo_endoso]").val() != ""){ 
                            fn_payments.summary.endorsements.filtro += "A.iConsecutivoTipoEndoso|"+$(fn_payments.summary.endorsements.data+" select[name=flt_tipo_endoso]").val()+",";
                        }else{valid = false;$(fn_payments.summary.endorsements.data+" select[name=flt_tipo_endoso]").addClass('error');}
                        
                        if($(fn_payments.summary.endorsements.data+" input[name=flt_dateFrom]").val() != ""){
                            fn_payments.summary.endorsements.filtro += "A.dFechaAplicacion|"+$(fn_payments.summary.endorsements.data+" input[name=flt_dateFrom]").val()+",";
                        }else{valid = false;$(fn_payments.summary.endorsements.data+" input[name=flt_dateFrom]").addClass('error');}
                        
                        if($(fn_payments.summary.endorsements.data+" input[name=flt_dateTo]").val() != ""){ 
                            fn_payments.summary.endorsements.filtro += "A.dFechaAplicacionF|"+$(fn_payments.summary.endorsements.data+" input[name=flt_dateTo]").val()+",";
                        }else{valid = false;$(fn_payments.summary.endorsements.data+" input[name=flt_dateTo]").addClass('error');}
                        
                        if(valid){
                            fn_payments.summary.endorsements.fillgrid();
                        }
                        else{fn_solotrucking.mensaje('Please write the all filter fields.');}    
                   },
                   fillgrid: function(){
                       $.ajax({             
                        type:"POST", 
                        url:"payments_server.php", 
                        data:{
                            'accion'              : "get_endorsements",
                            'filtroInformacion'   : fn_payments.summary.endorsements.filtro,  
                            'ordenInformacion'    : fn_payments.summary.endorsements.orden,
                            'sortInformacion'     : fn_payments.summary.endorsements.sort,
                            'iConsecutivoCompania': fn_payments.summary.company_id,
                            'iConsecutivoDetalle': $(fn_payments.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val()
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $(fn_payments.summary.endorsements.data_grid+" tbody").empty().append(data.tabla);
                        }
                    }); 
                   }, 
                   add : function(){
                      
                      valid = fn_payments.summary.save(true);
                      if(valid){
                          //Limpiar endorsement:
                          $(fn_payments.summary.endorsements.data_grid+" tbody").empty().append('<tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr>');
                          
                          var fechas = fn_solotrucking.obtener_fechas();
                          
                          //precargar filtros:
                          $(fn_payments.summary.endorsements.data+" select[name=flt_tipo_endoso]").val('0');
                          $(fn_payments.summary.endorsements.data+" input[name=flt_dateFrom]").val(fechas[1]); 
                          $(fn_payments.summary.endorsements.data+" input[name=flt_dateTo]").val(fechas[2]);
                          fn_payments.summary.endorsements.filtraInformacion(); 
                          
                          $("#edit_form_endorsements").show();     
                      } 
                      
                   }, 
                   // Cargar endosos ya guardados:
                   get_endorsements: function(){
                       $.ajax({             
                        type:"POST", 
                        url:"payments_server.php", 
                        data:{'accion' : "get_endorsements_added",'iConsecutivoDetalle': $(fn_payments.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val(),
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $("#invoice_endorsement_added tbody").empty().append(data.html); 
                            fn_payments.summary.endorsements.delete_endorsement(); 
                        }
                       }); 
                   }, 
                   delete_endorsement : function(){
                    $("#invoice_endorsement_added tbody td .btn_delete").bind("click",function(){
                        var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                            clave = clave.split("_");
                            clave = clave[1];
                        $.ajax({             
                            type:"POST", 
                            url:"payments_server.php", 
                            data:{
                                'accion' : "delete_endorsement_added",
                                'iConsecutivoDetalle': $(fn_payments.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val(),
                                'iConsecutivoEndoso' : clave,
                            },
                            async : true,
                            dataType : "json",
                            success : function(data){ 
                                fn_solotrucking.mensaje(data.msj);
                                                           
                                if(data.error == '0'){
                                    fn_payments.summary.endorsements.get_endorsements();     
                                }    
                            }
                        });    
                    });    
                   },
                   // guardar endosos seleccionados:
                   save : function(){
                       
                       //Validar si selecciono endosos:
                       $(fn_payments.summary.endorsements.data_grid+" input[name=chk_endorsement_invoice]").each(function(){
                           if($(this).is(':checked')){
                               var linehtml = $(this).parent().parent();
                               
                               $("#invoice_endorsement_added > tbody").append(linehtml);
                           }
                       }); 
                       
                       $("#edit_form_endorsements").hide();   
                   },
               },
            
            },  
            preview : function(){
                var clave = $("#edit_form_payment input[name=iConsecutivo]").val();
                if(clave != ''){
                    if(clave != ""){
                        window.open('pdf_invoice.php?id='+clave+'&ds=preview','_blank');
                    }     
                }
            },
    
           
    }     
    </script> 
    <div id="layer_content" class="main-section">
        <div id="ct_payments" class="container">
            <div class="page-title">
                <h1>Accounting</h1>
                <h2>PAYMENTS</h2>
            </div>
            <table id="data_grid_payments" class="data_grid">
            <thead>
                <tr id="grid-head1">
                    <td style='width:120px;'>
                        <input class="flt_ref" class="numeros" type="text" placeholder="No.#:">
                    </td>
                    <td><input class="flt_name" type="text" placeholder="Company:"></td> 
                    <td><input class="flt_date" type="text" placeholder="MM/DD/YY:"></td>
                    <td><input class="flt_desc" type="text" placeholder="Description:"></td>
                    <td><input class="flt_meth" type="text" placeholder="Pay Method:"></td>
                    <td><input class="flt_amount" type="text" placeholder="Amount:"></td> 
                    <td style='width:115px;'>
                        <div class="btn-icon-2 btn-left" title="Search" onclick="fn_payments.filtraInformacion();"><i class="fa fa-search"></i></div>
                        <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_payments.add();"><i class="fa fa-plus"></i></div>
                    </td> 
                </tr>
                <tr id="grid-head2">
                    <td class="etiqueta_grid" onclick="fn_payments.ordenamiento('sNoPago',this.cellIndex);">No. #</td>
                    <td class="etiqueta_grid"      onclick="fn_payments.ordenamiento('sNombreCompania',this.cellIndex);">Company</td>
                    <td class="etiqueta_grid down" onclick="fn_payments.ordenamiento('dFechaPago',this.cellIndex);">Date</td>
                    <td class="etiqueta_grid"      onclick="fn_payments.ordenamiento('sDescripcion',this.cellIndex);">Description</td> 
                    <td class="etiqueta_grid"      onclick="fn_payments.ordenamiento('sMetodoPago',this.cellIndex);">Pay Method</td>
                    <td class="etiqueta_grid"      onclick="fn_payments.ordenamiento('iMonto',this.cellIndex);">Amount</td>
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
                            <button id="pgn-inicio"    onclick="fn_payments.firstPage();" title="First page"><span></span></button>
                            <button id="pgn-anterior"  onclick="fn_payments.previousPage();" title="Previous"><span></span></button>
                            <button id="pgn-siguiente" onclick="fn_payments.nextPage();" title="Next"><span></span></button>
                            <button id="pgn-final"     onclick="fn_payments.lastPage();" title="Last Page"><span></span></button>
                        </div>
                    </td>
                </tr>
            </tfoot>
            </table>
            
        </div>
    </div>
    <!-- FORMULARIOS -->
    <div id="edit_form_payment" class="popup-form" style="height: 97%;width:80%;">
        <div class="p-header">
            <h2>INVOICE PAYMENT - (EDIT OR ADD)</h2>
            <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('edit_form_payment');"><i class="fa fa-times"></i></div>
        </div>
        <div class="p-container" style="height: 94%;overflow-y: auto;">
        <div>
            <form>
                <fieldset style="padding-bottom: 5px;">
                <legend>Payment General Data</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <table class="detalle_formulario" style="width: 100%;">
                    <tr>
                        <td>
                        <input name="iConsecutivoPago" type="hidden" value="">
                        <div class="field_item"> 
                            <label for="sNoPago">No. Payment <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="1" name="sNoPago" class="required-field txt-uppercase" type="text" value="" style="width: 97%;">
                        </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label for="dFechaPago">Date <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="2" id="dFechaPago" name="dFechaPago" class="required-field fecha" type="text" value="" style="width:90%!important;">
                        </div>
                        </td> 
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item">
                            <label>Company <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="3" name="iConsecutivoCompania" class="required-field" style="width: 98%!important;height: 25px!important;"><option value="">Select an option...</option></select>
                        </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Payment Currency <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="4" name="sCveMoneda" class="required-field" style="height: 25px!important;"><option value="USD" selected>USD</option></select> 
                        </div>
                        </td> 
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item"> 
                            <label for="sDescripcion">Description <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="5" name="sDescripcion" class="required-field txt-uppercase" type="text" value="">
                        </div>
                        </td> 
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item"> 
                            <label>Payment Method <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="6" name="sMetodoPago" class="required-field txt-uppercase" type="text" value="" style="width: 97%;"> 
                        </div>
                        </td> 
                        <td>
                        <div class="field_item">
                            <label>Amount <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="7" name="iMonto" class="required-field inputdecimals" type="text" value="" style="width: 97%;" onchange="fn_payments.payments.get_saldos();">
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item"> 
                                <label>File to upload (PDF,JPEG,JPG,PNG):</label>
                                <div class="file-container">
                                    <input id="fileselect" name="fileselect" type="file" style="height: 90px!important;"/>
                                    <div class="file-message"></div>
                                </div>
                            </div> 
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Comments:</label> 
                            <textarea tabindex="8" name="sComentarios" style="height:50px!important;resize:none;"></textarea>
                        </div>
                        </td> 
                    </tr>
                </table>
                </fieldset>
            </form>
            <div>
                <button type="button" class="btn-1" onclick="fn_payments.save();" style="">SAVE</button>          
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('edit_form_payment');" style="margin-right:10px;background:#e8051b;">Cancel</button> 
            </div>
        </div>
        </div>
    </div>
    
    <!-- ADD ENDORSEMENTS -->
    <div id="edit_form_endorsements" class="popup-form" style="height: 97%;width:80%;">
        <div class="p-header">
            <h2>INVOICE SUMMARY - (EDIT OR ADD)</h2>
            <div class="btn-close" title="Close Window" onclick="$('#edit_form_endorsements').hide();"><i class="fa fa-times"></i></div>
        </div>
        <div class="p-container" style="height: 94%;overflow-y: auto;">
        <div>
            <form>
                <fieldset style="padding-bottom: 5px;">
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <legend class="data-endorsements">Endorsements data</legend>
                <table style="width: 100%;" cellpadding="0" cellspacing="0" class="data-endorsements">
                <tr>
                    <td style="width:40%;">
                    <div class="field_item">
                        <label>Type <span style="color:#ff0000;">*</span>:</label> 
                        <select name="flt_tipo_endoso" style="height:25px!important;" class="flt_endosos">
                            <option value="">Select an option...</option>
                            <option value="0">Both</option>
                            <option value="1">Vehicles</option>
                            <option value="2">Drivers</option>
                        </select>
                    </div>
                    </td> 
                    <td style="width: 60%;padding-left: 10px;">
                    <div class="field_item">
                        <label>Filter by date <span style="color:#ff0000;">*</span>:</label> 
                        <div style="float: left;width: 90%;">
                            <label class="check-label" style="position: relative;top: 0px;">From</label><input name="flt_dateFrom" type="text"  placeholder="MM/DD/YY" style="width: 150px;" class="flt_endosos fecha">
                            <label class="check-label" style="position: relative;top: 0px;">To</label><input   name="flt_dateTo"   type="text"  placeholder="MM/DD/YY" style="width: 150px;" class="flt_endosos fecha">
                        </div>
                        <div class="btn_pdf btn-icon pdf btn-right" title="filter endorsements" onclick="fn_payments.summary.endorsements.filtraInformacion();"><i class="fa fa-search"></i></div>
                    </div>
                    </td> 
                </tr>
                <tr> 
                    <td colspan="2">
                    <table id="invoice_endorsement_grid" class="popup-datagrid" style="width: 100%;margin-top: 10px;margin-bottom: 10px;" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr id="grid-head2">
                                <td class="etiqueta_grid" style="width: 40%;">type / Description</td>
                                <td class="etiqueta_grid" style="width: 30%;">
                                    <span style="display: -webkit-inline-box;width: 60%;">Policy</span>
                                    <span style="display: -webkit-inline-box;width: 38%;">END No.</span>
                                </td>
                                <td class="etiqueta_grid">APP Date</td>
                                <td class="etiqueta_grid">Status</td>
                                <td class="etiqueta_grid"></td> 
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                        <tfoot></tfoot>
                    </table>
                    </td>
                </tr>
                </table> 
                </fieldset>
            </form>
            <div>
                <button type="button" class="btn-1" onclick="fn_payments.summary.endorsements.save();" style="">SAVE</button>          
                <button type="button" class="btn-1" onclick="$('#edit_form_endorsements').hide();" style="margin-right:10px;background:#e8051b;">Cancel</button> 
            </div>
        </div>
        </div>
    </div>
    <!-- DIALOGUES -->
    <div id="dialog-confirm-delete-invoice" title="Delete">
      <p><span class="ui-icon ui-icon-alert" ></span>Are you sure you want to delete the payment?</p>
      <input value="" type="hidden" name="iConsecutivo">
    </div>

    </div>
    <!-- FOOTER -->
    <?php include("footer.php"); ?> 
    </body>
</html>
<?php } ?>
