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
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);  
        fn_policies.init();
        fn_policies.fillgrid();
        $.unblockUI();
        
        $('#dialog_delete_policy').dialog({
            modal: true,
            autoOpen: false,
            width : 450,
            height : 430,
            resizable : false,
            buttons : {
                'YES' : function() {
                    clave = $('#id_policy').val();
                    $(this).dialog('close');
                    fn_policies.delete_policy(clave);             
                },
                 'NO' : function(){
                    $(this).dialog('close');
                }
            }
        });
        
        $('#dialog_report_policies').dialog({
            modal: true,
            autoOpen: false,
            width : 650,
            height : 510,
            resizable : false,
            buttons : {
                'DOWNLOAD EXCEL FILE' : function() {
                   //Parametros:
                   var company   = $("#dialog_report_policies .flt_company").val();
                   var broker    = $("#dialog_report_policies .flt_broker").val(); 
                   var insurance = $("#dialog_report_policies .flt_insurance").val(); 
                   var politype  = $("#dialog_report_policies .flt_type").val();  
                   var dExp_init = $("#dialog_report_policies #flt_dateFrom").val(); 
                   var dExp_endi = $("#dialog_report_policies #flt_dateTo").val();   
                   
                   if(dExp_init != "" && dExp_endi != ""){
                        window.open('xlsx_policies_report.php?company='+company+'&broker='+broker+'&insurance='+insurance+'&policytype='+politype+'&dExp_init='+dExp_init+'&dExp_endi='+dExp_endi);
                   }else{fn_solotrucking.mensaje("Please select before a valid dates."); }             
                },
                 'CANCEL' : function(){
                    $(this).dialog('close');
                }
            }
        });
        
        $('#dialog_driver_unit').dialog({
            modal: true,
            autoOpen: false,
            width : 350,
            height : 200,
            resizable : false,
            buttons : {
                'OK' : function() {
                    var clave = $('#dialog_driver_unit select[name=iConsecutivoCompania]').val();
                    var nombre= $('#dialog_driver_unit select[name=iConsecutivoCompania] option:selected').text();
                    if(clave != ""){
                        $(this).dialog('close');
                        fn_policies.get_list_description(clave,nombre); 
                    }else{fn_solotrucking.mensaje("Please select before a company from the list");}
                                
                },
                 'CLOSE' : function(){
                    $(this).dialog('close');
                }
            }
        });  
        
        $('#dialog_report_history_list').dialog({
            modal: true,
            autoOpen: false,
            width : 650,
            height : 510,
            resizable : false,
            buttons : {
                'DOWNLOAD EXCEL FILE' : function() {
                   //Parametros:
                   var company = $("#dialog_report_history_list .flt_company").val();
                   var type    = $("#dialog_report_history_list .flt_type").val(); 
                   var policy  = $("#dialog_report_history_list .flt_policies").val();    
                   
                   if(company != "" && policy != ""){
                        window.open('xlsx_report_list.php?company='+company+'&reporttype='+type+'&policy='+policy);
                   }
                   else{
                       fn_solotrucking.mensaje("Please select before a valid data."); 
                       //$("#dialog_report_history_list .flt_company").addClass('error');
                   }             
                },
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
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
var fn_policies = {
        domroot:"#ct_policies",
        data_grid: "#data_grid_policies",
        form : "#policies_edit_form #policy_data_form",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "A.iConsecutivo",
        filtro_table : 'active', 
        init : function(){
            fn_policies.fillgrid();
            
            $('.num').keydown(fn_solotrucking.inputnumero);  
            $('.decimal').keydown(fn_solotrucking.inputdecimals);  
            //Cargar companias, tipos y brokers:
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_companies"},
                async : true,
                dataType : "json",
                success : function(data){
                    $(fn_policies.form + " #iConsecutivoCompania").empty().append(data.select);
                    $("#file_edit_form #iConsecutivoCompania").empty().append(data.select); 
                    
                    //Reportes Select:
                    $("#dialog_report_policies .flt_company").empty().append(data.select);
                    $("#dialog_report_policies .flt_company option:first-child").text('All'); 
                    
                    //Reportes Select:
                    $("#dialog_report_history_list .flt_company").empty().append(data.select);
                    $("#dialog_report_history_list .flt_company option:first-child").text('All'); 
                    
                    //List de drivers/vehicles dialog:
                    $("#dialog_driver_unit select[name=iConsecutivoCompania]").empty().append(data.select);  
                }
            });
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_brokers"},
                async : true,
                dataType : "json",
                success : function(data){
                    $(fn_policies.form + " #iConsecutivoBrokers").empty().append(data.select);
                    //Reportes Select:
                    $("#dialog_report_policies .flt_broker").empty().append(data.select);
                    $("#dialog_report_policies .flt_broker option:first-child").text('All'); 
                }
            });
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_financing_insurances"},
                async : true,
                dataType : "json",
                success : function(data){
                    $(fn_policies.form + " #iConsecutivoInsurancePremiumFinancing").empty().append(data.select);
                    //Reportes Select:
                    /*$("#dialog_report_policies .flt_broker").empty().append(data.select);
                    $("#dialog_report_policies .flt_broker option:first-child").text('All');*/ 
                }
            });
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_policy_types"},
                async : true,
                dataType : "json",
                success : function(data){
                    $(fn_policies.form + " #iTipoPoliza").empty().append(data.select);
                    //Reportes Select:
                    $("#dialog_report_policies .flt_type").empty().append(data.select);
                    $("#dialog_report_policies .flt_type option:first-child").text('All');  
                }
            });
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_insurances"},
                async : true,
                dataType : "json",
                success : function(data){
                    $(fn_policies.form + " #iConsecutivoAseguranza").empty().append(data.select);
                    //Reportes Select:
                    $("#dialog_report_policies .flt_insurance").empty().append(data.select);
                    $("#dialog_report_policies .flt_insurance option:first-child").text('All'); 
                }
            });
            //Filtrado con la tecla enter
            $(fn_policies.data_grid + ' .grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_policies.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_policies.filtraInformacion();
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
            
            //FILES
            new AjaxUpload('#btnPolicyJacker', {
                    action: 'funciones_policies.php',
                    onSubmit : function(file , ext){
                        if (!(ext && (/^(pdf)$/i.test(ext))  )){
                            var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: Invalid file format, please a PDF File.</p>';
                            fn_solotrucking.mensaje(mensaje);
                            return false;
                        }else{
                            
                            this.setData({
                                'accion':'upload_policy',
                                'iConsecutivo':$(fn_policies.form +' #iConsecutivoArchivo').val(), 
                                'iConsecutivoCompania':$(fn_policies.form + ' #iConsecutivoCompania').val(),
                                'sNombreCompania':$(fn_policies.form +' #iConsecutivoCompania option:selected').text(),
                                'iConsecutivoPoliza':$(fn_policies.form + ' #iConsecutivo').val(),
                                'eArchivo' : 'policy_jacker' 
                            });
                            $('#txtPolicyJacker').val('loading...');
                            this.disable(); 
                        }
                    },
                    onComplete : function(file,response){  
                        var respuesta = JSON.parse(response);
                        switch(respuesta.error){
                            case '0':
                                $('#txtPolicyJacker').val(respuesta.name_file);
                                this.enable();
                                $('#iConsecutivoArchivo').val(respuesta.id_file);
                                fn_solotrucking.mensaje(respuesta.mensaje);
                                fn_policies.save();
                            break;
                            case '1':
                               fn_solotrucking.mensaje(respuesta.mensaje);
                               $('#txtPolicyJacker').val(''); 
                               this.enable();
                            break;
                        }   
                    }        
            }); 
            //PFA
            new AjaxUpload('#btnPolicyPFA', {
                    action: 'funciones_policies.php',
                    onSubmit : function(file , ext){
                        if (!(ext && (/^(pdf)$/i.test(ext))  )){
                            var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: Invalid file format, please a PDF File.</p>';
                            fn_solotrucking.mensaje(mensaje);
                            return false;
                        }else{
                            
                            this.setData({
                                'accion':'upload_policy',
                                'iConsecutivo':$(fn_policies.form + ' #iConsecutivoArchivoPFA').val(), 
                                'iConsecutivoCompania':$(fn_policies.form +' #iConsecutivoCompania').val(),
                                'sNombreCompania':$(fn_policies.form +' #iConsecutivoCompania option:selected').text(),
                                'iConsecutivoPoliza':$(fn_policies.form +' #iConsecutivo').val(),
                                'eArchivo' : 'pfa' 
                            });
                            $('#txtPolicyPFA').val('loading...');
                            this.disable(); 
                        }
                    },
                    onComplete : function(file,response){  
                        var respuesta = JSON.parse(response);
                        switch(respuesta.error){
                            case '0':
                                $('#txtPolicyPFA').val(respuesta.name_file);
                                this.enable();
                                $('#iConsecutivoArchivoPFA').val(respuesta.id_file);
                                fn_solotrucking.mensaje(respuesta.mensaje);
                                fn_policies.save();
                            break;
                            case '1':
                               fn_solotrucking.mensaje(respuesta.mensaje);
                               $('#txtPolicyPFA').val(''); 
                               this.enable();
                            break;
                        }   
                    }        
            }); 
            
            /*----- DRIVER UNIT -------*/
            new AjaxUpload('#btnFile', {
                    action   : 'funciones_policies_new.php',
                    onSubmit : function(file , ext){
                        if (!(ext && (/^(xls)$/i.test(ext) || /^(xlsx)$/i.test(ext)))){
                            var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: Invalid file format, please a Excel (xls) File.</p>';
                            fn_solotrucking.mensaje(mensaje);
                            return false;
                        }else{
                            
                            $("#file_edit_form .required-field").removeClass("error");
                            var policies_selected = "";
                            var valid = true;
                            var msj   = "";
                            $("#file_edit_form .company_policies .num_policies" ).each(function(index){
                                if($(this).is(':checked')){if(policies_selected != ''){policies_selected += "," + this.value; }else{policies_selected += this.value;}}
                            });
                            
                            //Revisamos campos obligatorios:
                            $("#file_edit_form .required-field").each(function(){
                               if($(this).val() == ""){valid = false; $(this).addClass('error');msj = "<li>You must capture the required fields.</li>";}
                            });
                            
                            if(policies_selected == "" || policies_selected == null){valid = false; msj = "<li>You must select at least one option from the policies.</li>";}
                            
                            if(valid){
                                this.setData({
                                    'accion'              : 'upload_list_file', 
                                    'iConsecutivoCompania': $('#file_edit_form #iConsecutivoCompania').val(),
                                    'iConsecutivoPolizas' : policies_selected,
                                    'eTipoLista'          : $("#file_edit_form select[name=File_eTipoLista]").val(),
                                });
                                $('#txtFile').val('loading...');
                                this.disable();
                            }else{
                                fn_solotrucking.mensaje('<p>Please check the following::</p><ul>'+msj+'</ul>');
                                return false;
                            }
                        }
                    },
                    onComplete : function(file,response){  
                            //var respuesta = JSON.parse(response);
                            var respuesta = $.parseJSON(response);
                            switch(respuesta.error){
                                case '0':
                                    $('#txtFile').val(respuesta.name_file);
                                    this.enable();
                                    fn_solotrucking.mensaje(respuesta.mensaje);
                                    $('#reporte_policy_update').empty().append(respuesta.reporte);
                                break;
                                case '1':
                                   fn_solotrucking.mensaje(respuesta.mensaje);
                                   $('#txtFile').val(''); 
                                   this.enable();
                                   $('#reporte_policy_update').empty();
                                break;
                            } 
                    }        
            }); 
            $("#driver_tabs").tabs();
            //Cargar Modelos para unidades:
            $.ajax({             
                type:"POST", 
                url:"funciones_endorsements.php", 
                data:{accion:"get_unit_models"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $("#unit_edit_form #iModelo").empty().append(data.select);
                     
                }
            });
            //Cargar Radio para unidades:
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_unit_radio"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $("#unit_edit_form #iConsecutivoRadio").empty().append(data.select);
                     
                }
            });
            //Cargar Años:
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_years"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $("#unit_edit_form #iYear").empty().append(data.select);
                     
                }
            });
            //Filtrar listas grid:
            $('#driver_tabs #drivers_active_table #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {event.preventDefault();fn_policies.list.filtraInformacion();}
                if(event.keyCode == '27'){event.preventDefault();$(this).val('');fn_policies.list.filtraInformacion();}
            });
            $('#driver_tabs #units_active_table #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {event.preventDefault();fn_policies.list.units_filtraInformacion();}
                if(event.keyCode == '27'){event.preventDefault();$(this).val('');fn_policies.list.units_filtraInformacion();}
            });
            
            // CERTIFICATE UPLOAD PDF
            new AjaxUpload('#btnsCertificatePDF', {
                action: 'funciones_certificate_pdf_upload.php',
                onSubmit : function(file , ext){
                    if (!(ext && /^(pdf)$/i.test(ext))){
                        var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The file format is not valid.</p>';
                        fn_solotrucking.mensaje(mensaje);
                        return false;
                    }else{
                        this.setData({
                            'accion': 'upload_certificate',
                            'iConsecutivoCompania': fn_certificate.id_company,
                            'sNombreCompania'     : $("#policies_edit_form select[name=iConsecutivoCompania] option:selected ").text(),
                            'iConsecutivo'        : $("#certificate_edit_form input[name=iConsecutivo]").val(),
                            'dFechaVencimiento'   : $('#certificate_edit_form input[name=dFechaVencimiento]').val(),
                            'eOrigenCertificado'  : $("#certificate_edit_form [name=eOrigenCertificado]").val()
                        });
                        $('#txtsCertificatePDF').val('loading...');
                        this.disable(); 
                    }
                },
                onComplete : function(file,response){  
                    var respuesta = JSON.parse(response);
                    switch(respuesta.error){
                        case '0':
                            $('#txtsCertificatePDF').val(respuesta.name_file);
                            this.enable();
                            $('#iConsecutivoCertificatePDF').val(respuesta.id_file);
                            fn_solotrucking.mensaje(respuesta.mensaje);
                        break;
                        case '1':
                           fn_solotrucking.mensaje(respuesta.mensaje);
                           $('#txtsCertificatePDF').val(''); 
                           this.enable();
                        break;
                    }   
                }        
            }); 
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_policies.php", 
                data:{
                    accion:"get_policies",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_policies.pagina_actual, 
                    filtroInformacion : fn_policies.filtro,  
                    ordenInformacion : fn_policies.orden,
                    sortInformacion : fn_policies.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_policies.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_policies.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_policies.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_policies.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_policies.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_policies.pagina_actual = data.pagina; 
                    fn_policies.edit();
                    fn_policies.delete_confirm();
                    $(fn_policies.data_grid + ' .btn-icon.expired_policies').removeClass('active');
                    $(fn_policies.data_grid + ' .btn-icon.active_policies').addClass('active');
                    fn_policies.filtro_table = 'active';
                }
            }); 
        },
        fillgrid_expired: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_policies.php", 
                data:{
                    accion:"get_expired_policies",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_policies.pagina_actual, 
                    filtroInformacion : fn_policies.filtro,  
                    ordenInformacion : fn_policies.orden,
                    sortInformacion : fn_policies.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_policies.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_policies.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_policies.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_policies.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_policies.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_policies.pagina_actual = data.pagina; 
                    $(fn_policies.data_grid + ' .btn-icon.active_policies').removeClass('active');
                    $(fn_policies.data_grid + ' .btn-icon.expired_policies').addClass('active');
                    fn_policies.filtro_table = 'expired'; 
                }
            }); 
        },
        upload_file_form : function(){
          $('#file_edit_form input, #file_edit_form select').val('');
          $("#file_edit_form .company_policies").empty();
          $('#reporte_policy_update').empty();
          fn_popups.resaltar_ventana('file_edit_form'); 
        },
        add : function(){
           $(fn_policies.form + ' input,' + fn_policies.form +' select').val('').removeClass('error');
           $(fn_policies.form+' .mensaje_valido').empty().append('The fields containing an (*) are required.');
           $('.policy_jacker_file, #certificate_edit_form').hide();
           $(fn_policies.form + ' #sNumeroPoliza ,' + fn_policies.form + ' #iConsecutivoCompania').removeAttr('readonly').removeClass('readonly');
           fn_policies.valida_tipo_poliza(false);
           fn_popups.resaltar_ventana('policies_edit_form');  
        },
        edit : function (){
            $(fn_policies.data_grid + " tbody td .edit").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                fn_policies.get_data(clave);
          });  
        },
        get_data : function(clave){
            $.ajax({
                type:"POST",
                url:"funciones_policies.php",
                data:{
                    "accion"  :"get_policy",
                    "clave"   : clave,
                    "domroot" : "policies_edit_form"
                },
                async : true,
                dataType : "json",
                success : function(data){
                    if(data.error == '0'){
                       $('#policies_edit_form .file_certificate  .trash, #certificate_edit_form').hide(); 
                       $(fn_policies.form+' input, '+fn_policies.form+' select').val('').removeClass('error'); 
                       $(fn_policies.form + ' #sNumeroPoliza ,' + fn_policies.form + ' #iConsecutivoCompania').attr('readonly','readonly').addClass('readonly');
                       eval(data.fields); 
                       fn_policies.validate_type();
                       fn_policies.valida_tipo_poliza(true);
                       
                       if($('#policies_edit_form :input[id=iConsecutivoArchivo]').val() != ""){$('#policies_edit_form .policyJackerField .trash').show();}
                       if($('#policies_edit_form :input[id=iConsecutivoArchivoPFA]').val() != ""){$('#policies_edit_form .policyPFAField .trash').show();}
                       $('.policy_jacker_file ').show(); 
                       
                       fn_certificate.id_company = $("#policies_edit_form #iConsecutivoCompania").val();
                       fn_certificate.init();
                       fn_popups.resaltar_ventana('policies_edit_form');
                         
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                }
            });    
        },
        save : function (){
           //Validate Fields:
           var sNumeroPoliza = $(fn_policies.form + ' #sNumeroPoliza');
           var iConsecutivoBrokers = $(fn_policies.form + ' #iConsecutivoBrokers');
           var iTipoPoliza = $(fn_policies.form + ' #iTipoPoliza'); 
           var iConsecutivoCompania = $(fn_policies.form + ' #iConsecutivoCompania');
           todosloscampos = $([]).add(sNumeroPoliza).add(iConsecutivoBrokers).add(iTipoPoliza).add(iConsecutivoCompania);
           todosloscampos.removeClass( "error" );
           var valid = true;
           if(iConsecutivoCompania.val() == ''){
               fn_solotrucking.mensaje('Please check the company field has a value.');iConsecutivoCompania.addClass('error');
               valid=false;
               return false;
           }
           valid = valid && fn_solotrucking.checkLength( sNumeroPoliza, "Number", 1, 30);
           /*if(iConsecutivoBrokers.val() == ''){
               fn_solotrucking.mensaje('Please check the broker field has a value.');iConsecutivoBrokers.addClass('error');
               valid=false;
               return false;
           } */
           if(iTipoPoliza.val() == ''){
               fn_solotrucking.mensaje('Please check the field for the type has a value.');iTipoPoliza.addClass('error');
               valid=false;
               return false;
           }
          
           if(valid){
             if($(fn_policies.form + ' #sNumeroPoliza').attr("readonly")){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
             struct_data_post.action ="save_policy";
             struct_data_post.domroot= fn_policies.form; 
                $.post("funciones_policies.php",struct_data_post.parse(),
                function(data){
                    switch(data.error){
                     case '0':
                        fn_solotrucking.mensaje(data.msj);
                        fn_policies.get_data(data.clave);
                     break;
                     case '1': fn_solotrucking.mensaje(data.msj); break;
                    }
                },"json");
           }
            
        },
        validate_type : function(){
            if($('#users_edit_form #iConsecutivoTipoUsuario').val() != 2){
                $('#users_edit_form .companies_option').hide();
                $('#users_edit_form #iConsecutivoCompania').val('');
            }else{
                $('#users_edit_form .companies_option').show();
            }
        },
        delete_confirm : function(){
          $(fn_policies.data_grid + " tbody .btn_delete").bind("click",function(){
               var clave  = $(this).parent().parent().find("td:eq(0)").html();
               var policy = $(this).parent().parent().find("td:eq(2)").html();
               $('#dialog_delete_policy #id_policy').val(clave);
               $('#dialog_delete_policy p > span').empty().text(policy);
               $('#dialog_delete_policy').dialog( 'open' );
               return false;
           });  
        },
        delete_policy : function(id){
          $.post("funciones_policies.php",{accion:"delete_policy", 'clave': id},
           function(data){
                fn_solotrucking.mensaje(data.msj);
                if(fn_policies.filtro_table == 'active'){fn_policies.fillgrid();}else if(fn_policies.filtro_table == 'expired'){fn_policies.fillgrid_expired();}
           },"json");  
        },
        firstPage : function(){
            if($(fn_policies.data_grid+" #pagina_actual").val() != "1"){
                fn_policies.pagina_actual = "";
                if(fn_policies.filtro_table == 'active'){fn_policies.fillgrid();}else if(fn_policies.filtro_table == 'expired'){fn_policies.fillgrid_expired();}
            }
        },
        previousPage : function(){
            if($(fn_policies.data_grid+" #pagina_actual").val() != "1"){
                fn_policies.pagina_actual = (parseInt($(fn_policies.data_grid+" #pagina_actual").val()) - 1) + "";
                if(fn_policies.filtro_table == 'active'){fn_policies.fillgrid();}else if(fn_policies.filtro_table == 'expired'){fn_policies.fillgrid_expired();}
            }
        },
        nextPage : function(){
            if($(fn_policies.data_grid+" #pagina_actual").val() != $(fn_policies.data_grid+" #paginas_total").val()){
                fn_policies.pagina_actual = (parseInt($(fn_policies.data_grid+" #pagina_actual").val()) + 1) + "";
                if(fn_policies.filtro_table == 'active'){fn_policies.fillgrid();}else if(fn_policies.filtro_table == 'expired'){fn_policies.fillgrid_expired();}
            }
        },
        lastPage : function(){
            if($(fn_policies.data_grid+" #pagina_actual").val() != $(fn_policies.data_grid+" #paginas_total").val()){
                fn_policies.pagina_actual = $(fn_policies.data_grid+" #paginas_total").val();
                if(fn_policies.filtro_table == 'active'){fn_policies.fillgrid();}else if(fn_policies.filtro_table == 'expired'){fn_policies.fillgrid_expired();};
            }
        }, 
        ordenamiento : function(campo,objeto){
            $(fn_policies.data_grid + " .grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_policies.orden){
                if(fn_policies.sort == "ASC"){
                    fn_policies.sort = "DESC";
                    $(fn_policies.data_grid + " .grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_policies.sort = "ASC";
                    $(fn_policies.data_grid + " .grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_policies.sort = "ASC";
                fn_policies.orden = campo;
                $(fn_policies.data_grid + " .grid-head2 td:eq("+objeto+")").addClass('down');
            }
            if(fn_policies.filtro_table == 'active'){fn_policies.fillgrid();}else if(fn_policies.filtro_table == 'expired'){fn_policies.fillgrid_expired();}

            return false;
        }, 
        filtraInformacion : function(){
            fn_policies.pagina_actual = 0;
            fn_policies.filtro = "";
            if($(fn_policies.data_grid+" .flt_pid").val() != ""){ fn_policies.filtro += "A.iConsecutivo|"+$(fn_policies.data_grid+" .flt_pid").val()+","}
            if($(fn_policies.data_grid+" .flt_pcompany").val() != ""){ fn_policies.filtro += "sNombreCompania|"+$(fn_policies.data_grid+" .flt_pcompany").val()+","} 
            if($(fn_policies.data_grid+" .flt_policynumber").val() != ""){ fn_policies.filtro += "sNumeroPoliza|"+$(fn_policies.data_grid+" .flt_policynumber").val()+","} 
            //if($(fn_policies.data_grid+" .flt_pbroker").val() != ""){ fn_policies.filtro += "sName|"+$(fn_policies.data_grid+" .flt_pbroker").val()+","}  
            if($(fn_policies.data_grid+" .flt_policytype").val() != ""){ fn_policies.filtro += "sDescripcion|"+$(fn_policies.data_grid+" .flt_policytype").val()+","} 
            if($(fn_policies.data_grid+" .flt_policystartdate").val() != ""){ fn_policies.filtro += "dFechaInicio|"+$(fn_policies.data_grid+" .flt_policystartdate").val()+","} 
            if($(fn_policies.data_grid+" .flt_policyexpdate").val() != ""){ fn_policies.filtro += "dFechaCaducidad|"+$(fn_policies.data_grid+" .flt_policyexpdate").val()+","}    
            
            if(fn_policies.filtro_table == 'active'){fn_policies.fillgrid();}else if(fn_policies.filtro_table == 'expired'){fn_policies.fillgrid_expired();}
            
        }, 
        valida_tipo_poliza : function(edit){
            
            if(edit == ""){edit = false;}
            var iTipoPoliza = $("#policies_edit_form #iTipoPoliza").val();
            // ocultar todos los campos primero:
            $("#policies_edit_form .premium_amounts_additional, #policies_edit_form .premium_amounts_GL, #policies_edit_form .cargo_policie_type").hide();
            if(!(edit)){$("#policies_edit_form .premium_amounts_additional input, #policies_edit_form .premium_amounts_GL input").val('');}
            
            //MTC-TI
            if(iTipoPoliza == "5" || iTipoPoliza == "10"){
                if(iTipoPoliza == "5"){ var textlimit = "Trailer Interchange Limit $:"; var textdedu = "Trailer Interchange Deductible $:";}
                else{ var textlimit = "Reefer Breakdown Limit $:"; var textdedu = "Reefer Breakdown Deductible $:"; }
                
                $("#policies_edit_form .premium_amounts_additional > td:first-child .field_item label").text(textlimit);
                $("#policies_edit_form .premium_amounts_additional > td:last-child  .field_item label").text(textdedu);
                $("#policies_edit_form .premium_amounts_additional").show();
                $("#policies_edit_form .cargo_policie_type").show();
            }
            //MTC
            else if(iTipoPoliza == "2"){
                $("#policies_edit_form .cargo_policie_type").show();
                $("#policies_edit_form .premium_amounts").show();
            }
            //CGL
            else if(iTipoPoliza == "6"){
                $("#policies_edit_form .premium_amounts").hide();
                $("#policies_edit_form .premium_amounts_GL").show(); 
            }else{
                $("#policies_edit_form .premium_amounts").show(); 
                $("#policies_edit_form .cargo_policie_type").show();
            }
        },
        upload_file : function(){
            $(fn_policies.data_grid + " tbody td .btn_upload_policy").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                
           });  
        },
        delete_file : function(fileType){
            $.ajax({
                type:"POST",
                url:"funciones_policies.php",
                data:{
                    'accion'              : "delete_file",
                    'iConsecutivo'        : $(fn_policies.form +' #iConsecutivoArchivo').val(), 
                    'iConsecutivoCompania': $(fn_policies.form + ' #iConsecutivoCompania').val(),
                    'iConsecutivoPoliza'  : $(fn_policies.form + ' #iConsecutivo').val(),
                    'eArchivo'            : fileType 
                },
                async : true,
                dataType : "json",
                success : function(data){
                    fn_solotrucking.mensaje(data.msj);
                    if(data.error == '0'){fn_policies.get_data($(fn_policies.form + ' #iConsecutivo').val());}
                }
            });        
        }, 
        //FUNCIONES PARA SUBIR TXT DE DRIVER OR UNITS:
        get_company_policies : function(){
            var company = $('#file_edit_form #iConsecutivoCompania').val();
            $("#file_edit_form .company_policies").empty().append('<div style="width:100%;margin:5px auto;text-align:center;"><img src="images/ajax-loader.gif" border="0" width="16" height="16" alt="ajax-loader.gif (673 bytes)"></div>');
            if(company != ''){
                $.ajax({             
                    type:"POST", 
                    url:"funciones_policies.php", 
                    data:{accion:"get_company_policies",company : company},
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){
                            $("#file_edit_form .company_policies").empty().append(data.checkboxes); 
                        }
                    }
                }); 
            }else{
                fn_solotrucking.actualizarMensajeAlerta('Please select a company to upload file.');
                $("#file_edit_form .company_policies").empty();
            }
        },
        get_list_open : function(){
           $('#dialog_driver_unit :text').val('');
           $('#dialog_driver_unit').dialog( 'open' );
           return false; 
        },
        get_list_description : function(clave,nombre){

           
            fn_policies.list.id_company = clave;
            fn_policies.list.fill_drivers_actives();
            fn_policies.list.fill_units_actives();
            $("#driver_tabs" ).tabs('option', 'active', 0);
            $('#drivers_active_table,#units_active_table ').show();
            $('#drivers_edit_form,#unit_edit_form ').hide(); 
            
            $('#driver_list_form h2').empty().append('DRIVERS/VEHICLES LIST OF: '+nombre);
            fn_popups.resaltar_ventana('driver_list_form');
        },
        list : {
            domroot_nav : "#driver_tabs",
            filtro : "",
            drivers_pagina_actual : "",
            units_pagina_actual : "",
            sort : "ASC",
            orden_driver : "sNombre",
            orden_unit : "sVIN",
            id_policy : "",
            id_company : "",
            cargar_polizas : function(domroot){
                $.ajax({             
                    type:"POST", 
                    url:"funciones_policies.php", 
                    data:{accion:"get_company_policies",company : fn_policies.list.id_company},
                    async : false,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){
                            $(domroot + " .company_policies").empty().append(data.checkboxes); 
                            $(domroot + " .company_policies input[type=checkbox]").prop('disabled','disabled'); 
                        }
                    }
                });
            },
            filtraInformacion : function(){
                fn_policies.list.drivers_pagina_actual = 0;
                fn_policies.list.filtro = "";
                if($(fn_policies.list.domroot_nav+" .flt_dName").val() != ""){ fn_policies.list.filtro += "sNombre|"+$(fn_policies.list.domroot_nav+" .flt_dName").val()+","}
                if($(fn_policies.list.domroot_nav+" .flt_dDob").val() != ""){ fn_policies.list.filtro += "dFechaNacimiento|"+$(fn_policies.list.domroot_nav+" .flt_dDob").val()+","} 
                if($(fn_policies.list.domroot_nav+" .flt_dLicense").val() != ""){ fn_policies.list.filtro += "iNumLicencia|"+$(fn_policies.list.domroot_nav+" .flt_dLicense").val()+","} 
                if($(fn_policies.list.domroot_nav+" .flt_dtype").val() != ""){ fn_policies.list.filtro += "eTipoLicencia|"+$(fn_policies.list.domroot_nav+" .flt_dtype").val()+","}  
                if($(fn_policies.list.domroot_nav+" .flt_dExpire").val() != ""){ fn_policies.list.filtro += "dFechaExpiracionLicencia|"+$(fn_policies.list.domroot_nav+" .flt_dExpire").val()+","} 
                //if($(fn_policies.list.domroot_nav+" .flt_dApp").val() != ""){ fn_policies.list.filtro += "dFechaAplicacion|"+$(fn_policies.list.domroot_nav+" .flt_dApp").val()+","} 
                if($(fn_policies.list.domroot_nav+" .flt_dYears").val() != ""){ fn_policies.list.filtro += "iExperienciaYear|"+$(fn_policies.list.domroot_nav+" .flt_dYears").val()+","} 
                fn_policies.list.fill_drivers_actives();
            },    
            fill_drivers_actives : function(){
                 $.ajax({             
                    type:"POST", 
                    url:"funciones_policies.php", 
                    data:{
                        accion:"get_drivers_active",
                        registros_por_pagina : "40", 
                        iConsecutivoPoliza :  fn_policies.list.id_policy,
                        iConsecutivoCompania : fn_policies.list.id_company, 
                        pagina_actual : fn_policies.list.drivers_pagina_actual, 
                        filtroInformacion : fn_policies.list.filtro,  
                        ordenInformacion : fn_policies.list.orden_driver,
                        sortInformacion : fn_policies.list.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_policies.list.domroot_nav+" #drivers_active_table tbody").empty().append(data.tabla);
                        $(fn_policies.list.domroot_nav+" #drivers_active_table > tbody > tr:even").addClass('gray');
                        $(fn_policies.list.domroot_nav+" #drivers_active_table > tbody > tr:odd").addClass('white');
                        $(fn_policies.list.domroot_nav+" #drivers_active_table tfoot .paginas_total").val(data.total);
                        $(fn_policies.list.domroot_nav+" #drivers_active_table tfoot .pagina_actual").val(data.pagina);
                        fn_policies.list.drivers_pagina_actual = data.pagina; 
                        fn_policies.list.drivers_edit();
                        //fn_policies.list.delete_confirm();
                    }
                }); 
            },
            drivers_firstPage : function(){
                if($("#drivers_active_table .pagina_actual").val() != "1"){
                    fn_policies.list.drivers_pagina_actual = "";
                    fn_policies.list.fill_drivers_actives();
                }
            },
            drivers_previousPage : function(){
                if($("#drivers_active_table .pagina_actual").val() != "1"){
                    fn_policies.list.drivers_pagina_actual = (parseInt($("#drivers_active_table .pagina_actual").val()) - 1) + "";
                    fn_policies.list.fill_drivers_actives();
                }
            },
            drivers_nextPage : function(){
                if($("#drivers_active_table .pagina_actual").val() != $("#drivers_active_table .paginas_total").val()){
                    fn_policies.list.drivers_pagina_actual = (parseInt($("#drivers_active_table .pagina_actual").val()) + 1) + "";
                    fn_policies.list.fill_drivers_actives();
                }
            },
            drivers_lastPage : function(){
                if($("#drivers_active_table .pagina_actual").val() != $("#drivers_active_table .paginas_total").val()){
                    fn_policies.list.drivers_pagina_actual = $("#drivers_active_table .paginas_total").val();
                    fn_policies.list.fill_drivers_actives();
                }
            },
            drivers_add : function(){
                $('#drivers_edit_form :text ').val(''); 
                $('#drivers_edit_form #iConsecutivoCompania').val(fn_policies.list.id_company);
                fn_policies.list.cargar_polizas('#drivers_edit_form');
                $('#drivers_active_table').hide();
                $('#drivers_edit_form').show();
                
                //fn_solotrucking.get_date('#drivers_edit_form .fecha');
            },
            drivers_save : function(){
                
                todosloscampos = $('#data_driver_form input, #data_driver_form select');
                todosloscampos.removeClass( "error" );
                valid = true;
                var policies_selected = "";
                
                //Revsamos los valores marcados como required:
                $("#data_driver_form .required-field" ).each(function( index ){
                     if($(this).val() == ''){
                        $(this).addClass('error'); 
                        valid = false;
                     }
                });
                if(!valid){fn_solotrucking.mensaje('Please check all fields are required for the driver.'); }
                
                $("#drivers_edit_form .company_policies .num_policies" ).each(function( index ){
                       if(this.checked){
                          if(policies_selected != ''){policies_selected += "," + this.value; }else{policies_selected += this.value;} 
                       }
                         
                });
                if(policies_selected == ''){valid = false;}else{$("#data_driver_form #siConsecutivosPolizas").val(policies_selected);}
                
                
                if(valid){
                    
                    if($('#drivers_edit_form #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";} 
                    struct_data_post.action = "save_driver";
                    struct_data_post.domroot= "#data_driver_form";  
                    $.post("funciones_policies.php",struct_data_post.parse(),
                    function(data){
                        fn_solotrucking.mensaje(data.msj);
                        if(data.error == '0'){
                            fn_policies.list.fill_drivers_actives(); 
                            $('#drivers_active_table').show();
                            $('#drivers_edit_form').hide();
                        }    
                    },"json");
                    
                    
                }else{
                   fn_solotrucking.mensaje('Please select the policies to which you want to add the driver.'); 
                }

            },
            drivers_edit : function (){
                $("#drivers_active_table tbody td .edit").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").attr('id');
                    fn_policies.list.cargar_polizas('#drivers_edit_form');
                    $.ajax({             
                    type:"POST", 
                    url:"funciones_policies.php", 
                    data:{
                        accion:"get_driver", 
                        clave: clave, 
                        company : fn_policies.list.id_company, 
                        domroot : "drivers_edit_form"
                    },
                    async : false,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){
                           $('#drivers_edit_form input:text, #drivers_edit_form select').val('').removeClass('error'); 
                           //$(fn_policies.form + ' #sNumeroPoliza ,' + fn_policies.form + ' #iConsecutivoCompania').attr('readonly','readonly').addClass('readonly');
                           eval(data.fields); 
                           $('#drivers_active_table').hide();
                           $('#drivers_edit_form').show();
                             
                        }else{
                           fn_solotrucking.mensaje(data.msj);  
                        }       
                    }
                    }); 
              });  
            },
            //UNITS
            fill_units_actives : function(){
                 $.ajax({             
                    type:"POST", 
                    url:"funciones_policies.php", 
                    data:{
                        accion:"get_units_active",
                        registros_por_pagina : "40", 
                        iConsecutivoPoliza :  fn_policies.list.id_policy,
                        iConsecutivoCompania : fn_policies.list.id_company,
                        pagina_actual : fn_policies.list.units_pagina_actual, 
                        filtroInformacion : fn_policies.list.filtro,  
                        ordenInformacion : fn_policies.list.orden_unit,
                        sortInformacion : fn_policies.list.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_policies.list.domroot_nav+" #units_active_table tbody").empty().append(data.tabla);
                        $(fn_policies.list.domroot_nav+" #units_active_table > tbody > tr:even").addClass('gray');
                        $(fn_policies.list.domroot_nav+" #units_active_table > tbody > tr:odd").addClass('white');
                        $(fn_policies.list.domroot_nav+" #units_active_table tfoot .paginas_total").val(data.total);
                        $(fn_policies.list.domroot_nav+" #units_active_table tfoot .pagina_actual").val(data.pagina);
                        fn_policies.list.units_pagina_actual = data.pagina; 
                        fn_policies.list.unit_edit(); 
                    }
                }); 
            },
            units_filtraInformacion : function(){
                fn_policies.list.units_pagina_actual = 0;
                fn_policies.list.filtro = "";
                if($(fn_policies.list.domroot_nav+" .flt_uVIN").val() != ""){ fn_policies.list.filtro += "sVIN|"+$(fn_policies.list.domroot_nav+" .flt_uVIN").val()+","}
                if($(fn_policies.list.domroot_nav+" .flt_uRadio").val() != ""){ fn_policies.list.filtro += "iConsecutivoRadio|"+$(fn_policies.list.domroot_nav+" .flt_uRadio").val()+","} 
                if($(fn_policies.list.domroot_nav+" .flt_uYear").val() != ""){ fn_policies.list.filtro += "iYear|"+$(fn_policies.list.domroot_nav+" .flt_uYear").val()+","} 
                if($(fn_policies.list.domroot_nav+" .flt_uMake").val() != ""){ fn_policies.list.filtro += "C.sDescription|"+$(fn_policies.list.domroot_nav+" .flt_uMake").val()+","}  
                if($(fn_policies.list.domroot_nav+" .flt_uType").val() != ""){ fn_policies.list.filtro += "sTipo|"+$(fn_policies.list.domroot_nav+" .flt_uType").val()+","} 
                if($(fn_policies.list.domroot_nav+" .flt_uWeight").val() != ""){ fn_policies.list.filtro += "sPeso|"+$(fn_policies.list.domroot_nav+" .flt_uWeight").val()+","} 
                fn_policies.list.fill_units_actives();
            },
            units_firstPage : function(){
                if($("#units_active_table .pagina_actual").val() != "1"){
                    fn_policies.list.units_pagina_actual = "";
                    fn_policies.list.fill_units_actives();
                }
            },
            units_previousPage : function(){
                if($("#units_active_table .pagina_actual").val() != "1"){
                    fn_policies.list.units_pagina_actual = (parseInt($("#units_active_table .pagina_actual").val()) - 1) + "";
                    fn_policies.list.fill_units_actives();
                }
            },
            units_nextPage : function(){
                if($("#units_active_table .pagina_actual").val() != $("#units_active_table .paginas_total").val()){
                    fn_policies.list.units_pagina_actual = (parseInt($("#units_active_table .pagina_actual").val()) + 1) + "";
                    fn_policies.list.fill_units_actives();
                }
            },
            units_lastPage : function(){
                if($("#units_active_table .pagina_actual").val() != $("#units_active_table .paginas_total").val()){
                    fn_policies.list.units_pagina_actual = $("#units_active_table .paginas_total").val();
                    fn_policies.list.fill_units_actives();
                }
            },
            unit_add : function(){
                $('#unit_edit_form :text,#unit_edit_form select').val(''); 
                $('#unit_edit_form #iConsecutivoCompania').val(fn_policies.list.id_company);
                fn_policies.list.cargar_polizas('#unit_edit_form');
                $('#units_active_table').hide();
                $('#unit_edit_form').show();

            }, 
            unit_save : function(){
                
                var todosloscampos    = $('#data_unit_form input, #data_unit_form select');
                var valid             = true;
                var policies_selected = "";
                todosloscampos.removeClass( "error" ); 
                
                //Revsamos los valores marcados como required:
                $("#unit_edit_form .required-field" ).each(function( index ){
                     if($(this).val() == ''){
                        $(this).addClass('error'); 
                        valid = false;
                     }
                });
                if(!valid){fn_solotrucking.mensaje('Please check all fields are required for the driver.'); }
                
                $("#unit_edit_form .company_policies .num_policies" ).each(function( index ){
                       if(this.checked){
                          if(policies_selected != ''){policies_selected += "," + this.value; }else{policies_selected += this.value;} 
                       }
                         
                });
                //if(policies_selected == ''){valid = false;}else{$("#unit_edit_form #siConsecutivosPolizas").val(policies_selected); }
                
                
                if(valid){
                    
                    if($('#unit_edit_form #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";} 
                    struct_data_post.action="save_unit";
                    struct_data_post.domroot= "#data_unit_form";  
                    $.post("funciones_policies.php",struct_data_post.parse(),
                    function(data){
                        fn_solotrucking.mensaje(data.msj);
                        if(data.error == '0'){
                            fn_policies.list.fill_drivers_actives(); 
                            $('#units_active_table').show();
                            $('#unit_edit_form').hide();
                        }    
                    },"json");
                    
                    
                }else{
                   fn_solotrucking.mensaje('Please select the policies to which you want to add the unit/trailer.'); 
                }

            },
            unit_edit : function (){
                $("#units_active_table tbody td .edit").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").attr('id'); 
                    fn_policies.list.cargar_polizas('#unit_edit_form');
                    $.ajax({             
                    type:"POST", 
                    url:"funciones_policies.php", 
                    data:{
                        accion:"get_unit", 
                        clave: clave, 
                        company : fn_policies.list.id_company, 
                        domroot : "unit_edit_form"
                    },
                    async : false,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){
                           eval(data.fields); 
                           $('#units_active_table').hide();
                           $('#unit_edit_form').show();
                           fn_policies.list.fill_units_actives();  
                        }else{
                           fn_solotrucking.mensaje(data.msj);  
                        }       
                    }
                    }); 
              });  
            },
            download_report : function(company,reporttype,filtro){
                
                //Autollenar parametros:
                $("#dialog_report_history_list .flt_company").val(company);
                $("#dialog_report_history_list .flt_type").val(reporttype); 
                
                //Cargar polizas:
                fn_policies.list.get_policies(company);
                
                /*if(reporttype != ""){$("#dialog_report_history_list .flt_type").prop('disabled',true).addClass('readonly'); }
                else{$("#dialog_report_history_list .flt_type").removeProp('disabled').removeClass('readonly');}*/ 
                
                $("#dialog_report_history_list").dialog('open');  
            },
            get_policies : function(company){
                
                if(company != ""){
                    $.ajax({             
                        type:"POST", 
                        url :"catalogos_generales.php", 
                        data:{"accion":"get_policies","iConsecutivoCompania":company},
                        async : true,
                        dataType : "json",
                        success : function(data){
                            //Reportes Select:
                            if(data.error == '0'){
                               $("#dialog_report_history_list .flt_policies").empty().append(data.select).removeClass('readonly').removeProp('disabled');
                               //$("#dialog_report_history_list .flt_policies option:first-child").text('All');  
                            }
                            else{
                               fn_solotrucking.mensaje(data.mensaje); 
                               $("#dialog_report_history_list .flt_policies").empty().append('<option value="">Select an option...</option>').addClass('readonly').prop('disabled','disabled');  
                            }
                            
                        }
                    });    
                }
                else{$("#dialog_report_history_list .flt_policies").empty().append('<option value="">Select an option...</option>').addClass('readonly').prop('disabled','disabled');}
                
            }
        },
        //FUNCIONES PARA REPORTE DE POLIZAS:
        dialog_report_open : function(){
            $("#dialog_report_policies :text, #dialog_report_policies select").val("");
            var fechas = fn_solotrucking.obtener_fechas();
            $("#dialog_report_policies #flt_dateFrom").val(fechas[1]); 
            $("#dialog_report_policies #flt_dateTo").val(fechas[2]); 
            $("#dialog_report_policies").dialog('open'); 
        },
         
               
} 

