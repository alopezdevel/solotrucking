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
            fn_invoices.init();
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
                        fn_invoices.eliminar(clave);
                        $(this).dialog('close');
                    },
                    'CANCEL' : function(){$(this).dialog('close');}
                }
            });
        
    }  
    function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
    var fn_invoices = {
            domroot:"#ct_invoices",
            data_grid: "#data_grid_invoices",
            form : "#invoice_data",
            filtro : "",
            pagina_actual : "",
            sort : "ASC",
            orden : "A.iConsecutivo",
            init : function(){
                fn_invoices.fillgrid();
                $('.num').keydown(fn_solotrucking.inputnumero);
                $('.inputdecimals').keydown(fn_solotrucking.inputdecimals); 
                //Filtrado con la tecla enter
                $(fn_invoices.data_grid + ' #grid-head1 input').keyup(function(event){
                    if (event.keyCode == '13') {
                        event.preventDefault();
                        fn_invoices.filtraInformacion();
                    }
                    if(event.keyCode == '27'){
                       event.preventDefault();
                       $(this).val(''); 
                       fn_invoices.filtraInformacion();
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
                        if(data.error == '0'){$("#edit_form_invoice #iConsecutivoCompania, #edit_form_payment select[name=iConsecutivoCompania]").empty().append(data.select); }
                    }
                });
                
                //Archivos:
                if(window.File && window.FileList && window.FileReader) {
                      fn_solotrucking.files.form      = "#edit_form_invoice";
                      fn_solotrucking.files.fileinput = "fileselect";
                      fn_solotrucking.files.add();
                }
                
            },
            fillgrid: function(){
                   $.ajax({             
                    type:"POST", 
                    url:"funciones_invoices.php", 
                    data:{
                        accion:"get_invoices",
                        registros_por_pagina : "15", 
                        pagina_actual : fn_invoices.pagina_actual, 
                        filtroInformacion : fn_invoices.filtro,  
                        ordenInformacion : fn_invoices.orden,
                        sortInformacion : fn_invoices.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_invoices.data_grid+" tbody").empty().append(data.tabla);
                        $(fn_invoices.data_grid+" tbody tr:even").addClass('gray');
                        $(fn_invoices.data_grid+" tbody tr:odd").addClass('white');
                        $(fn_invoices.data_grid + " tfoot #paginas_total").val(data.total);
                        $(fn_invoices.data_grid + " tfoot #pagina_actual").val(data.pagina);
                        fn_invoices.pagina_actual = data.pagina; 
                        fn_invoices.edit();
                        fn_invoices.confirmar_eliminar();
                        fn_solotrucking.btn_tooltip();
                    }
                }); 
            },
            add : function(){
               $('#edit_form_invoice :text, #edit_form_invoice select').val('').removeClass('error');
               $('#edit_form_invoice .mensaje_valido').empty().append('The fields containing an (<span style="color:#ff0000;">*</span>) are required.');
               $('#edit_form_invoice .p-header h2').empty().append('INVOICES - NEW INVOICE');
               $(fn_invoices.form+' #sNoReferencia').removeProp('readonly').removeClass("readonly");
               $(fn_invoices.form+' button.btn-preview').hide();
               $(fn_invoices.form+" #sCveMoneda").val('USD');
               fn_invoices.summary.get_services(); 
               //ocultar datagrid detalle:
               $("#invoice_detalle").hide();
               $(fn_invoices.summary.data_grid+" tbody").empty();
               
               fn_solotrucking.get_date("#dFechaInvoice");
               fn_popups.resaltar_ventana('edit_form_invoice');
               
               //Limpiar campo file:
               $(fn_invoices.form+" .file-message").html("");
               $(fn_invoices.form+" #fileselect").val(null);
               $(fn_invoices.form+" #fileselect").removeClass("fileupload");  
            },
            edit : function (){
                $(fn_invoices.data_grid + " tbody td .edit").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(1)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    
                    $('#edit_form_invoice .p-header h2').empty().append('INVOICES - EDIT INVOICE #' + $(this).parent().parent().find("td:eq(1)").html()); 
                    fn_invoices.get_data(clave);
                }); 
              
                $(fn_invoices.data_grid + " tbody td .btn_apply").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(1)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    
                    $.post("funciones_invoices.php",{accion:"apply_data", clave: clave},
                    function(data){
                        fn_solotrucking.mensaje(data.msj);
                        if(data.error == '0'){fn_invoices.filtraInformacion();}
                    },"json"); 
                }); 
                
                $(fn_invoices.data_grid + " tbody td .btn_pdf").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(1)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                
                        if(clave != ""){
                            window.open('pdf_invoice.php?id='+clave+'&ds=view','_blank');
                        }  
                }); 
               
                $(fn_invoices.data_grid + " tbody .btn_send_invoice").bind("click",function(){
                       var clave = $(this).parent().parent().find("td:eq(1)").prop('id');
                           clave = clave.split("_");
                           clave = clave[1];
                       var msg   = "<p style=\"text-align:center;\">Sending invoice by e-mail, please wait....<br><img src=\"images/ajax-loader.gif\" alt=\"ajax-loader.gif\" style=\"margin-top:10px;\"><br></p>";
                       
                       $('#Wait').empty().append(msg).dialog('open');
                       $.post("funciones_invoices.php",{accion:"send_email_gmail", clave: clave},
                        function(data){ 
                            $('#Wait').empty().dialog('close');
                            fn_solotrucking.mensaje(data.msj);
                            if(data.error == '0'){fn_invoices.filtraInformacion();}     
                        },"json");
                }); 
                
                $(fn_invoices.data_grid + " tbody td .btn_payments").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(1)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    
                    fn_invoices.payments.iConsecutivoInvoice = clave;
                    fn_invoices.payments.init();
                    
                });
            }, 
            get_data : function(clave){
                if(clave != ""){
                    $.post("funciones_invoices.php",
                    {accion:"get_data", clave: clave, domroot : fn_invoices.form},
                    function(data){
                        if(data.error == '0'){
                           $('#edit_form_invoice :text, #edit_form_invoice select').val('').removeClass('error'); 
                           $(fn_invoices.form+' #sNoReferencia').prop('readonly','readonly').addClass("readonly");
                           $(fn_invoices.form+' button.btn-preview').show();
                           
                           //Limpiar campo file:
                           $(fn_invoices.form+" .file-message").html("");
                           $(fn_invoices.form+" #fileselect").val(null);
                           $(fn_invoices.form+" #fileselect").removeClass("fileupload");
                           
                           eval(data.fields);
                           
                           fn_invoices.actualiza_totales();
                           
                           //Inicializar datagrid de productos y servicios:
                           fn_invoices.summary.invoice_id =  $(fn_invoices.form+' #iConsecutivo').val();
                           fn_invoices.summary.company_id =  $(fn_invoices.form+' #iConsecutivoCompania').val();
                           fn_invoices.summary.fillgrid();
                           fn_invoices.summary.get_services(); 
                
                           $("#invoice_detalle").show();
                           fn_popups.resaltar_ventana('edit_form_invoice');
                        }
                        else{fn_solotrucking.mensaje(data.msj);}       
                    },"json"); 
                }    
            },
            save : function (){
               
               //Validate Fields:
               var valid = true;
               var message = "";
               $(fn_invoices.form+" .required-field").removeClass("error");
               $(fn_invoices.form+" .required-field").each(function(){
                   if($(this).val() == ""){
                       valid   = false;
                       message = '<li> You must write all required fields.</li>';
                       $(this).addClass('error');
                   }
               });
               
               if(valid){
                   
                    var form       = "#invoice_data";
                    var dataForm   = new FormData();
                    var other_data = $(form).serializeArray();
                    dataForm.append('accion','save_data');
                    $.each($(form+' input[type=file]')[0].files,function(i, file){dataForm.append('file-'+i, file);});
                    $.each(other_data,function(key,input){dataForm.append(input.name,input.value);});
                    
                    $.ajax({
                      type: "POST",
                      url : "funciones_invoices.php",
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
                                $(fn_invoices.form+' #iConsecutivoCompania').val(data.idFactura);
                                fn_invoices.summary.company_id =  $(fn_invoices.form+' #iConsecutivoCompania').val();
                                fn_invoices.actualiza_totales();
                                
                                fn_invoices.filtraInformacion();
                                
                                fn_invoices.get_data(data.idFactura);
                              break;
                              case '1': fn_solotrucking.mensaje(data.msj); break;
                          }
                      }
                    });  
                    
               }
               else{fn_solotrucking.mensaje('<p>Please check the following:</p><ul style="padding: 10px;">'+message+'</ul>');}
            },
            firstPage : function(){
                if($(fn_invoices.data_grid+" #pagina_actual").val() != "1"){
                    fn_invoices.pagina_actual = "";
                    fn_invoices.fillgrid();
                }
            },
            previousPage : function(){
                if($(fn_invoices.data_grid+" #pagina_actual").val() != "1"){
                    fn_invoices.pagina_actual = (parseInt($(fn_invoices.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_invoices.fillgrid();
                }
            },
            nextPage : function(){
                if($(fn_invoices.data_grid+" #pagina_actual").val() != $(fn_invoices.data_grid+" #paginas_total").val()){
                    fn_invoices.pagina_actual = (parseInt($(fn_invoices.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_invoices.fillgrid();
                }
            },
            lastPage : function(){
                if($(fn_invoices.data_grid+" #pagina_actual").val() != $(fn_invoices.data_grid+" #paginas_total").val()){
                    fn_invoices.pagina_actual = $(fn_invoices.data_grid+" #paginas_total").val();
                    fn_invoices.fillgrid();
                }
            }, 
            ordenamiento : function(campo,objeto){
                $(fn_invoices.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_invoices.orden){
                    if(fn_invoices.sort == "ASC"){
                        fn_invoices.sort = "DESC";
                        $(fn_invoices.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_invoices.sort = "ASC";
                        $(fn_invoices.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_invoices.sort = "ASC";
                    fn_invoices.orden = campo;
                    $(fn_invoices.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_invoices.fillgrid();

                return false;
            }, 
            filtraInformacion : function(){
                fn_invoices.pagina_actual = 0;
                fn_invoices.filtro = "";
                if($(fn_invoices.data_grid+" .flt_ref").val() != "")   { fn_invoices.filtro += "sNoReferencia|"+$(fn_invoices.data_grid+" .flt_ref").val()+","}
                if($(fn_invoices.data_grid+" .flt_name").val() != "")  { fn_invoices.filtro += "sNombreCompania|"+$(fn_invoices.data_grid+" .flt_name").val()+","}  
                if($(fn_invoices.data_grid+" .flt_date").val() != "")  { fn_invoices.filtro += "dFechaInvoice|"+$(fn_invoices.data_grid+" .flt_date").val()+","}  
                if($(fn_invoices.data_grid+" .flt_amount").val() != ""){ fn_invoices.filtro += "dTotal|"+$(fn_invoices.data_grid+" .flt_amount").val()+","}    
                fn_invoices.fillgrid();
            },
            valid_type : function(service){
                if(service == '1'){ //ENDORSEMENTS:
                  // 1 - Cargar opciones para invoice de endoso:
                  fn_invoices.valid_financing($('#iFinanciamiento').val());
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
                    url : "funciones_invoices.php",
                    data: {
                        "accion"      : "actualiza_totales",
                        "iConsecutivo": $("#edit_form_invoice input[name=iConsecutivo]").val(),
                        "dSubtotal"   : $("#edit_form_invoice input[name=dSubtotal]").val(),
                        "dTax"        : $("#edit_form_invoice input[name=dTax]").val(),
                        "dTotal"      : $("#edit_form_invoice input[name=dTotal]").val(),
                        "dBalance"    : $("#edit_form_invoice input[name=dBalance]").val(),
                        "domroot"     : "#invoice_detalle",
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){
                        if(data.error == "0"){eval(data.fields);}
                        else{
                            fn_solotrucking.mensaje(data.msj);
                            fn_popups.cerrar_ventana('edit_form_invoice');    
                        }
                    }
                });
            },
            confirmar_eliminar : function(){
                $(fn_invoices.data_grid + " tbody td .btn_delete").bind("click",function(){
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
                        url:"funciones_invoices.php", 
                        data:{'accion' : "delete_data",'iConsecutivo': clave,},
                        async : true,
                        dataType : "json",
                        success : function(data){ 
                            fn_solotrucking.mensaje(data.msj);
                            if(data.error == '0'){fn_invoices.fillgrid();}    
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
                   if(fn_invoices.summary.invoice_id != ""){
                        $.ajax({             
                            type:"POST", 
                            url:"funciones_invoices.php", 
                            data:{accion:"ps_get_dataset","iConsecutivoInvoice":fn_invoices.summary.invoice_id},
                            async : true,
                            dataType : "json",
                            success : function(data){                               
                                $(fn_invoices.summary.data_grid+" tbody").empty().append(data.tabla);
                                fn_invoices.summary.edit();
                                fn_invoices.summary.eliminar(); 
                            }
                        });     
                   }
               },
               add : function(){
                  $("#edit_form_summary input, #edit_form_summary select, #edit_form_summary textarea").val('').removeClass('error'); 
                  $("#edit_form_summary input:checkbox").prop('checked',false);
                  //Valores Default:
                  $("#edit_form_summary #iCantidad").val(1);
                  $("#edit_form_summary input[name=iConsecutivoInvoice]").val(fn_invoices.summary.invoice_id);
                  
                  //Limpiar endorsement:
                  $("#edit_form_summary .data-endorsements").hide();
                  $("#edit_form_summary .data-endorsements #invoice_endorsement_grid tbody").empty().append('<tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr>');
                  
                  $("#edit_form_summary").show(); 
               },
               edit : function (){
                  $(fn_invoices.summary.data_grid+" tbody td .edit").bind("click",function(){
                        var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                            clave = clave.split("_");
                            clave = clave[1];
                        
                        $.post("funciones_invoices.php",{accion:"detalle_get_data", clave: clave, domroot : fn_invoices.summary.form},
                        function(data){
                            if(data.error == '0'){
                               $("#edit_form_summary input, #edit_form_summary select, #edit_form_summary textarea").val('').removeClass('error'); 
                               eval(data.fields);
                               
                               fn_invoices.summary.valida_endorsements();
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
                        url:"funciones_invoices.php", 
                        data:{accion:"get_service_data","iConsecutivo":service,"domroot":"edit_form_summary"},
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            if(data.error == '0'){eval(data.fields);fn_invoices.summary.get_total();}
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
                   $(fn_invoices.summary.form+" .required-field").removeClass("error");
                   $(fn_invoices.summary.form+" .required-field").each(function(){
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
                     if($(fn_invoices.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
                        
                        struct_data_post.action  = "detalle_save_data";
                        struct_data_post.domroot = fn_invoices.summary.form+" .detalle_formulario"; 
                        
                        $.post("funciones_invoices.php",struct_data_post.parse(),
                        function(data){
                            switch(data.error){
                             case '0':
                                if(ghost_mode == false){
                                    fn_solotrucking.mensaje(data.msj);
                                    fn_invoices.summary.fillgrid();
                                    $("#edit_form_summary input, #edit_form_summary select, #edit_form_summary textarea").val('').removeClass('error');
                                    $("#edit_form_summary .data-endorsements").hide();
                                    $("#edit_form_summary .data-endorsements #invoice_endorsement_grid tbody").empty().append('<tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr>');
                                    $("#edit_form_summary").hide(); 
                                    fn_invoices.actualiza_totales();
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
                $(fn_invoices.summary.data_grid+" tbody td .trash").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    $.ajax({             
                        type:"POST", 
                        url:"funciones_invoices.php", 
                        data:{
                            'accion' : "detalle_delete",
                            'iConsecutivoDetalle': clave,
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){ 
                            fn_solotrucking.mensaje(data.msj);
                                                       
                            if(data.error == '0'){
                                fn_invoices.summary.fillgrid();  
                                fn_invoices.actualiza_totales();   
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
                       if($(fn_invoices.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val() != ''){
                           fn_invoices.summary.endorsements.get_endorsements();
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
                        fn_invoices.summary.endorsements.filtro = ""; 
                        $(fn_invoices.summary.endorsements.data+" .flt_endosos").removeClass("error");
                        
                        if($(fn_invoices.summary.endorsements.data+" select[name=flt_tipo_endoso]").val() != ""){ 
                            fn_invoices.summary.endorsements.filtro += "A.iConsecutivoTipoEndoso|"+$(fn_invoices.summary.endorsements.data+" select[name=flt_tipo_endoso]").val()+",";
                        }else{valid = false;$(fn_invoices.summary.endorsements.data+" select[name=flt_tipo_endoso]").addClass('error');}
                        
                        if($(fn_invoices.summary.endorsements.data+" input[name=flt_dateFrom]").val() != ""){
                            fn_invoices.summary.endorsements.filtro += "A.dFechaAplicacion|"+$(fn_invoices.summary.endorsements.data+" input[name=flt_dateFrom]").val()+",";
                        }else{valid = false;$(fn_invoices.summary.endorsements.data+" input[name=flt_dateFrom]").addClass('error');}
                        
                        if($(fn_invoices.summary.endorsements.data+" input[name=flt_dateTo]").val() != ""){ 
                            fn_invoices.summary.endorsements.filtro += "A.dFechaAplicacionF|"+$(fn_invoices.summary.endorsements.data+" input[name=flt_dateTo]").val()+",";
                        }else{valid = false;$(fn_invoices.summary.endorsements.data+" input[name=flt_dateTo]").addClass('error');}
                        
                        if(valid){
                            fn_invoices.summary.endorsements.fillgrid();
                        }
                        else{fn_solotrucking.mensaje('Please write the all filter fields.');}    
                   },
                   fillgrid: function(){
                       $.ajax({             
                        type:"POST", 
                        url:"funciones_invoices.php", 
                        data:{
                            'accion'              : "get_endorsements",
                            'filtroInformacion'   : fn_invoices.summary.endorsements.filtro,  
                            'ordenInformacion'    : fn_invoices.summary.endorsements.orden,
                            'sortInformacion'     : fn_invoices.summary.endorsements.sort,
                            'iConsecutivoCompania': fn_invoices.summary.company_id,
                            'iConsecutivoDetalle': $(fn_invoices.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val()
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $(fn_invoices.summary.endorsements.data_grid+" tbody").empty().append(data.tabla);
                        }
                    }); 
                   }, 
                   add : function(){
                      
                      valid = fn_invoices.summary.save(true);
                      if(valid){
                          //Limpiar endorsement:
                          $(fn_invoices.summary.endorsements.data_grid+" tbody").empty().append('<tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr>');
                          
                          var fechas = fn_solotrucking.obtener_fechas();
                          
                          //precargar filtros:
                          $(fn_invoices.summary.endorsements.data+" select[name=flt_tipo_endoso]").val('0');
                          $(fn_invoices.summary.endorsements.data+" input[name=flt_dateFrom]").val(fechas[1]); 
                          $(fn_invoices.summary.endorsements.data+" input[name=flt_dateTo]").val(fechas[2]);
                          fn_invoices.summary.endorsements.filtraInformacion(); 
                          
                          $("#edit_form_endorsements").show();     
                      } 
                      
                   }, 
                   // Cargar endosos ya guardados:
                   get_endorsements: function(){
                       $.ajax({             
                        type:"POST", 
                        url:"funciones_invoices.php", 
                        data:{'accion' : "get_endorsements_added",'iConsecutivoDetalle': $(fn_invoices.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val(),
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $("#invoice_endorsement_added tbody").empty().append(data.html); 
                            fn_invoices.summary.endorsements.delete_endorsement(); 
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
                            url:"funciones_invoices.php", 
                            data:{
                                'accion' : "delete_endorsement_added",
                                'iConsecutivoDetalle': $(fn_invoices.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val(),
                                'iConsecutivoEndoso' : clave,
                            },
                            async : true,
                            dataType : "json",
                            success : function(data){ 
                                fn_solotrucking.mensaje(data.msj);
                                                           
                                if(data.error == '0'){
                                    fn_invoices.summary.endorsements.get_endorsements();     
                                }    
                            }
                        });    
                    });    
                   },
                   // guardar endosos seleccionados:
                   save : function(){
                       
                       //Validar si selecciono endosos:
                       $(fn_invoices.summary.endorsements.data_grid+" input[name=chk_endorsement_invoice]").each(function(){
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
                var clave = $("#edit_form_invoice input[name=iConsecutivo]").val();
                if(clave != ''){
                    if(clave != ""){
                        window.open('pdf_invoice.php?id='+clave+'&ds=preview','_blank');
                    }     
                }
            },
            //PAYMENTS
            payments : {
                iConsecutivoInvoice : "",
                form     : "edit_form_payment",
                form_add : "#edit_form_payment_detalle",
                data_grid: "#payments_detalle",
                init : function(){
                    if(fn_invoices.payments.iConsecutivoInvoice != ""){
                        
                        $.post("funciones_invoices.php",
                        {
                            accion :"payment_invoice_getdata", 
                            clave  : fn_invoices.payments.iConsecutivoInvoice, 
                            domroot: fn_invoices.payments.form
                        },
                        function(data){
                            if(data.error == '0'){
                               $('#'+fn_invoices.payments.form+' :text, #'+fn_invoices.payments.form+' select').val(''); 
                               eval(data.fields);
                               fn_invoices.payments.fillgrid();
                               fn_popups.resaltar_ventana('edit_form_payment');
                            }
                            else{fn_solotrucking.mensaje(data.msj);}       
                        },"json"); 
                           
                    }
                },
                fillgrid: function(){
                   if(fn_invoices.payments.iConsecutivoInvoice != ""){
                        $.ajax({             
                            type:"POST", 
                            url:"funciones_invoices.php", 
                            data:{accion:"payment_getdata","iConsecutivoInvoice":fn_invoices.payments.iConsecutivoInvoice},
                            async : true,
                            dataType : "json",
                            success : function(data){                               
                                $(fn_invoices.payments.data_grid+" tbody").empty().append(data.tabla);
                                //fn_invoices.summary.edit();
                                //fn_invoices.summary.eliminar(); 
                            }
                        });     
                   }
               },
                add : function(){
                      $("#edit_form_payment_detalle input, #edit_form_payment_detalle select, #edit_form_payment_detalle textarea").val('').removeClass('error'); 
                      
                      //Valores Default:
                      fn_solotrucking.get_date("#dFechaPago");
                      $("#edit_form_payment_detalle input[name=iConsecutivoInvoice]").val(fn_invoices.summary.invoice_id);
                      $("#edit_form_payment_detalle input[name=iMonto]").val(0);
                      $("#edit_form_payment_detalle input[name=iSaldoAnterior]").val(parseFloat($("#"+fn_invoices.payments.form+" input[name=iBalanceOutstanding]").val()).toFixed(2));
                      $("#edit_form_payment_detalle input[name=iSaldoPendiente]").val(parseFloat($("#"+fn_invoices.payments.form+" input[name=iBalanceOutstanding]").val()).toFixed(2));

                      $("#edit_form_payment_detalle").show(); 
                },
                cerrar_ventana : function(){
                   $("#edit_form_payment_detalle").hide(); 
                },
                save : function(ghost_mode){
                   
                   if(ghost_mode == '' || ghost_mode == undefined){ghost_mode = false;} 
                   
                   //Validate Fields:
                   var valid   = true;
                   var message = "";
                   $(fn_invoices.payments.form_add+" .required-field").removeClass("error");
                   $(fn_invoices.payments.form_add+" .required-field").each(function(){
                       if($(this).val() == ""){
                           valid   = false;
                           message = '<li> You must write all required fields.</li>';
                           $(this).addClass('error');
                       }
                   });
                   
                   
                   if(valid){ 
                     if($(fn_invoices.payments.form_add+' input[name=iConsecutivoPago]').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
                        
                        struct_data_post.action  = "payment_save_data";
                        struct_data_post.domroot = fn_invoices.payments.form_add; 
                        
                        $.post("funciones_invoices.php",struct_data_post.parse(),
                        function(data){
                            switch(data.error){
                             case '0':
                                if(ghost_mode == false){
                                    fn_solotrucking.mensaje(data.msj);
                                    $("#edit_form_payment_detalle input, #edit_form_payment_detalle select, #edit_form_payment_detalle textarea").val('').removeClass('error');
                                    $("#edit_form_payment_detalle").hide(); 
                                    fn_invoices.payments.init();
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
                get_saldos : function(){
                
                   var monto_aplicado = parseFloat($("#edit_form_payment_detalle input[name=iMonto]").val());
                   
                   if(monto_aplicado == "" || monto_aplicado == null){
                      $("#edit_form_payment_detalle input[name=iMonto]").val(0); 
                      monto_aplicado = 0;
                   } 
                   var saldo_anterior = parseFloat($("#edit_form_payment_detalle input[name=iSaldoAnterior]").val());  
                   var saldo_pendiente= saldo_anterior - monto_aplicado;
                   $("#edit_form_payment_detalle input[name=iSaldoPendiente]").val(parseFloat(saldo_pendiente).toFixed(2));
                   
                }
            }
    }     
    </script> 
    <div id="layer_content" class="main-section">
        <div id="ct_invoices" class="container">
            <div class="page-title">
                <h1>Accounting</h1>
                <h2>Invoices</h2>
            </div>
            <table id="data_grid_invoices" class="data_grid">
            <thead>
                <tr id="grid-head1">
                    <td class="etiqueta_grid" style="width: 100px;"></td>
                    <td style='width:120px;'>
                        <input class="flt_ref" class="numeros" type="text" placeholder="No.#:">
                    </td>
                    <td><input class="flt_name" type="text" placeholder="Company:"></td> 
                    <td><input class="flt_date" type="text" placeholder="MM/DD/YY:"></td>
                    <td><input class="flt_amount" type="text" placeholder="Total:"></td> 
                    <td style='width:120px;'>
                        <div class="btn-icon-2 btn-left" title="Search" onclick="fn_invoices.filtraInformacion();"><i class="fa fa-search"></i></div>
                        <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_invoices.add();"><i class="fa fa-plus"></i></div>
                    </td> 
                </tr>
                <tr id="grid-head2">
                    <td class="etiqueta_grid"></td>
                    <td class="etiqueta_grid down" onclick="fn_invoices.ordenamiento('sNoReferencia',this.cellIndex);">No. #</td>
                    <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('sNombreCompania',this.cellIndex);">Company</td>
                    <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('A.dFechaInvoice',this.cellIndex);">Date</td> 
                    <td class="etiqueta_grid"      onclick="fn_invoices.ordenamiento('dTotal',this.cellIndex);">Total</td>
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
                            <button id="pgn-inicio"    onclick="fn_invoices.firstPage();" title="First page"><span></span></button>
                            <button id="pgn-anterior"  onclick="fn_invoices.previousPage();" title="Previous"><span></span></button>
                            <button id="pgn-siguiente" onclick="fn_invoices.nextPage();" title="Next"><span></span></button>
                            <button id="pgn-final"     onclick="fn_invoices.lastPage();" title="Last Page"><span></span></button>
                        </div>
                    </td>
                </tr>
            </tfoot>
            </table>
            
        </div>
    </div>
    <!-- FORMULARIOS -->
    <div id="edit_form_invoice" class="popup-form" style="height: 97%;width:80%;">
        <div class="p-header">
            <h2>INVOICES - (EDIT OR ADD)</h2>
            <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('edit_form_invoice');fn_invoices.filtraInformacion();"><i class="fa fa-times"></i></div>
        </div>
        <div class="p-container" style="height: 94%;overflow-y: auto;">
        <div>
            <form id="invoice_data">
                <fieldset style="padding-bottom: 5px;">
                <legend>General Data</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <input id="iConsecutivo" name="iConsecutivo" type="hidden" value=""> 
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 60%;">
                        <div class="field_item"> 
                            <label for="sNoReferencia">No. # <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="1" id="sNoReferencia" name="sNoReferencia" type="text" class="txt-uppercase required-field" style="width: 97%;">
                        </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Invoice Date <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="2" id="dFechaInvoice" name="dFechaInvoice" type="text" class="fecha required-field" style="width: 90%;position: relative;margin-left:15px;">
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item">
                            <label>Company <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="3" id="iConsecutivoCompania" name="iConsecutivoCompania" class="required-field" style="width: 98%!important;height: 25px!important;"><option value="">Select an option...</option></select>
                        </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Payment Currency <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="4" id="sCveMoneda" name="sCveMoneda" class="required-field" style="height: 25px!important;"><option value="USD" selected>USD</option></select> 
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
                            <textarea tabindex="6" id="sComentarios" name="sComentarios" style="height:50px!important;"></textarea>
                        </div>
                        </td> 
                    </tr>
                    <!--
                    <tr>
                        <td style="width:70%;"> 
                        <div class="field_item"> 
                            <label>Service or Product: <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="3" id="iTipoInvoice" onblur="fn_invoices.valid_type(this.value);"><option value="">Select an option...</option></select>
                        </div>
                        </td> 
                        <td>
                        <div class="field_item"> 
                            <label>Number: <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="4" id="sNumber" type="text" maxlength="6" class="num">
                        </div>
                        </td> 
                    </tr>
                    <tr>
                        <td style="width:70%;"></td> 
                        <td> 
                        <div class="field_item"> 
                            <label>Invoice Amount: <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="5" id="iAmount" type="text" maxlength="10" class="inputdecimals" placeholder="$:">
                        </div>
                        </td>
                    </tr> -->
                </table>
                </fieldset>
                <!-- BEGIN ENDORSEMENT INVOICE  
                <fieldset id="endoso_invoice" style="display:none;">
                    <table style="width: 100%;">
                    <tr>
                        <td>
                        <div class="field_item"> 
                            <label>Is Financing: <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="8" id="iFinanciamiento" onblur="fn_invoices.valid_financing(this.value);">
                                <option value="0">NO</option>
                                <option value="1">YES</option>  
                            </select> 
                        </div>
                        </td>
                        <td>
                          <div class="field_item financing_fields" style="display:none">
                                <label>Financing Company: <span style="color:#ff0000;">*</span>:</label> 
                                <select tabindex="2" id="iConsecutivoFinanciera"><option value="">Select an option...</option></select>
                          </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item financing_fields" style="display:none"> 
                            <label>Financing Months:</label>  
                            <input tabindex="9" id="sDiasFinanciamiento" name="sDiasFinanciamiento" type="text" class="num" maxlenght="2">
                        </div> 
                        </td>
                        <td>
                        <div class="field_item financing_fields" style="display:none"> 
                            <label>Amount of each pay:</label>  
                            <input tabindex="9" id="iFinancingAmount" name="iFinancingAmount" type="text" class="num" maxlenght="10" placeholder="$:">
                        </div>
                        </td>
                    </tr> 
                    </table>
                </fieldset>--> 
                <div id="invoice_detalle" style="width: 99%;margin: 0 auto 25px;">
                    <h5 class="data-grid-header">Products & Services Summary</h5>
                    <table class="popup-datagrid" style="width: 100%;">
                        <thead>
                            <tr class="grid-head2"> 
                                <td class="etiqueta_grid">No.</td>
                                <td class="etiqueta_grid">Description</td> 
                                <td class="etiqueta_grid">Qty.</td>
                                <td class="etiqueta_grid">Unit price</td>
                                <td class="etiqueta_grid">TAX %</td>
                                <td class="etiqueta_grid" style="width: 150px;">Total price</td>
                                <td><div class="btn-icon add" title="Add +"  onclick="fn_invoices.summary.add();"><i class="fa fa-plus"></i></div>  </td>
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Subtotal: </label></div></td>
                                <td><div class="data-grid-totales"><input id="dSubtotal" name="dSubtotal" type="text" readonly="readonly" value=""></div></td>  
                            </tr>
                            <!--<tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Advance payment: </label></div></td>
                                <td><div class="data-grid-totales"><input id="dAnticipo" type="text" readonly="readonly" value="" onblur="fn_invoices.actualiza_totales();"></div></td>  
                            </tr>-->
                            <tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Tax: </label></div></td>
                                <td><div class="data-grid-totales"><input id="dTax" name="dTax" type="text" readonly="readonly" value=""></div></td>  
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Total: </label></div></td>
                                <td><div class="data-grid-totales"><input type="text" id="dTotal" name="dTotal" readonly="readonly" value=""></div></td>  
                            </tr>
                            <!--<tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Balance: </label></div></td>
                                <td><div class="data-grid-totales"><input id="dBalance" name="dBalance" type="text" readonly="readonly" value=""></div></td>  
                            </tr>-->
                        </tfoot>
                    </table>   
                </div>
            </form>
            <div>
                <button type="button" class="btn-1" onclick="fn_invoices.save();" style="">SAVE</button>  
                <button type="button" class="btn-1 btn-preview" onclick="fn_invoices.preview();" style="margin-right:10px;background:#5ec2d4;">Preview</button>         
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('edit_form_invoice');fn_invoices.filtraInformacion();" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
            </div>
        </div>
        </div>
    </div>
    <!-- EDIT PS -->
    <div id="edit_form_summary" class="popup-form" style="height: 97%;width:80%;">
        <div class="p-header">
            <h2>INVOICE SUMMARY - (EDIT OR ADD)</h2>
            <div class="btn-close" title="Close Window" onclick="fn_invoices.summary.cerrar_ventana();"><i class="fa fa-times"></i></div>
        </div>
        <div class="p-container" style="height: 94%;overflow-y: auto;">
        <div>
            <form>
                <fieldset style="padding-bottom: 5px;">
                <legend>Product / Service - General Data</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <table class="detalle_formulario" style="width: 100%;">
                    <tr>
                        <td colspan="100%">
                        <input id="iConsecutivoDetalle" name="iConsecutivoDetalle" type="hidden" value=""> 
                        <input id="iConsecutivoEndosos" name="iConsecutivoEndosos" type="hidden" value=""> 
                        <input id="iConsecutivoInvoice" name="iConsecutivoInvoice" type="hidden" value="">
                        <div class="field_item"> 
                            <label for="iConsecutivoServicio">Service/Product <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="1" id="iConsecutivoServicio" name="iConsecutivoServicio" class="required-field" onblur="fn_invoices.summary.get_service_data();"><option value="">Select an option...</option></select>
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Comments:</label> 
                            <textarea tabindex="2" id="sComentarios" name="sComentarios" style="height:50px!important;resize:none;"></textarea>
                        </div>
                        </td> 
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item">
                            <label>Qty <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="3" id="iCantidad" name="iCantidad" class="required-field num" type="text" value="" style="width: 97%;" onchange="fn_invoices.summary.get_total();">
                        </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Unit Price <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="4" id="iPrecioUnitario" name="iPrecioUnitario" class="required-field inputdecimals" type="text" value="" style="width: 97%;" onchange="fn_invoices.summary.get_total();"> 
                        </div>
                        </td> 
                        <td>
                        <div class="field_item"> 
                            <label>%TAX <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="5" id="iPctImpuesto" name="iPctImpuesto" class="required-field inputdecimals" type="text" value="" style="width: 97%;" onchange="fn_invoices.summary.get_total();"> 
                        </div>
                        </td> 
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item">
                            <label>TAX Amount:</label> 
                            <input tabindex="6" id="iImpuesto" name="iImpuesto" class="required-field inputdecimals readonly" type="text" value="" readonly="readonly" style="width: 97%;">
                        </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Total Price:</label> 
                            <input tabindex="7" id="iPrecioExtendido" name="iPrecioExtendido" class="required-field inputdecimals readonly" type="text" value="" readonly="readonly" style="width: 97%;"> 
                        </div>
                        </td> 
                        <td>
                        <div class="field_item"> 
                            <input tabindex="8" id="iEndorsementsApply" name="iEndorsementsApply" type="checkbox" value="" onchange="fn_invoices.summary.valida_endorsements();" style="position: relative;top: 6px;margin-left: 5px!important;margin-right: 4px!important;">
                            <label style="position: relative;top: 2px;">Apply payment to endorsement?</label> 
                        </div>
                        </td> 
                    </tr>
                    <tr class="data-endorsements">
                        <td>
                        <div class="field_item"> 
                            <label>Show detail of endorsements on the invoice?</label> 
                            <select tabindex="9" id="iMostrarEndorsements" name="iMostrarEndorsements" style="width: 50%;">
                                <option value="0">NO</option>
                                <option value="1">YES</option>
                            </select>
                        </div>
                        </td>
                    </tr>
                </table>
                <legend class="data-endorsements">Endorsements data</legend>
                <table style="width: 100%;" cellpadding="0" cellspacing="0" class="data-endorsements">
                <tr> 
                    <td colspan="2">
                    <table id="invoice_endorsement_added" class="popup-datagrid" style="width: 100%;margin-top: 10px;margin-bottom: 10px;" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr class="grid-head2">
                                <td class="etiqueta_grid" style="width: 40%;">type / Description</td>
                                <td class="etiqueta_grid" style="width: 30%;">
                                    <span style="display: -webkit-inline-box;width: 60%;">Policy</span>
                                    <span style="display: -webkit-inline-box;width: 38%;">END No.</span>
                                </td>
                                <td class="etiqueta_grid">APP Date</td>
                                <td class="etiqueta_grid">Status</td>                                      
                                <td><div class="btn-icon add" title="Add +" onclick="fn_invoices.summary.endorsements.add();"><i class="fa fa-plus"></i></div></td>
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
                <button type="button" class="btn-1" onclick="fn_invoices.summary.save();" style="">SAVE</button>          
                <button type="button" class="btn-1" onclick="fn_invoices.summary.cerrar_ventana();" style="margin-right:10px;background:#e8051b;">Cancel</button> 
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
                        <div class="btn_pdf btn-icon pdf btn-right" title="filter endorsements" onclick="fn_invoices.summary.endorsements.filtraInformacion();"><i class="fa fa-search"></i></div>
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
                <button type="button" class="btn-1" onclick="fn_invoices.summary.endorsements.save();" style="">SAVE</button>          
                <button type="button" class="btn-1" onclick="$('#edit_form_endorsements').hide();" style="margin-right:10px;background:#e8051b;">Cancel</button> 
            </div>
        </div>
        </div>
    </div>
    <!-- DIALOGUES -->
    <div id="dialog-confirm-delete-invoice" title="Delete">
      <p><span class="ui-icon ui-icon-alert" ></span>Are you sure you want to delete the invoice?</p>
      <input value="" type="hidden" name="iConsecutivo">
    </div>
    <!-- PAYMENTS -->
    <div id="edit_form_payment" class="popup-form" style="height: 97%;width:80%;">
        <div class="p-header">
            <h2>INVOICES - PAYMENTS</h2>
            <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('edit_form_payment');"><i class="fa fa-times"></i></div>
        </div>
        <div class="p-container" style="height: 94%;overflow-y: auto;">
        <div>
            <form id="payments_data">
                <fieldset>
                    <legend>Invoice Data</legend>
                    <table style="width: 100%;">
                    <tbody>
                        <tr>
                            <td style="width: 60%;">
                            <div class="field_item"> 
                                <label for="sNoReferencia">No. Reference:</label> 
                                <input name="sNoReferencia" type="text" class="readonly" style="width: 97%;" readonly>
                            </div>
                            </td>
                            <td>
                            <div class="field_item"> 
                                <label>Invoice Date:</label> 
                                <input name="dFechaInvoice" type="text" class="readonly" style="width: 97%;" readonly>
                            </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            <div class="field_item">
                                <label>Company:</label> 
                                <select name="iConsecutivoCompania" class="readonly" disabled style="width: 98%!important;height: 25px!important;"><option value="">Select an option...</option></select>
                            </div>
                            </td>
                            <td>
                            <div class="field_item"> 
                                <label>Payment Currency:</label> 
                                <select name="sCveMoneda" class="readonly" disabled style="width: 99%!important;height: 25px!important;"><option value="USD" selected="">USD</option></select> 
                            </div>
                            </td> 
                        </tr>
                        <tr>
                            <td>
                            <div class="field_item">
                                <label>Balance paid:</label> 
                                <input name="iBalancePaid" type="text" class="readonly" style="width: 97%;" readonly>
                            </div>
                            </td>
                            <td>
                            <div class="field_item"> 
                                <label>Invoice Total:</label> 
                                <input name="dTotal" type="text" class="readonly" style="width: 97%;" readonly>
                            </div>
                            </td> 
                        </tr>
                        <tr>
                            <td>
                            <div class="field_item">
                                <label>Outstanding balance:</label> 
                                <input name="iBalanceOutstanding" type="text" class="readonly" style="width: 97%;" readonly>
                            </div>
                            </td>
                            <td></td> 
                        </tr>
                    </tbody>
                    </table>
                </fieldset>
                <div id="payments_detalle" style="width: 99%;margin: 0 auto 25px;">
                    <h5 class="data-grid-header">Payments</h5>
                    <table class="popup-datagrid" style="width: 100%;">
                        <thead>
                            <tr class="grid-head2"> 
                                <td class="etiqueta_grid">No.</td>
                                <td class="etiqueta_grid">Payment Date</td>
                                <td class="etiqueta_grid">Description</td> 
                                <td class="etiqueta_grid">Amount</td>
                                <td><div class="btn-icon add" title="Add +"  onclick="fn_invoices.payments.add();"><i class="fa fa-plus"></i></div></td>
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                    </table>   
                </div>
            </form>
            <div>        
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('edit_form_payment');" style="margin-right:10px;background:#e8051b;">Close</button> 
            </div>
        </div>
        </div>
    </div>
    <div id="edit_form_payment_detalle" class="popup-form" style="height: 97%;width:80%;">
        <div class="p-header">
            <h2>INVOICE PAYMENT - (EDIT OR ADD)</h2>
            <div class="btn-close" title="Close Window" onclick="fn_invoices.payments.cerrar_ventana();"><i class="fa fa-times"></i></div>
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
                        <input name="iConsecutivoInvoice" type="hidden" value="">
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
                        <td colspan="100%">
                        <div class="field_item"> 
                            <label for="sDescripcion">Description <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="3" name="sDescripcion" class="required-field txt-uppercase" type="text" value="">
                        </div>
                        </td> 
                    </tr>
                    <tr> 
                        <td>
                        <div class="field_item">
                            <label>Amount <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="4" name="iMonto" class="required-field inputdecimals" type="text" value="" style="width: 97%;" onchange="fn_invoices.payments.get_saldos();">
                        </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Currency <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="5" name="sCveMoneda" class="required-field" style="height: 25px!important;width:97%;"><option value="USD" selected>USD</option></select> 
                        </div>
                        </td>  
                        <td>
                        <div class="field_item"> 
                            <label>Payment Method <span style="color:#ff0000;">*</span>:</label> 
                            <input tabindex="6" name="sMetodoPago" class="required-field txt-uppercase" type="text" value="" style="width: 97%;"> 
                        </div>
                        </td> 
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Comments:</label> 
                            <textarea tabindex="7" name="sComentarios" style="height:50px!important;"></textarea>
                        </div>
                        </td> 
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item">
                            <label>Previous balance:</label> 
                            <input name="iSaldoAnterior" class="required-field inputdecimals readonly" type="text" value="" readonly="readonly" style="width: 97%;">
                        </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Outstanding balance:</label> 
                            <input name="iSaldoPendiente" class="required-field inputdecimals readonly" type="text" value="" readonly="readonly" style="width: 97%;"> 
                        </div>
                        </td>  
                    </tr>
                </table>
                </fieldset>
            </form>
            <div>
                <button type="button" class="btn-1" onclick="fn_invoices.payments.save();" style="">SAVE</button>          
                <button type="button" class="btn-1" onclick="fn_invoices.payments.cerrar_ventana();" style="margin-right:10px;background:#e8051b;">Cancel</button> 
            </div>
        </div>
        </div>
    </div>
    <!-- FOOTER -->
    <?php include("footer.php"); ?> 
    </body>
</html>
<?php } ?>
