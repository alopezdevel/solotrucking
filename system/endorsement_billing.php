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
            fn_endorsement_billing.init();
            fn_endorsement_billing.fillgrid();
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
                        fn_endorsement_billing.save();             
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
                height : 450,
                resizable : false,
                buttons : {
                    'SAVE DATA' : function() {
                        fn_endorsement_billing.files.save();
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
                        fn_endorsement_billing.email.send();             
                    },
                    'NO' : function(){
                        $(this).dialog('close');
                    }
                }
            }); 
            $('#dialog_mark_email_unit').dialog({
                modal: true,
                autoOpen: false,
                width : 420,
                height : 200,
                resizable : false,
                buttons : {
                    'YES' : function() {
                        $(this).dialog('close');
                        fn_endorsement_billing.email.mark_sent();             
                    },
                    'NO' : function(){
                        $(this).dialog('close');
                    }
                }
            });
            $('#dialog_delete_endorsement').dialog({
                modal: true,
                autoOpen: false,
                width : 300,
                height : 200,
                resizable : false,
                buttons : {
                    'YES' : function() {
                        clave = $('#dialog_delete_endorsement input[name=iConsecutivo]').val();
                        $(this).dialog('close');
                        
                        fn_endorsement_billing.delete_endorsement(clave);             
                    },
                     'NO' : function(){
                        $(this).dialog('close');
                    }
                }
            }); 
            
            $('#dialog_report_endorsements').dialog({
                modal: true,
                autoOpen: false,
                width : 650,
                height : 510,
                resizable : false,
                buttons : {
                    'DOWNLOAD EXCEL FILE' : function() { fn_endorsement_billing.report.open(true);},
                    'OK' : function(){fn_endorsement_billing.report.open(false);},
                    'CANCEL' : function(){
                        $(this).dialog('close');
                    }
                }
            }); 
            
            //DATEPICKERS
            var dates_rp = $( "#flt_dateFrom, #flt_dateTo" ).datepicker({
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
               
    }  
    function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}  }                   
    var fn_endorsement_billing = {
            domroot:"#ct_endorsement",
            data_grid: "#data_grid_endorsement",
            form : "#endorsements_edit_form ",
            filtro : "",
            pagina_actual : "",
            sort : "DESC",
            orden : "LEFT(B.dFechaAplicacion,10)",
            init : function(){
                
                $('.num').keydown(fn_solotrucking.inputnumero); 
                $('.decimals').keydown(fn_solotrucking.inputdecimals);
                $('.hora').mask('00:00');
                
                //Filtrado con la tecla enter
                $(fn_endorsement_billing.data_grid + ' #grid-head1 input').keyup(function(event){
                    if (event.keyCode == '13') {
                        event.preventDefault();
                        fn_endorsement_billing.filtraInformacion();
                    }
                    if(event.keyCode == '27'){
                       event.preventDefault();
                       $(this).val(''); 
                       fn_endorsement_billing.filtraInformacion();
                    }
                });      
                //INICIALIZA DATEPICKER PARA CAMPOS FECHA
                $(".fecha").datepicker({
                    showOn: 'button',
                    buttonImage: 'images/layout.png',
                    dateFormat : 'mm/dd/yy',
                    buttonImageOnly: true
                });
                $(".fecha,.flt_date").mask("99/99/9999");
                
                //Cargar Catalogos:
                $.ajax({             
                    type:"POST", 
                    url:"catalogos_generales.php", 
                    data:{accion:"get_companies"},
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){
                            $("#endorsements_edit_form select[name=iConsecutivoCompania], #edit_form_payment select[name=iConsecutivoCompania]").empty().append(data.select);
                        }
                    }
                });
                $.ajax({             
                    type:"POST", 
                    url:"catalogos_generales.php", 
                    data:{accion:"get_financieras"},
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){$("#endorsements_edit_form select[name=iConsecutivoFinanciera]").empty().append(data.select); }
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
                    url:"endorsement_billing_server.php", 
                    data:{
                        accion:"get_endorsements",
                        registros_por_pagina : "15", 
                        pagina_actual : fn_endorsement_billing.pagina_actual, 
                        filtroInformacion : fn_endorsement_billing.filtro,  
                        ordenInformacion : fn_endorsement_billing.orden,
                        sortInformacion : fn_endorsement_billing.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_endorsement_billing.data_grid+" tbody").empty().append(data.tabla);
                        $(fn_endorsement_billing.data_grid+" tbody tr:even").addClass('gray');
                        $(fn_endorsement_billing.data_grid+" tbody tr:odd").addClass('white');
                        $(fn_endorsement_billing.data_grid + " tfoot #paginas_total").val(data.total);
                        $(fn_endorsement_billing.data_grid + " tfoot #pagina_actual").val(data.pagina);
                        fn_endorsement_billing.pagina_actual = data.pagina;
                        
                        fn_endorsement_billing.add();
                        fn_endorsement_billing.edit();
                        fn_endorsement_billing.pay_add();
                    }
                }); 
            },
            firstPage : function(){
                if($(fn_endorsement_billing.data_grid+" #pagina_actual").val() != "1"){
                    fn_endorsement_billing.pagina_actual = "";
                    fn_endorsement_billing.fillgrid();
                }
            },
            previousPage : function(){
                if($(fn_endorsement_billing.data_grid+" #pagina_actual").val() != "1"){
                    fn_endorsement_billing.pagina_actual = (parseInt($(fn_endorsement_billing.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_endorsement_billing.fillgrid();
                }
            },
            nextPage : function(){
                if($(fn_endorsement_billing.data_grid+" #pagina_actual").val() != $(fn_endorsement_billing.data_grid+" #paginas_total").val()){
                    fn_endorsement_billing.pagina_actual = (parseInt($(fn_endorsement_billing.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_endorsement_billing.fillgrid();
                }
            },
            lastPage : function(){
                if($(fn_endorsement_billing.data_grid+" #pagina_actual").val() != $(fn_endorsement_billing.data_grid+" #paginas_total").val()){
                    fn_endorsement_billing.pagina_actual = $(fn_endorsement_billing.data_grid+" #paginas_total").val();
                    fn_endorsement_billing.fillgrid();
                }
            }, 
            ordenamiento : function(campo,objeto){
                $(fn_endorsement_billing.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_endorsement_billing.orden){
                    if(fn_endorsement_billing.sort == "ASC"){
                        fn_endorsement_billing.sort = "DESC";
                        $(fn_endorsement_billing.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_endorsement_billing.sort = "ASC";
                        $(fn_endorsement_billing.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_endorsement_billing.sort = "ASC";
                    fn_endorsement_billing.orden = campo;
                    $(fn_endorsement_billing.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_endorsement_billing.fillgrid();

                return false;
            }, 
            filtraInformacion : function(){
                    fn_endorsement_billing.pagina_actual = 0;
                    fn_endorsement_billing.filtro = "";
                   
                    if($(fn_endorsement_billing.data_grid+" .flt_company").val() != ""){ fn_endorsement_billing.filtro += "D.sNombreCompania|"+$(fn_endorsement_billing.data_grid+" .flt_company").val()+","} 
                    if($(fn_endorsement_billing.data_grid+" .flt_policy").val() != "") { fn_endorsement_billing.filtro += "C.sNumeroPoliza|"+$(fn_endorsement_billing.data_grid+" .flt_policy").val()+","} 
                    if($(fn_endorsement_billing.data_grid+" .flt_endoso").val() != "") { fn_endorsement_billing.filtro += "A.sNumeroEndosoBroker|"+$(fn_endorsement_billing.data_grid+" .flt_endoso").val()+","}
                    if($(fn_endorsement_billing.data_grid+" .flt_desc").val() != "")   { fn_endorsement_billing.filtro += "sDescripcionEndoso|"+$(fn_endorsement_billing.data_grid+" .flt_desc").val()+","}
                    if($(fn_endorsement_billing.data_grid+" .flt_date").val() != "")   { fn_endorsement_billing.filtro += "B.dFechaAplicacion|"+$(fn_endorsement_billing.data_grid+" .flt_date").val()+","}
                    //if($(fn_endorsement_billing.data_grid+" .flt_status").val() != "") { fn_endorsement_billing.filtro += "F.eStatus |"+$(fn_endorsement_billing.data_grid+" .flt_status").val()+","}
                    if($(fn_endorsement_billing.data_grid+" .flt_amount").val() != "") { fn_endorsement_billing.filtro += "F.dTotal|"+$(fn_endorsement_billing.data_grid+" .flt_amount").val()+","}
                    
                    fn_endorsement_billing.fillgrid();
            },
            // INVOICE
            add : function(){
                $(fn_endorsement_billing.data_grid + " tbody td .btn_add").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    var endor = $(this).parent().parent().find("td:eq(2)").prop('id');
                        endor = endor.split("_");
                        endor = endor[1];
                    var poliza= $(this).parent().parent().find("td:eq(1)").prop('id');
                        poliza= poliza.split("_");
                        poliza= poliza[1];
                        
                    fn_endorsement_billing.summary.endoso_id = endor;
                    fn_endorsement_billing.summary.company_id= clave;
                    fn_endorsement_billing.summary.poliza_id = poliza;
                    
                    $('#endorsements_edit_form input, #endorsements_edit_form select, #endorsements_edit_form textarea').val('');
                    $("#endorsements_edit_form .required-field").removeClass("error");
                    $('#endorsements_edit_form input[name=sNoReferencia]').removeProp('readonly').removeClass("readonly");
                    $('#endorsements_edit_form button.btn-preview,#endorsements_edit_form button.btn-apply').hide();
                    $("#endorsements_edit_form select[name=sCveMoneda]").val('USD');   
                    $("#endorsements_edit_form select[name=iConsecutivoCompania]").val(fn_endorsement_billing.summary.company_id); 
                    $("#endorsements_edit_form input[name=iConsecutivoEndoso]").val(fn_endorsement_billing.summary.endoso_id);
                    $("#endorsements_edit_form input[name=iConsecutivoPoliza]").val(fn_endorsement_billing.summary.poliza_id);
                    $("#endorsements_edit_form select[name=iConsecutivoCompania],#endorsements_edit_form select[name=sCveMoneda], #endorsements_edit_form select[name=iConsecutivoFinanciera]").prop("disabled","disabled");
                    fn_endorsement_billing.summary.get_services(); 
                    fn_endorsement_billing.get_policy_data();
                    
                    // campos financiamiento:
                    $("#endorsements_edit_form select[name=iFinanciamiento]").val('0');
                    fn_endorsement_billing.valid_financing();
                   
                    //ocultar datagrid detalle:
                    $("#invoice_detalle").hide();
                    $("#invoice_detalle .popup-datagrid tbody").empty();
                   
                    fn_solotrucking.get_date("#dFechaInvoice");
                   
                    //Limpiar campo file:
                    /*$("#endorsements_edit_form .file-message").html("");
                    $("#endorsements_edit_form #fileselect").val(null);
                    $("#endorsements_edit_form #fileselect").removeClass("fileupload");*/ 
                    
                    fn_popups.resaltar_ventana('endorsements_edit_form');
                    
                }); 
            },
            save : function (ghost_mode){
               
               if(ghost_mode == ''){ghost_mode = false;}
               //Validate Fields:
               var valid = true;
               var message = "";
               $(fn_endorsement_billing.form+" .required-field").removeClass("error");
               $(fn_endorsement_billing.form+" .required-field").each(function(){
                   if($(this).val() == ""){
                       valid   = false;
                       message = '<li> You must write all required fields.</li>';
                       $(this).addClass('error');
                   }
               });
               
               if(valid){
                   
                    //Removes temporalmente los disabled:
                    $("#endorsements_edit_form select[name=iConsecutivoCompania],#endorsements_edit_form select[name=sCveMoneda], #endorsements_edit_form select[name=iConsecutivoFinanciera]").removeProp("disabled");
                   
                    var form       = "#invoice_data";
                    var dataForm   = new FormData();
                    var other_data = $(form).serializeArray();
                    dataForm.append('accion','save_data');
                    //$.each($(form+' input[type=file]')[0].files,function(i, file){dataForm.append('file-'+i, file);});
                    $.each(other_data,function(key,input){dataForm.append(input.name,input.value);});
                    
                    $.ajax({
                      type: "POST",
                      url : "endorsement_billing_server.php",
                      data: dataForm,
                      cache: false,
                      contentType: false,
                      processData: false,
                      type: 'POST',
                      dataType : "json",
                      success : function(data){ 
                          switch(data.error){
                              case '0': 
                                if(!(ghost_mode)){
                                    fn_solotrucking.mensaje(data.msj);
                                    fn_endorsement_billing.filtraInformacion();
                                    fn_endorsement_billing.get_data(data.idFactura);
                                }
                              break;
                              case '1': fn_solotrucking.mensaje(data.msj); break;
                          }
                      }
                    });  
                    
               }
               else{fn_solotrucking.mensaje('<p>Please check the following:</p><ul style="padding: 10px;">'+message+'</ul>');}
            },
            edit : function (){
              $(fn_endorsement_billing.data_grid + " tbody td .btn_edit").bind("click",function(){
                    
                    var inv   = $(this).parent().parent().find("td:eq(0)").prop('class');
                        inv   = inv.split("_");
                        inv   = inv[1];
                    var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                        clave = clave.split("_");
                        clave = clave[1];
                    var endor = $(this).parent().parent().find("td:eq(2)").prop('id');
                        endor = endor.split("_");
                        endor = endor[1];
                    var poliza= $(this).parent().parent().find("td:eq(1)").prop('id');
                        poliza= poliza.split("_");
                        poliza= poliza[1];
                 
                    fn_endorsement_billing.summary.invoice_id= inv;
                    fn_endorsement_billing.summary.endoso_id = endor;
                    fn_endorsement_billing.summary.company_id= clave;
                    fn_endorsement_billing.summary.poliza_id = poliza;
                    
                    fn_endorsement_billing.get_data(inv);
                    
                    fn_popups.resaltar_ventana('endorsements_edit_form');
              }); 
              
              $(fn_endorsement_billing.data_grid + " tbody td .btn_pdf").bind("click",function(){
                    var inv   = $(this).parent().parent().find("td:eq(0)").prop('class');
                        inv   = inv.split("_");
                        inv   = inv[1];
                
                    if(inv != ""){window.open('pdf_invoice.php?id='+inv+'&ds=view','_blank');}  
              });  
            },
            get_data : function(clave){
                if(clave != ""){
                    $.post("funciones_invoices.php",
                    {accion:"get_data", clave: clave, domroot : fn_endorsement_billing.form},
                    function(data){
                        if(data.error == '0'){
                           $('#invoice_data :text, #invoice_data select, #invoice_data input').val('').removeClass('error'); 
                           $(fn_endorsement_billing.form+' #sNoReferencia').prop('readonly','readonly').addClass("readonly");
                           $("#endorsements_edit_form select[name=iConsecutivoCompania],#endorsements_edit_form select[name=sCveMoneda], #endorsements_edit_form select[name=iConsecutivoFinanciera]").prop("disabled","disabled");
                           $(fn_endorsement_billing.form+' button.btn-preview, #endorsements_edit_form button.btn-apply').show();
                           
                           //Limpiar campo file:
                           /*$(fn_endorsement_billing.form+" .file-message").html("");
                           $(fn_endorsement_billing.form+" #fileselect").val(null);
                           $(fn_endorsement_billing.form+" #fileselect").removeClass("fileupload");*/
                           
                           eval(data.fields);
                           
                           fn_endorsement_billing.actualiza_totales();
                           
                           //Inicializar datagrid de productos y servicios:
                           fn_endorsement_billing.summary.invoice_id =  $(fn_endorsement_billing.form+' #iConsecutivo').val();
                           fn_endorsement_billing.summary.company_id =  $(fn_endorsement_billing.form+' #iConsecutivoCompania').val();
                           
                           $(fn_endorsement_billing.form+" input[name=iConsecutivoEndoso]").val(fn_endorsement_billing.summary.endoso_id);
                           $(fn_endorsement_billing.form+" input[name=iConsecutivoPoliza]").val(fn_endorsement_billing.summary.poliza_id);
                           
                           fn_endorsement_billing.summary.fillgrid();
                           fn_endorsement_billing.summary.get_services();
                           fn_endorsement_billing.valid_financing(); 
                
                           $("#invoice_detalle").show();
                        }
                        else{fn_solotrucking.mensaje(data.msj);}       
                    },"json"); 
                }    
            },
            apply : function(){
                var clave = $("#invoice_data input[name=iConsecutivo]").val();
                if(clave != ""){
                    fn_endorsement_billing.save(true);
                    $.post("funciones_invoices.php",{"accion":"apply_data", "clave": clave},
                    function(data){
                        fn_solotrucking.mensaje(data.msj);
                        if(data.error == '0'){
                            fn_endorsement_billing.filtraInformacion();
                            fn_popups.cerrar_ventana('endorsements_edit_form');
                        }
                    },"json"); 
                }    
            },
            // Extras:
            preview : function(){
                var clave = $("#invoice_data input[name=iConsecutivo]").val();
                if(clave != ''){
                    if(clave != ""){
                        window.open('pdf_invoice.php?id='+clave+'&ds=preview','_blank');
                    }     
                }
            },
            valid_financing : function(){
                var financing = $("#endorsements_edit_form select[name=iFinanciamiento]").val();
                if(financing == '1'){
                    $('#endorsements_edit_form .financing_fields select').removeProp('disabled'); 
                    $('#endorsements_edit_form .financing_fields input').removeProp('readonly'); 
                }
                else{
                    $('#endorsements_edit_form .financing_fields select').prop('disabled','disabled');
                    $('#endorsements_edit_form .financing_fields input').prop('readonly','readonly');
                    $("#endorsements_edit_form .financing_fields input, #endorsements_edit_form .financing_fields select").val('');
                }
            },
            actualiza_totales : function(){
                $.ajax({
                    type: "POST",
                    url : "funciones_invoices.php",
                    data: {
                        "accion"      : "actualiza_totales",
                        "iConsecutivo": $("#invoice_data input[name=iConsecutivo]").val(),
                        "dSubtotal"   : $("#invoice_data input[name=dSubtotal]").val(),
                        "dTax"        : $("#invoice_data input[name=dTax]").val(),
                        "dTotal"      : $("#invoice_data input[name=dTotal]").val(),
                        "dBalance"    : $("#invoice_data input[name=dBalance]").val(),
                        "domroot"     : "#invoice_data",
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
            get_policy_data : function(){
                var policy = fn_endorsement_billing.summary.poliza_id;
                var endoso = fn_endorsement_billing.summary.endoso_id;
                if(policy != "" && endoso != ''){
                    $.ajax({             
                        type:"POST", 
                        url:"endorsement_billing_server.php", 
                        data:{'accion':"get_policy_data",'iConsecutivoPoliza':policy, 'iConsecutivoEndoso':endoso},
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $("#endorsements_edit_form .data_policy tbody").empty().append(data.html);
                        }
                    });
                }    
            },
            // Detalle Factura:
            summary : {
               invoice_id : "",
               endoso_id  : "", 
               company_id : "",
               poliza_id  : "",
               data_grid  : "#invoice_detalle .popup-datagrid",
               form : "#edit_form_summary", 
               fillgrid: function(){
                   if(fn_endorsement_billing.summary.invoice_id != ""){
                        $.ajax({             
                            type:"POST", 
                            url:"funciones_invoices.php", 
                            data:{
                                'accion'              : "ps_get_dataset",
                                "iConsecutivoInvoice" : fn_endorsement_billing.summary.invoice_id,
                                'iEndososBilling'     : 'true',
                            },
                            async : true,
                            dataType : "json",
                            success : function(data){                               
                                $(fn_endorsement_billing.summary.data_grid+" tbody").empty().append(data.tabla);
                                fn_endorsement_billing.summary.edit();
                                fn_endorsement_billing.summary.eliminar(); 
                            }
                        });     
                   }
               },
               add : function(){
                  
                  $("#edit_form_summary input, #edit_form_summary select, #edit_form_summary textarea").val('').removeClass('error'); 
                  $("#edit_form_summary input:checkbox").prop('checked',false);
                  $("#edit_form_summary select[name=iConsecutivoServicio]").removeProp('disabled');
                  
                  //Deshabilidar la aplicacion de mas endosos:  
                  $("#edit_form_summary input[name=iEndorsementsApply]").prop('disabled','disabled');  
                  $("#edit_form_summary .iEndosoApplyFields").hide();              
                  
                  //Valores Default:
                  $("#edit_form_summary #iCantidad").val(1);
                  $("#edit_form_summary input[name=iConsecutivoInvoice]").val(fn_endorsement_billing.summary.invoice_id);
                  
                  //Limpiar endorsement:
                  $("#edit_form_summary .data-endorsements").hide();
                  $("#edit_form_summary .data-endorsements #invoice_endorsement_grid tbody").empty().append('<tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr>');
                  
                  $("#edit_form_summary").show(); 
               },
               edit : function (){
                  $(fn_endorsement_billing.summary.data_grid+" tbody td .edit").bind("click",function(){
                        var clave = $(this).parent().parent().find("td:eq(0)").prop('id');
                            clave = clave.split("_");
                            clave = clave[1];
                        
                        var number= $(this).parent().parent().find("td:eq(0)").html();
                            
                        $.post("funciones_invoices.php",{accion:"detalle_get_data", clave: clave, domroot : fn_endorsement_billing.summary.form},
                        function(data){
                            if(data.error == '0'){
                               $("#edit_form_summary input, #edit_form_summary select, #edit_form_summary textarea").val('').removeClass('error'); 
                               eval(data.fields);
                               fn_endorsement_billing.summary.valida_endorsements();
                               
                               // Si es el detalle ligado al endoso, no permitimos editar servicio ni endosos aplicados.
                               if(number == '1'){
                                  $("#edit_form_summary .iEndosoApplyFields").show();  
                                  $("#edit_form_summary select[name=iConsecutivoServicio]").prop('disabled','disabled');  
                                  $("#edit_form_summary input[name=iEndorsementsApply]").prop('disabled','disabled');
                               }
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
                            if(data.error == '0'){eval(data.fields);fn_endorsement_billing.summary.get_total();}
                        }
                      });  
                  } 
               },
               get_total : function(){
                   var cantidad        = $("#edit_form_summary .detalle_formulario input[name=iCantidad]").val();
                   var iPrecioUnitario = $("#edit_form_summary .detalle_formulario input[name=iPrecioUnitario]").val();
                   var iPctImpuesto    = $("#edit_form_summary .detalle_formulario input[name=iPctImpuesto]").val();
                   
                   if(cantidad > 0){
                       cantidad        = fn_solotrucking.calcular_decimales(cantidad,2);
                       iPrecioUnitario = fn_solotrucking.calcular_decimales(iPrecioUnitario,2);
                       iPctImpuesto    = parseFloat(iPctImpuesto);
                       
                       var impXcan = fn_solotrucking.calcular_decimales((cantidad*iPrecioUnitario),2); //Importe Unit por Cantidad.
                       var tax     = fn_solotrucking.calcular_decimales((impXcan*(iPctImpuesto/100)),2); //Calcular Tax.
                       var total   = fn_solotrucking.calcular_decimales((impXcan+tax),2);
                           tax     = tax.toFixed(2);
                           total   = total.toFixed(2);     
                       
                       $("#edit_form_summary .detalle_formulario input[name=iImpuesto]").val(tax);
                       $("#edit_form_summary .detalle_formulario input[name=iPrecioExtendido]").val(total);
                   }
               },
               save : function(ghost_mode){
                   
                   if(ghost_mode == '' || ghost_mode == undefined){ghost_mode = false;} 
                   
                   //Validate Fields:
                   var valid   = true;
                   var message = "";
                   $(fn_endorsement_billing.summary.form+" .required-field").removeClass("error");
                   $(fn_endorsement_billing.summary.form+" .required-field").each(function(){
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
                     if($(fn_endorsement_billing.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
                        
                        struct_data_post.action  = "detalle_save_data";
                        struct_data_post.domroot = fn_endorsement_billing.summary.form+" .detalle_formulario"; 
                        
                        $.post("funciones_invoices.php",struct_data_post.parse(),
                        function(data){
                            switch(data.error){
                             case '0':
                                if(ghost_mode == false){
                                    fn_solotrucking.mensaje(data.msj);
                                    fn_endorsement_billing.summary.fillgrid();
                                    $("#edit_form_summary input, #edit_form_summary select, #edit_form_summary textarea").val('').removeClass('error');
                                    $("#edit_form_summary .data-endorsements").hide();
                                    $("#edit_form_summary .data-endorsements #invoice_endorsement_grid tbody").empty().append('<tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr>');
                                    $("#edit_form_summary").hide(); 
                                    fn_endorsement_billing.actualiza_totales();
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
                $(fn_endorsement_billing.summary.data_grid+" tbody td .trash").bind("click",function(){
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
                                fn_endorsement_billing.summary.fillgrid();  
                                fn_endorsement_billing.actualiza_totales();   
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
                       if($(fn_endorsement_billing.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val() != ''){
                           fn_endorsement_billing.summary.endorsements.get_endorsements();
                       }
                       //$("#edit_form_summary .data-endorsements select[name='iMostrarEndorsements']").val('0');
                   }
                   else{
                       $("#edit_form_summary .data-endorsements").hide();
                       $("#edit_form_summary .data-endorsements #invoice_endorsement_grid tbody").empty().append('<tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr>');
                   }
               },
               endorsements : {
                   // Cargar endosos ya guardados:
                   get_endorsements: function(){
                       $.ajax({             
                        type:"POST", 
                        url:"funciones_invoices.php", 
                        data:{
                            'accion'              : "get_endorsements_added",
                            'iConsecutivoDetalle' : $(fn_endorsement_billing.summary.form+' .detalle_formulario input[name=iConsecutivoDetalle]').val(),
                            'iBtnsActive'         : 'false',
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $("#invoice_endorsement_added tbody").empty().append(data.html); 
                        }
                       }); 
                   }, 
               },
            }, 
            // PAYMENTS
            pay_add : function(){
                $(fn_endorsement_billing.data_grid + " tbody td .btn_add_pay").bind("click",function(){
                    
                    var clave = $(this).parent().parent().find("td:eq(0)").prop('class');
                        clave = clave.split("_");
                        clave = clave[1];
                    var comp  = $(this).parent().parent().find("td:eq(0)").prop('id');
                        comp  = comp.split("_");
                        comp  = comp[1];
                    
                    var financing = $(this).parent().parent().find("td:eq(7)").html();
                    
                    $('#edit_form_payment :text,#edit_form_payment select,#edit_form_payment input').val('').removeClass('error');
                    $('#edit_form_payment input[name=iConsecutivoInvoice]').val(clave);
                    $('#edit_form_payment input[name=sNoPago]').removeProp('readonly').removeClass("readonly");
                    $('#edit_form_payment select[name=sCveMoneda]').val('USD');
                    $("#edit_form_payment select[name=iConsecutivoCompania]").val(comp);
                    $("#edit_form_payment select[name=iConsecutivoCompania],#edit_form_payment select[name=sCveMoneda]").prop("disabled","disabled"); 
                    
                    if(financing == 'N/A'){
                        var total = $(this).parent().parent().find("td:eq(9)").prop('title');
                        $("#edit_form_payment input[name=iMonto]").val(total);    
                    }   
                    fn_solotrucking.get_date("#dFechaPago");
                    fn_endorsement_billing.get_invoice_data(); 
                       
                    //Limpiar campo file:
                    $("#edit_form_payment .file-message").html("");
                    $("#edit_form_payment #fileselect").val(null);
                    $("#edit_form_payment #fileselect").removeClass("fileupload");
                       
                    fn_popups.resaltar_ventana('edit_form_payment');      
                });
            },
            pay_save : function (){
               
               //Validate Fields:
               var valid = true;
               var message = "";
               $("#edit_form_payment .required-field").removeClass("error");
               $("#edit_form_payment .required-field").each(function(){
                   if($(this).val() == ""){
                       valid   = false;
                       message = '<li> You must write all required fields.</li>';
                       $(this).addClass('error');
                   }
               });
               
               if(valid){
    
                   $("#edit_form_payment select[name=iConsecutivoCompania],#edit_form_payment select[name=sCveMoneda]").removeProp("disabled");
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
                                fn_endorsement_billing.filtraInformacion();
                                fn_popups.cerrar_ventana('edit_form_payment');
                              break;
                              case '1': fn_solotrucking.mensaje(data.msj); break;
                          }
                      }
                    });   
                 
               }
               else{
                   fn_solotrucking.mensaje('<p>Please check the following:</p><ul style="padding: 10px;">'+message+'</ul>');
               }
                
            },
            pay_valid : function(){
                
            },
            // Extras for payments:
            get_invoice_data : function(){
                clave = $('#edit_form_payment input[name=iConsecutivoInvoice]').val();
                if(clave != ""){
                    $.ajax({             
                        type:"POST", 
                        url:"endorsement_billing_server.php", 
                        data:{'accion':"get_invoice_data",'iConsecutivoInvoice':clave},
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $("#edit_form_payment .data_invoice tbody").empty().append(data.html);
                        }
                    });
                }    
            },
    }    
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_endorsement" class="container">
        <div class="page-title">
            <h1>ACCOUNTING / CONTABILIDAD</h1>
            <h2>ENDORSEMENTS / ENDOSOS </h2>
            <img src="images/data-grid/endorsement_status.jpg" alt="endorsement_status.jpg" style="float:right;position: relative;top: -90px;margin-bottom: -100px;"> 
        </div>
        <table id="data_grid_endorsement" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style="width:300px;"><input class="flt_company" type="text" placeholder="Company:"></td>
                <td style="width:200px;"><input class="flt_policy" type="text" placeholder="Policy:"></td>
                <td style="width:70px;"><input class="flt_endoso" type="text" placeholder="END#:"></td>
                <td style="width:330px;">
                    <input class="flt_desc" type="text" placeholder="Description" style="width: 60%;float: left;">
                    <input class="flt_date" type="text" placeholder="DD/MM/YYYY" style="float: left;width: 30%;margin-left: 4%!important;">
                </td>
                <td style="width:90px;"></td>
                <td style="width:150px;">
                    <!--<select class="flt_status" onblur="fn_endorsement_billing.filtraInformacion();">
                        <option value="">Select an option...</option>
                        <option value="_">NO INVOICE</option>
                        <option value="EDITABLE">NEW</option>
                        <option value="APPLIED">APPLIED WITHOUT SEND</option>
                        <option value="SENT">APPLIED & SENT</option>
                        <option value="PAID">PAID</option> 
                        <option value="CANCELED">CANCELED</option>
                    </select>-->
                </td>
                <td style="width:130px;"><input class="flt_amount" type="text" placeholder="Amount:"></td>  
                <td style='width:110px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_endorsement_billing.filtraInformacion();"><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <!--<tr id="grid-head-tools">
                <td colspan="100%">
                    <ul> 
                        <li><div class="btn-icon report btn-left" title="Report of Endorsements" onclick="fn_endorsement_billing.dialog_report_open();" style="width:auto!important;"><i class="fa fa-folder-open"></i><span style="margin-left:5px;font-size: 10px!important;">Report of Endorsements</span></div></li>  
                    </ul>
                </td>
            </tr>-->
            <tr id="grid-head2">
                <td class="etiqueta_grid" onclick="fn_endorsement_billing.ordenamiento('D.sNombreCompania',this.cellIndex);">COMPANY</td>
                <td class="etiqueta_grid" onclick="fn_endorsement_billing.ordenamiento('C.sNumeroPoliza',this.cellIndex);">Policy</td>
                <td class="etiqueta_grid" onclick="fn_endorsement_billing.ordenamiento('A.sNumeroEndosoBroker',this.cellIndex);">END#</td>
                <td class="etiqueta_grid">Description</td>
                <td class="etiqueta_grid" onclick="fn_endorsement_billing.ordenamiento('F.iFinanciamiento',this.cellIndex);">FINANCING</td>
                <td class="etiqueta_grid" onclick="fn_endorsement_billing.ordenamiento('F.eStatus',this.cellIndex);">Billing Status</td>
                <td class="etiqueta_grid" onclick="fn_endorsement_billing.ordenamiento('A.rImporteEndosoBroker',this.cellIndex);">Amount</td>
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
                        <button id="pgn-inicio"    onclick="fn_endorsement_billing.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_endorsement_billing.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_endorsement_billing.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_endorsement_billing.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>    
    </div>
</div>
<!-- INVOICE -->
<div id="endorsements_edit_form" class="popup-form" style="width: 1300px;padding-bottom: 10px;">
    <div class="p-header">
            <h2>ENDORSEMENT INVOICE - (EDIT OR ADD)</h2>
            <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('endorsements_edit_form');fn_endorsement_billing.filtraInformacion();"><i class="fa fa-times"></i></div>
        </div>
        <div class="p-container" style="height: 94%;overflow-y: auto;">
        <div>
            <table class="data_policy popup-datagrid" style="width: 100%;margin-top:0px;margin-bottom: 20px;" cellpadding="0" cellspacing="0">
            <thead>
                    <tr class="grid-head1">
                        <td class="etiqueta_grid" colspan="6" style="padding: 2px;height: 20px;color: #fff;text-transform: uppercase;font-weight: 700;font-size: 0.9em;">Policy Data</td>
                        <td class="etiqueta_grid" colspan="3" style="padding: 2px;height: 20px;color: #fff;text-transform: uppercase;font-weight: 700;font-size: 0.9em;">Endorsement Data</td>
                        <td style="padding: 2px;height: 20px;color: #fff;text-transform: uppercase;font-weight: 700;font-size: 0.9em;"></td>
                    </tr>
                    <tr class="grid-head2">
                        <td class="etiqueta_grid">Policy No.</td>
                        <td class="etiqueta_grid">Type</td>
                        <td class="etiqueta_grid">Broker</td>
                        <td class="etiqueta_grid">Insurance</td>
                        <td class="etiqueta_grid">EFF date</td> 
                        <td class="etiqueta_grid">Exp Date</td> 
                        <td class="etiqueta_grid">END No.</td>
                        <td class="etiqueta_grid">Description</td>
                        <td class="etiqueta_grid">Broker Invoice</td>
                        <td></td>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <form id="invoice_data">
                <fieldset style="padding-bottom: 5px;">
                <legend>General Data</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <input id="iConsecutivo" name="iConsecutivo" type="hidden" value=""> 
                <input id="iConsecutivoEndoso" name="iConsecutivoEndoso" type="hidden" value="">
                <input id="iConsecutivoPoliza" name="iConsecutivoPoliza" type="hidden" value="">
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
                        <td>
                        <div class="field_item"> 
                            <label>Is Financing: <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="5" id="iFinanciamiento" name="iFinanciamiento" onblur="fn_endorsement_billing.valid_financing();">
                                <option value="0">NO</option>
                                <option value="1">YES</option>  
                            </select> 
                        </div>
                        </td>
                        <td>
                          <div class="field_item financing_fields">
                                <label>Financing Company: <span style="color:#ff0000;">*</span>:</label> 
                                <select tabindex="6" id="iConsecutivoFinanciera" name="iConsecutivoFinanciera"><option value="">Select an option...</option></select>
                          </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item financing_fields"> 
                            <label>Financing Months:</label>  
                            <input tabindex="7" id="sDiasFinanciamiento" name="sDiasFinanciamiento" type="text" class="num" maxlenght="2">
                        </div> 
                        </td>
                        <td>
                        <div class="field_item financing_fields"> 
                            <label>Amount of each pay:</label>  
                            <input tabindex="8" id="dFinanciamientoMonto" name="dFinanciamientoMonto" type="text" class="inputdecimals" maxlenght="10" placeholder="$:">
                        </div>
                        </td>
                    </tr> 
                    <!--<tr style="display:none;">
                        <td colspan="100%">
                            <div class="field_item"> 
                                <label>File to upload (PDF,JPEG,JPG,PNG):</label>
                                <div class="file-container">
                                    <input tabindex="9" id="fileselect" name="fileselect" type="file" style="height: 90px!important;"/>
                                    <div class="file-message"></div>
                                </div>
                            </div> 
                        </td>
                    </tr> -->
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Comments:</label> 
                            <textarea tabindex="10" id="sComentarios" name="sComentarios" style="height:50px!important;"></textarea>
                        </div>
                        </td> 
                    </tr>
                </table>
                </fieldset>
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
                                <td><div class="btn-icon add" title="Add +"  onclick="fn_endorsement_billing.summary.add();"><i class="fa fa-plus"></i></div>  </td>
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4"></td>
                                <td><div class="data-grid-totales"><label>Subtotal: </label></div></td>
                                <td><div class="data-grid-totales"><input id="dSubtotal" name="dSubtotal" type="text" readonly="readonly" value=""></div></td>  
                            </tr>
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
                        </tfoot>
                    </table>   
                </div>
            </form>
            <div>
                <button type="button" class="btn-1 btn-apply" onclick="fn_endorsement_billing.apply();" style="margin-right:10px;background: #87c540;width: 140px;">APPLY INVOICE</button>
                <button type="button" class="btn-1" onclick="fn_endorsement_billing.save();"  style="margin-right: 10px;">SAVE</button>  
                <button type="button" class="btn-1 btn-preview" onclick="fn_endorsement_billing.preview();" style="margin-right:10px;background:#5ec2d4;">Preview</button>         
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('endorsements_edit_form');fn_endorsement_billing.filtraInformacion();" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
            </div>
        </div>
        </div>
</div>
<div id="edit_form_summary" class="popup-form" style="width: 1300px;padding-bottom: 10px;">
    <div class="p-header">
        <h2>ENDORSEMENT INVOICE SUMMARY - (EDIT OR ADD)</h2>
        <div class="btn-close" title="Close Window" onclick="fn_endorsement_billing.summary.cerrar_ventana();"><i class="fa fa-times"></i></div>
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
                        <select tabindex="1" id="iConsecutivoServicio" name="iConsecutivoServicio" class="required-field" onblur="fn_endorsement_billing.summary.get_service_data();"><option value="">Select an option...</option></select>
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
                        <input tabindex="3" id="iCantidad" name="iCantidad" class="required-field num" type="text" value="" style="width: 97%;" onchange="fn_endorsement_billing.summary.get_total();">
                    </div>
                    </td>
                    <td>
                    <div class="field_item"> 
                        <label>Unit Price <span style="color:#ff0000;">*</span>:</label> 
                        <input tabindex="4" id="iPrecioUnitario" name="iPrecioUnitario" class="required-field inputdecimals" type="text" value="" style="width: 97%;" onchange="fn_endorsement_billing.summary.get_total();"> 
                    </div>
                    </td> 
                    <td>
                    <div class="field_item"> 
                        <label>%TAX <span style="color:#ff0000;">*</span>:</label> 
                        <input tabindex="5" id="iPctImpuesto" name="iPctImpuesto" class="required-field inputdecimals" type="text" value="" style="width: 97%;" onchange="fn_endorsement_billing.summary.get_total();"> 
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
                    <div class="field_item iEndosoApplyFields"> 
                        <input tabindex="8" id="iEndorsementsApply" name="iEndorsementsApply" type="checkbox" value="" onchange="fn_endorsement_billing.summary.valida_endorsements();" style="position: relative;top: 6px;margin-left: 5px!important;margin-right: 4px!important;">
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
            <button type="button" class="btn-1" onclick="fn_endorsement_billing.summary.save();" style="">SAVE</button>          
            <button type="button" class="btn-1" onclick="fn_endorsement_billing.summary.cerrar_ventana();" style="margin-right:10px;background:#e8051b;">Cancel</button> 
        </div>
    </div>
    </div>
</div>
<!-- PAYMENTS -->
<div id="edit_form_payment" class="popup-form" style="height: 97%;width:80%;">
    <div class="p-header">
        <h2>ENDORSEMENT INVOICE PAYMENT - (EDIT OR ADD)</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('edit_form_payment');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container" style="height: 94%;overflow-y: auto;">
    <div>
        <table class="data_invoice popup-datagrid" style="width: 100%;margin-top:0px;margin-bottom: 20px;" cellpadding="0" cellspacing="0">
        <thead>
                <tr class="grid-head2">
                    <td class="etiqueta_grid">Invoice No.</td>
                    <td class="etiqueta_grid">Finanging</td>
                    <td class="etiqueta_grid">Date</td>
                    <td class="etiqueta_grid">Total</td>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <form>
            <fieldset style="padding-bottom: 5px;">
            <legend>General Data</legend>
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
                        <input tabindex="7" name="iMonto" class="required-field decimals" type="text" value="" style="width: 97%;" onchange="fn_payments.payments.get_saldos();">
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
            <button type="button" class="btn-1" onclick="fn_endorsement_billing.pay_save();" style="">SAVE</button>          
            <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('edit_form_payment');" style="margin-right:10px;background:#e8051b;">Cancel</button> 
        </div>
    </div>
    </div>
</div>
<!-- FOOTER -->
<?php include("footer.php"); ?> 
</body>
</html>
<?php } ?>