var fn_certificate = {
    id_company : "",
    init : function(){
        if(fn_certificate.id_company != ""){
            $.ajax({
                type:"POST",
                url:"funciones_certificate_pdf_upload.php",
                data:{
                    "accion"   :"get_files", 
                    "clave"    : fn_certificate.id_company, 
                    "domroot"  : "certificate_edit_form",
                    "parametro": "name",
                },
                async : true,
                dataType : "json",
                success : function(data){
                    if(data.error == '0'){ 
                       eval(data.fields); 
                       fn_certificate.valida_tipo_certificado();
                       $("#certificate_edit_form").show();
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }     
                }
            });            
        }    
    },
    get_datapolicies : function(){
        $.ajax({             
            type:"POST", 
            url:"funciones_certificate_pdf_upload.php", 
            data:{'accion':"get_policies",'iConsecutivoCompania':fn_certificate.id_company},
            async : false,
            dataType : "json",
            success : function(data){                               
                if(data.error == '0'){
                    $(".fieldset-certificates #info_policies").show();
                    $(".fieldset-certificates #info_policies table tbody").empty().append(data.policies_information);
                }
            }
        });
    },
    valida_tipo_certificado : function(){
        var tipo = $(".fieldset-certificates [name=eOrigenCertificado]").val();
        if(tipo == 'LAYOUT'){
            $("#certificate_edit_form .campos-layout").show();
            $("#certificate_edit_form .campos-database, #certificate_edit_form #info_policies").hide();
        }else if(tipo == 'DATABASE'){
            fn_certificate.get_datapolicies();
            $("#certificate_edit_form .campos-database").show();
            $("#certificate_edit_form .campos-layout").hide();
        }else{
            $("#certificate_edit_form .campos-database, #certificate_edit_form #info_policies, #certificate_edit_form .campos-layout").hide();
        }
    },
    preview_certificate : function(){
        if(fn_certificate.id_company != ""){
            window.open('pdf_certificate.php?id='+fn_certificate.id_company+'&ca=COMPANY%20NAME&cb=NUMBER%20AND%20ADDRESS&cc=CITY&cd=STATE&ce=ZIPCODE&ds=PREVIEW');
        }
    },
    save : function(){
        var valid = true;
        var msj   = "";
        $("#certificate_edit_form .required-field").removeClass("error");
        //Revisamos campos obligatorios: 
        $("#certificate_edit_form  input.required-field, #certificate_edit_form  select.required-field").each(function(){
           if($(this).val() == ""){valid = false; $(this).addClass('error');msj = "<li>You must capture the required fields.</li>";}
        });
        
        if(valid){ 
          $("#certificate_edit_form input[name=iConsecutivoCompania]").val(fn_certificate.id_company);  
          if($("#certificate_edit_form input[name=iConsecutivo]").val() != ""){struct_data_post_new.edit_mode = "true";}else{struct_data_post_new.edit_mode = "false";}
          struct_data_post_new.action  = "upload_certificate";
          struct_data_post_new.domroot = "#certificate_edit_form";
          $.ajax({             
            type  : "POST", 
            url   : "funciones_certificate_pdf_upload.php", 
            data  : struct_data_post_new.parse(),
            async : true,
            dataType : "json",
            success  : function(data){                               
                switch(data.error){ 
                 case '0':
                    fn_solotrucking.mensaje(data.mensaje);
                    fn_certificate.init();
                 break;
                 case '1': fn_solotrucking.mensaje(data.mensaje); break;
                }
            }
          }); 
            
        }else{fn_solotrucking.mensaje('<p>Please check the following::</p><ul>'+msj+'</ul>');}
    },  
}  

</script> 
<div id="layer_content" class="main-section">
    <div id="ct_policies" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
            <h2>INSURANCE POLICIES</h2>
            <img src="images/data-grid/policy_status.jpg" alt="policy_status.jpg" style="float:right;position: relative;top: -90px;margin-bottom: -100px;"> 
        </div>
        <table id="data_grid_policies" class="data_grid">
        <thead>
            <tr class="grid-head1">
                <td style="width:50px!important;"><input class="flt_pid" type="text" placeholder="ID:"></td> 
                <td style="width:350px;"><input class="flt_pcompany" type="text" placeholder="Company:"></td>
                <td><input class="flt_policynumber" type="text" placeholder="Policy Numer:"></td>
                <td><input class="flt_policytype" type="text" placeholder="Policy Type:"></td> 
                <td><input class="flt_policystartdate" type="text" placeholder="MM/DD/YY"></td> 
                <td><input class="flt_policyexpdate" type="text" placeholder="MM/DD/YY"></td>  
                <td style='width:145px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_policies.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_policies.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <tr id="grid-head-tools">
                <td colspan="100%">
                    <ul>
                        <li><div class="btn-icon report btn-left"                     title="Drivers/Vehicle List"                  onclick="fn_policies.get_list_open();" style="width:auto!important;"><i class="fa fa-list-ul"></i><span style="margin-left:5px;font-size: 10px!important;">Drivers/Vehicle List</span></div></li>
                        <li><div class="btn-icon report btn-left"                     title="Report of Policies"                    onclick="fn_policies.dialog_report_open();" style="width:auto!important;"><i class="fa fa-folder-open"></i><span style="margin-left:5px;font-size: 10px!important;">Report of Policies</span></div></li>  
                        <li><div class="btn-icon add btn-left active active_policies" title="View Actived Policies "                onclick="fn_policies.pagina_actual='';fn_policies.fillgrid();"><i class="fa fa-file-text"></i></div></li> 
                        <li><div class="btn-icon trash btn-left expired_policies"      title="View Canceled & Expired Policies "    onclick="fn_policies.pagina_actual='';fn_policies.fillgrid_expired();"><i class="fa fa-clock-o"></i></div></li>
                        <li><div class="btn-icon edit btn-left"                        title="Upload drivers or units of a company"  onclick="fn_policies.upload_file_form();" style="width: auto;"><i class="fa fa-upload"></i><span style="margin-left:5px;font-size: 10px!important;">Upload Excel List</span></div></li>
                    </ul>
                </td>
            </tr>
            <tr class="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_policies.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('sNombreCompania',this.cellIndex);">Company Name</td> 
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('sNumeroPoliza',this.cellIndex);">Policy Number</td>
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('sDescripcion',this.cellIndex);">Type</td>
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('dFechaInicio',this.cellIndex);">EFFECTIVE DATE </td> 
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('dFechaCaducidad',this.cellIndex);">EXPIRE Date</td> 
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
                        <button id="pgn-inicio"    onclick="fn_policies.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_policies.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_policies.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_policies.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>   
    </div>
</div>
<!-- FORMULARIOS -->
<div id="policies_edit_form" class="popup-form">
    <div class="p-header">
        <h2>EDIT OR ADD COMPANY POLICY</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('policies_edit_form');fn_policies.fillgrid();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div id="policy_information">
        <form>   
            <fieldset id="policy_data_form">
                <legend>Policy Data</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <table style="width: 100%;border-collapse: collapse;">
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <input id="iConsecutivo"  name="iConsecutivo"  type="hidden">
                            <label>Company <span style="color:#ff0000;">*</span>:</label>  
                            <select tabindex="1" id="iConsecutivoCompania"  name="iConsecutivoCompania" style="height: 27px!important;"><option value="">Select an option...</option></select>
                        </div> 
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item">
                            <label>Policy No. <span style="color:#ff0000;">*</span>:</label> 
                            <input class="txt-uppercase" tabindex="2" id="sNumeroPoliza" name="sNumeroPoliza" type="text" placeholder="" maxlength="30" style="width:97%!important;">
                        </div> 
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Type <span style="color:#ff0000;">*</span>:</label> 
                            <select tabindex="3" id="iTipoPoliza"  name="iTipoPoliza" onblur="fn_policies.valida_tipo_poliza(false);" style="height: 27px!important;"><option value="">Select an option...</option></select>
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="field_item"> 
                                <label>Effective Date <span style="color:#ff0000;">*</span>: </label>
                                <input tabindex="4" id="dFechaInicio" type="text" class="txt-uppercase fecha" style="width: 92%;">
                            </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Expiration Date <span style="color:#ff0000;">*</span>: </label>
                            <input tabindex="5" id="dFechaCaducidad" type="text" class="txt-uppercase fecha" style="width: 92%;">
                        </div>
                        </td>
                    </tr>
                    <tr class="cargo_policie_type">
                        <td>
                            <div class="field_item"> 
                                <label>Data Type: </label>
                                <select tabindex="6" id="eTipoCategoria" style="width: 99%!important;height: 27px!important;">
                                    <option value="">Select an option...</option>
                                    <option value="GROSS">Gross</option>
                                    <option value="SCHEDULE">Schedule</option>
                                    <option value="DRIVERS">Drivers</option>
                                </select>
                            </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                            <label>Data Submission Plan: </label>
                            <select tabindex="6" id="eTipoEnvio" style="width: 99%!important;height: 27px!important;">
                                <option value="">Select an option...</option>
                                <option value="CHANGE">Change</option>
                                <option value="MONTHLY">Monthly</option>
                                <option value="TRIMONTHS">Trimonths</option>
                                <option value="QUATERLY">Quaterly</option>
                                <option value="YEAR">Year</option>
                            </select>
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%"> 
                        <table class="premium_amounts" style="width:100%;border-collapse:collapse;">
                            <tr>
                                <td>
                                <div class="field_item"> 
                                    <label>Limit $: </label>
                                    <input id="iPremiumAmount" type="text" class="" style="width:97%!important;"/>
                                </div>
                                </td>
                                <td>
                                <div class="field_item"> 
                                    <label>Deductible $: </label>
                                    <input id="iDeductible" type="text" class="decimal"/>
                                </div>
                                </td>
                            </tr>
                            <tr class="premium_amounts_additional" style="display: none;">
                                <td>
                                <div class="field_item"> 
                                    <label>Trailer Interchange Limit $: </label>
                                    <input id="iPremiumAmountAdditional" type="text" class="" style="width:97%!important;"/>
                                </div>
                                </td>
                                <td>
                                <div class="field_item"> 
                                    <label>Trailer Interchange Deductible $: </label>
                                    <input id="iDeductibleAdditional" type="text" class="decimal"/>
                                </div>
                                </td>
                            </tr>
                        </table>
                        <table class="premium_amounts_GL" style="width:100%;border-collapse:collapse;">
                            <tr>
                                <td>
                                <div class="field_item"> 
                                    <label>Each Occurence: </label>
                                    <input id="iCGL_EachOccurrence" type="text" class="decimal" style="width:97%!important;"/>
                                </div>
                                </td>
                                <td>
                                <div class="field_item"> 
                                    <label>Damaged to Rented Premises (Ea Occurrence): </label>
                                    <input id="iCGL_DamageRented" type="text" class="decimal" style="width:97%!important;"/>
                                </div>
                                </td>
                                <td>
                                <div class="field_item"> 
                                    <label>MED EXP (Any one person): </label>
                                    <input id="iCGL_MedExp" type="text" class="decimal"/>
                                </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                <div class="field_item"> 
                                    <label>Personal & Advance Injury: </label>
                                    <input id="iCGL_PersonalAdvInjury" type="text" class="decimal" style="width:97%!important;"/>
                                </div>
                                </td>
                                <td>
                                <div class="field_item"> 
                                    <label>General Aggregate: </label>
                                    <input id="iCGL_GeneralAggregate" type="text" class="decimal" style="width:97%!important;"/>
                                </div>
                                </td>
                                <td>
                                <div class="field_item"> 
                                    <label>Products - COMP/OP AGG: </label>
                                    <input id="iCGL_ProductsComp" type="text" class="decimal"/>
                                </div>
                                </td>
                            </tr>
                        </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label title="This Broker apply for the endorsements of this policy">Broker:</label>  
                            <select tabindex="3" id="iConsecutivoBrokers"  name="iConsecutivoBrokers" style="height: 27px!important;"><option value="">Select an option...</option></select> 
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label title="This Insurance apply for the claims of this policy">Insurance Company:</label>  
                            <select tabindex="3" id="iConsecutivoAseguranza"  name="iConsecutivoAseguranza" style="height: 27px!important;"><option value="">Select an option...</option></select> 
                        </div>
                        </td>
                    </tr>
                </table>
                <div class="policy_jacker_file" style="display:none;height: 45px;">
                    <!--<legend>POLICY FILES</legend>-->
                    <div class="file_certificate files policyJackerField" style="width: 50%;float: left;"> 
                        <label>Policy Jacker: <span style="color:#9e2e2e;">Please upload the policy jacker in PDF format.</span></label> 
                        <input  id="txtPolicyJacker" type="text" readonly="readonly" value="" size="40" style="width:55%;float: left;" />
                        <button id="btnPolicyJacker" type="button" style="top: 5px!important;float: left;margin-left: 5px;">Upload file</button>
                        <div class="btn-icon trash btn-left active" title="Delete Policy Jacker" onclick="fn_policies.delete_file('policy_jacker');" style="padding: 3px 5px;position: relative;top: 3px;"><i class="fa fa-trash"></i></div>
                        <input  id="iConsecutivoArchivo" type="hidden">
                    </div>
                    <div class="file_certificate files policyPFAField" style="width: 50%;float: left;"> 
                        <label>PFA: <span style="color:#9e2e2e;">Please upload the PFA in PDF format.</span></label>
                        <div class="btn-icon trash btn-left active" title="Delete Policy PFA" onclick="fn_policies.delete_file('pfa');" style="padding: 3px 5px;position: relative;top: 19px;float: right;"><i class="fa fa-trash"></i></div> 
                        <button id="btnPolicyPFA" type="button" style="top: 21px!important;float: right;margin-left: 5px;">Upload file</button>
                        <input  id="txtPolicyPFA" type="text" readonly="readonly" value="" size="40" style="width:55%;float: right;clear: none;" />
                        <input  id="iConsecutivoArchivoPFA" type="hidden">
                    </div>
                </div>
                <br> 
                <button type="button" class="btn-1" onclick="fn_policies.save();" style="width: 130px;">SAVE POLICY</button> 
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('policies_edit_form');fn_policies.fillgrid();" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
            </fieldset>
            <div id="certificate_edit_form">
            <fieldset class="fieldset-certificates">
                <legend>GENERAL DATA FOR CERTIFICATE</legend>
                 <table style="width: 100%;border-collapse: collapse;">
                    <tr><td colspan="100%"><p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p></td></tr>
                    <tr>
                        <td style="width:60%;">
                        <div class="field_item">
                            <label class="required-field" title="the limit date for certificate layout">Set certificate from <span style="color:#ff0000;">*</span>:</label> 
                            <select name="eOrigenCertificado" class="required-field" style="height: 27px!important;" onblur="fn_certificate.valida_tipo_certificado();">
                                <option value="">Select an option...</option>
                                <option value="LAYOUT">PDF - Layout uploaded.</option>
                                <option value="DATABASE">DATA BASE - Data from company data policies in the system.</option>
                            </select>
                            <input name="iConsecutivoCompania"  type="hidden" value="">
                        </div>
                        </td>
                        <td>
                        <div class="field_item">
                           <label title="the limit date for certificate layout">Expire Date <span style="color:#ff0000;">*</span>:</label> 
                           <input name="dFechaVencimiento" type="text" class="fecha required-field" style="width:90%;" value="">
                        </div>
                        </td>
                    </tr>  
                    <tr>
                        <td colspan="100%">
                        <div id="info_policies">
                            <table class="popup-datagrid" style="margin-bottom: 10px;width: 100%;" cellpadding="0" cellspacing="0">
                                <thead>
                                    <tr id="grid-head2">
                                        <td class="etiqueta_grid">Type</td>
                                        <td class="etiqueta_grid">Policy Number</td>
                                        <td class="etiqueta_grid">POLICY EFF </td>
                                        <td class="etiqueta_grid">POLICY EXP</td>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        </td>
                    </tr>
                    <tr class="campos-database">
                        <td colspan="100%">
                        <div class="field_item">
                           <label title="DESCRIPTION OF OPERATIONS / LOCATIONS / VEHICLES (ACORD 101, Additional Remarks Schedule, may be attached if more space is required)">Descriptions of operations:</label> 
                           <textarea name="sDescripcionOperaciones" style="width:99%;"></textarea>
                        </div>
                        </td>
                    </tr>
                 </table>
                <br>
                <button type="button" class="btn-1 campos-database" onclick="fn_certificate.save();" style="width:150px;">SAVE CERTIFICATE</button> 
                <button type="button" class="btn-1 campos-database" onclick="fn_certificate.preview_certificate();" style="margin-right:10px;background:#5ec2d4;width: 180px;">PREVIEW CERTIFICATE</button> 
                <button type="button" class="btn-1 campos-database" onclick="fn_popups.cerrar_ventana('policies_edit_form');fn_policies.fillgrid();" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
            </fieldset>
            <fieldset class="campos-layout">
                <legend>UPLOAD PDF LAYOUT FROM INSURED HUB</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <table style="width: 100%;border-collapse: collapse;">
                    <tr>
                        <td colspan="100%">
                        <div class="file_certificate files"> 
                            <label>Certificate: <span style="color:#9e2e2e;">Please upload a copy of the form in PDF format.</span></label> 
                            <input  id="txtsCertificatePDF" name="txtsCertificatePDF" type="text" readonly="readonly" value="" size="40" style="width:83%;" />
                            <button id="btnsCertificatePDF" type="button">Upload Certificate</button>
                            <input  name="iConsecutivo" type="hidden">
                        </div> 
                        </td>
                    </tr>
                </table>
            </fieldset> 
            </div>
        </form>
    </div>
    </div>
</div>
<!-- upload file form -->
<div id="file_edit_form" class="popup-form">
    <div class="p-header">
        <h2>UPLOAD DRIVER/VEHICLE FILE FROM AMIC LIST</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('file_edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <form>
            <fieldset>
                <table style="width: 100%;">
                    <tr>
                        <td><a class="btn-text btn-left" title="Download BIND Layout XLS" href="documentos/plantilla_ejemplo_upload_amic.xlsx" target="_blank"><i class="fa fa-file-excel-o"></i><span>Download BIND Layout XLS</span></a></td>
                        <!--<td><a class="btn-text btn-left" title="Download ENDORSEMENT Layout XLS" href="documentos/plantilla_ejemplo_upload_endosos.xlsx" target="_blank"><i class="fa fa-file-excel-o"></i><span>Download ENDORSEMENT Layout XLS</span></a></td>-->
                        <td><a class="btn-text btn-left" title="Download PDF Manual" href="documentos/manual_para_subir_y_crear_plantillas.pdf" target="_blank"><i class="fa fa-file-pdf-o"></i><span>Download PDF Manual</span></a></td>
                    </tr>
                </table>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <div class="field_item">
                    <label>Company <span style="color:#ff0000;">*</span>:</label>  
                    <select tabindex="2" id="iConsecutivoCompania" class="required-field"  name="File_iConsecutivoCompania" onchange="fn_policies.get_company_policies(this.value);" style="height:25px!important;">
                        <option value="">Select an option...</option>
                    </select>
                </div>                
                <div class="field_item">
                    <label>Policies <span style="color:#ff0000;">*</span>:</label>  
                    <div class="company_policies" style="padding:5px 10px;"></div>
                </div>
                <div class="field_item"> 
                    <label>File: <span style="color:#ff0000;">(Please upload an Excel File with the layout example).</span></label> 
                    <input id="txtFile" type="text" readonly="readonly" value="" size="40" style="width:98%;" />
                </div>
                <table id="reporte_policy_update"></table>
                <br> 
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('file_edit_form');" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
                <button id="btnFile" type="button" class="btn-1" style="width:230px;">Upload & Save file</button>
            </fieldset>
        </form>
    </div>
    </div>
</div>
<!-- DRIVERS & UNITS -->
<div id="dialog_driver_unit" title="Driver/Vehicle LIST" style="display:none;" >
    <p>Please select a company:</p>
    <form id="frm_report_policies" method="post">
        <fieldset>
        <div class="field_item"> 
            <select name="iConsecutivoCompania" class="flt_company"><option value="">Select an option...</option></select>
        </div>
        </fieldset>
    </form>  
</div>
<div id="driver_list_form" class="popup-form" style="width:90%!important;">
    <div class="p-header">
        <h2>LIST OF DRIVERS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('driver_list_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div id="driver_tabs">
      <ul style="border-radius: 4px 4px 0px 0px;">
        <li><a href="#tabs-1" onclick="fn_policies.list.fill_drivers_actives();">Drivers</a></li>
        <li><a href="#tabs-2" onclick="fn_policies.list.fill_units_actives();">Vehicles</a></li>
      </ul>
      <div id="tabs-1" style="padding:0!important;">
        <table id="drivers_active_table" class="popup-datagrid" style="width:100%!important;">
            <thead>
                    <tr id="grid-head1">
                        <td style="width:380px;"><input class="flt_dName" type="text" placeholder="Name:"></td> 
                        <td><input class="flt_dDob flt_fecha"  type="text" placeholder="MM-DD-YY"></td>
                        <td><input class="flt_dLicense" type="text" placeholder="License #:"></td> 
                        <td style="width: 120px;">
                            <select class="flt_dtype" type="text" onblur="fn_policies.list.drivers_filtraInformacion();">
                                <option value="">Select an option...</option>
                                <option value="1">Federal / B1</option> 
                                <option value="2">Commercial / CDL - A</option> 
                            </select>
                        </td> 
                        <td><input class="flt_dExpire flt_fecha" type="text" placeholder="MM-DD-YY"></td> 
                        <td style="width:80px;"><input class="flt_dYears num" type="text" placeholder="Years:"></td> 
                        <td style='width:250px;' class="txt-c"></td>  
                        <td style='width:120px;'>
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_policies.list.filtraInformacion();"><i class="fa fa-search"></i></div>
                            <!--<div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_policies.list.drivers_add();"><i class="fa fa-plus"></i></div>-->
                        </td> 
                    </tr>
                    <tr id="grid-head2">
                        <td class="etiqueta_grid">Name</td>
                        <td class="etiqueta_grid">DOB</td>
                        <td class="etiqueta_grid">LICENSE NUMBER</td>
                        <td class="etiqueta_grid">LICENSE TYPE</td> 
                        <td class="etiqueta_grid">EXPIRE DATE</td> 
                        <td class="etiqueta_grid">EXPERIENCE YEARS</td>
                        <td class="etiqueta_grid" style="padding: 3px 0px 0px!important;width:450px;">
                            <span style="display: block;padding: 0px 3px;text-align: center;">POLICIES</span>
                            <table style="width: 100%;text-transform: uppercase;">
                            <thead><tr>
                                <td style="width:40%;">No.</td>
                                <td style="width:10%">Type</td>
                                <td style="width:50%">Application Date</td>
                            </tr></thead></table>
                        </td>
                        <td class="etiqueta_grid"><div class="btn-icon report btn-left" title="Download excel list" onclick="fn_policies.list.download_report(fn_policies.list.id_company,'2',fn_policies.list.filtro);" style="width:auto!important;"><i class="fa fa-download"></i><span style="margin-left:5px;font-size: 10px!important;">Download Excel</span></div></td>
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
                                <button class="pgn-inicio"    onclick="fn_policies.list.drivers_firstPage();" title="First page"><span></span></button>
                                <button class="pgn-anterior"  onclick="fn_policies.list.drivers_previousPage();" title="Previous"><span></span></button>
                                <button class="pgn-siguiente" onclick="fn_policies.list.drivers_nextPage();" title="Next"><span></span></button>
                                <button class="pgn-final"     onclick="fn_policies.list.drivers_lastPage();" title="Last Page"><span></span></button>
                            </div>
                        </td>
                    </tr>
                </tfoot>
        </table>
        <!-- FORMULARIOS DE EDICION DRIVERS-->
        <div id="drivers_edit_form" style="display:none;">
           <form style="padding:10px;" action="" method="post">
           <fieldset>
               <legend>DRIVER DATA</legend>
               <p>In this form you can only update the driver general data, not its policies.</p>
               <div class="field_item" style="font-size: 12px;">
                    <label style="font-size: 12px;">Actual Policies: <span style="color:#ff0000;">*</span>:</label>  
                    <div class="company_policies" style="padding:10px;"></div>
                    <br>
                </div> 
               <table id="data_driver_form" cellpadding="0" cellspacing="0" style="width: 100%;">
                <tr>
                    <td colspan="100%">
                        <input type="hidden" id="iConsecutivo" value="">
                        <input type="hidden" id="iConsecutivoCompania" value="">
                        <input type="hidden" id="siConsecutivosPolizas" value="">
                    </td>
                </tr>
                <tr>
                    <td>
                    <div class="field_item"> 
                        <label>Name <span style="color:#ff0000;">*</span>: </label>
                        <input tabindex="1" id="sNombre" type="text" placeholder="Please write a name..." class="txt-uppercase required-field" style="width: 97%;">
                    </div>
                    </td>
                    <td>
                    <div class="field_item"> 
                        <label>Date of Birth: </label>
                        <input tabindex="2" id="dFechaNacimiento" type="text" class="txt-uppercase fecha" style="width: 90%;">
                    </div>
                    </td>
                    <td>
                    <div class="field_item"> 
                        <label>Experience Years: </label>
                        <input tabindex="3" id="iExperienciaYear" class="num txt-uppercase" type="text" placeholder="Please write only the number." style="width: 97%;">
                    </div>
                    </td>
                </tr>
                <tr>
                    <td>
                    <div class="field_item"> 
                        <label>License Number: </label>
                        <input tabindex="4" id="iNumLicencia" class="txt-uppercase" maxlength="10" type="text" placeholder="Please write the license number..." style="width: 97%;">
                    </div>
                    </td>
                    <td>
                    <div class="field_item_operador"> 
                          <label>Licence Type: </label>
                          <Select tabindex="5" id="eTipoLicencia" style="width: 99%!important;height: 25px!important;">
                            <option value="">Select an option...</option>
                            <option value="FEDERAL/B1">FEDERAL / B1</option>
                            <option value="COMMERCIAL/CDL-A">COMMERCIAL / CDL-1</option>
                          </select>
                    </div>
                    </td>
                    <td>
                    <div class="field_item"> 
                        <label>Expiration Date: </label>
                        <input tabindex="6" id="dFechaExpiracionLicencia" type="text" class="txt-uppercase fecha" style="width: 90%;">
                    </div>
                    </td>
                </tr>
               </table>
               <button type="button" class="btn-1" onclick="fn_policies.list.drivers_save();" style="font: 400 13.3333px Arial!important;">SAVE</button>
               <button type="button" class="btn-1" onclick="$('#drivers_active_table').show();$('#drivers_edit_form').hide();" style="margin-right:10px;background:#e8051b;font: 400 13.3333px Arial!important;">CLOSE</button>
            </fieldset>
            </form> 
        </div>
      </div>
      <div id="tabs-2" style="padding:0!important;">
        <table id="units_active_table" class="popup-datagrid" style="width:100%!important;">
            <thead>
                <tr id="grid-head1">
                        <td style="width:380px;"><input class="flt_uVIN" type="text" placeholder="Name:"></td> 
                        <td>
                            <select class="flt_uRadio" type="text" onblur="fn_policies.list.units_filtraInformacion();">
                                <option value="">Select an option...</option>
                                <option value="1">0 - 250 (TEXAS)</option> 
                                <option value="2">0 - 500 (TEXAS)</option>
                                <option value="3">0 - 500 (TEXAS)</option> 
                            </select>
                        </td>
                        <td><input class="flt_uYear num" type="text" placeholder="Year:"></td> 
                        <td><input class="flt_uMake" type="text" placeholder="Make:"></td>  
                        <td><input class="flt_uType" type="text" placeholder="Type:"></td> 
                        <td style="width:80px;"><input class="flt_uWeight" type="text" placeholder="Weigth:"></td>
                        <td style='width:100px;'></td> 
                        <td style='width:250px;' class="txt-c"></td>   
                        <td style='width:120px;'>
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_policies.list.units_filtraInformacion();"><i class="fa fa-search"></i></div>
                            <!--<div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_policies.list.unit_add();"><i class="fa fa-plus"></i></div>--> 
                        </td> 
                    </tr>
                    <tr id="grid-head2">
                        <td class="etiqueta_grid">VIN</td>
                        <td class="etiqueta_grid">RADIUS</td>
                        <td class="etiqueta_grid">YEAR</td>
                        <td class="etiqueta_grid">MAKE</td> 
                        <td class="etiqueta_grid">TYPE</td> 
                        <td class="etiqueta_grid">CAPACITY</td> 
                        <td class="etiqueta_grid">$ VALUE</td> 
                        <td class="etiqueta_grid" style="padding: 3px 0px 0px!important;width:450px;">
                            <span style="display: block;padding: 0px 3px;text-align: center;">POLICIES</span>
                            <table style="width: 100%;text-transform: uppercase;"><thead><tr>
                                <td style="width:40%">No.</td>
                                <td style="width:10%">Type</td>
                                <td style="width:50%">Application Date</td>
                            </tr></thead></table>
                        </td>
                        <td class="etiqueta_grid"><div class="btn-icon report btn-left" title="Download excel list" onclick="fn_policies.list.download_report(fn_policies.list.id_company,'1',fn_policies.list.filtro);" style="width:auto!important;"><i class="fa fa-download"></i><span style="margin-left:5px;font-size: 10px!important;">Download Excel</span></div></td>
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
                                <button class="pgn-inicio"    onclick="fn_policies.list.units_firstPage();" title="First page"><span></span></button>
                                <button class="pgn-anterior"  onclick="fn_policies.list.units_previousPage();" title="Previous"><span></span></button>
                                <button class="pgn-siguiente" onclick="fn_policies.list.units_nextPage();" title="Next"><span></span></button>
                                <button class="pgn-final"     onclick="fn_policies.list.units_lastPage();" title="Last Page"><span></span></button>
                            </div>
                        </td>
                    </tr>
                </tfoot>
        </table>
        <!-- FORMULARIOS DE EDICION DRIVERS -->
        <div id="unit_edit_form" style="display:none;">
           <form style="padding:10px;">
           <fieldset>
                <legend>VEHICLE DATA</legend>
                <p>In this form you can only update the vehicle general data, not its policies.</p>
                <div class="field_item">
                    <label>Actual Policies: <span style="color:#ff0000;">*</span>:</label>  
                    <div class="company_policies" style="padding:10px;"></div>
                    <br>
                </div> 
                <table id="data_unit_form" cellpadding="0" cellspacing="0" style="width:100%;">
                    <tr>
                        <td colspan="100%">
                            <input type="hidden" id="iConsecutivo" value="">
                            <input type="hidden" id="iConsecutivoCompania" value="">
                            <input type="hidden" id="siConsecutivosPolizas" value="">
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item required_field"> 
                            <label>VIN Number <span style="color:#ff0000;">*</span>: </label>
                            <input tabindex="1" id="sVIN" type="text" class="txt-uppercase" value="" style="width: 97%;">
                        </div>
                        </td>
                        <td>
                        <div class="field_item required_field"> 
                            <label>Type <span style="color:#ff0000;">*</span>: </label>
                            <Select tabindex="2" id="sTipo" style="width: 99%!important;height: 25px!important;">
                                <option value="">Select an option...</option>
                                <option value="UNIT">Unit</option>
                                <option value="TRAILER">Trailer</option>
                                <option value="TRACTOR">Tractor</option>
                            </select>    
                        </div>
                        </td>
                        <td>
                        <div class="field_item required_field"> 
                            <label>Year <span style="color:#ff0000;">*</span>: </label>
                            <Select tabindex="3" id="iYear" style="width: 99%!important;height: 25px!important;"><option value="">Select an option...</option></select> 
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <div class="field_item"> 
                              <label>Make: </label>
                              <Select tabindex="4" id="iModelo" style="width: 99%!important;height: 25px!important;"><option value="">Select an option...</option></select>
                        </div>
                        </td>
                        <td>
                        <div class="field_item"> 
                              <label>Radius: </label>
                              <Select tabindex="5" id="iConsecutivoRadio"  style="width: 99%!important;height: 25px!important;"><option value="">Select an option...</option></select>
                        </div>
                        </td>
                        <td>
                        <div class="field_item required_field"> 
                            <label>PD Amount ($): </label>
                            <input id="iTotalPremiumPD" type="text" class="num" maxlength="25">
                        </div>
                        </td>
                    </tr>
                </table> 
                <button type="button" class="btn-1" onclick="fn_policies.list.unit_save();" style="font: 400 13.3333px Arial!important;">SAVE</button>
                <button type="button" class="btn-1" onclick="$('#units_active_table').show();$('#unit_edit_form').hide();" style="margin-right:10px;background:#e8051b;font: 400 13.3333px Arial!important;">CLOSE</button>
            </fieldset>
            </form> 
        </div>
      </div>
    </div>
    <div>
    </div>
    </div>
</div>
<!-- DIALOGUES -->
<div id="dialog_report_policies" title="REPORT OF POLICIES" style="display:none;" >
    <p>Please select the parameters to generate a report of the policies:</p>
    <form id="frm_report_policies" method="post">
        <fieldset>
        <div class="field_item"> 
            <label>Company: </label>
            <select class="flt_company"><option>All</option></select>
        </div>
        <div class="field_item"> 
            <label>Broker: </label>
            <select class="flt_broker"><option>All</option></select>
        </div> 
        <div class="field_item"> 
            <label>Insurance: </label>
            <select class="flt_insurance"><option>All</option></select>
        </div> 
        <div class="field_item"> 
            <label>Type: </label>
            <select class="flt_type"><option>All</option></select>
        </div> 
        <div class="field_item"> 
            <label>Expiration Date: </label>
            <div>
                <label class="check-label" style="position: relative;top: 0px;">From</label><input id="flt_dateFrom" type="text"  placeholder="MM/DD/YY" style="width: 140px;">
                <label class="check-label" style="position: relative;top: 0px;">To</label><input   id="flt_dateTo"   type="text"  placeholder="MM/DD/YY" style="width: 140px;">
            </div>
        </div> 
        </fieldset>
    </form>  
</div>
<div id="dialog_delete_policy" title="SYSTEM ALERT" style="display:none;">
    <p>Are you sure you want to delete the policy: <span></span>?</p>
    <form id="elimina" method="post">
        <fieldset>
            <input type="hidden" name="id_policy" id="id_policy">
            <label>Mark the policy like:</label>
            <select name="eDeletedStatus">
                <option value="DELETED">Deleted</option>
                <option value="CANCELED">Canceled</option>
                <option value="RENEWED">deleted for Renew</option>
            </select>
            <label>Additional Comments:</label>
            <textarea name="sComentariosCancelacion" style="resize:none;width: 98%;"></textarea>
        </fieldset>
    </form>  
</div>
<div id="dialog_report_history_list" title="REPORT OF HISTORY LISTS" style="display:none;" >
    <p>Please select the parameters to generate the history report:</p>
    <form id="frm_report_history_list" method="post">
        <fieldset>
        <div class="field_item"> 
            <label>Company: </label>
            <select class="flt_company" onblur="fn_policies.list.get_policies(this.value);"><option value="">Select an option...</option></select>
        </div>
        <div class="field_item"> 
            <label>Type: </label>
            <select class="flt_type">
                <option value="2">Drivers</option>
                <option value="1">Vehicles</option>
            </select>
        </div> 
        <div class="field_item"> 
            <label>Policy: </label>
            <select class="flt_policies"><option value="">All</option></select>
        </div> 
        </fieldset>
    </form>  
</div>
<!-- FOOTER -->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>