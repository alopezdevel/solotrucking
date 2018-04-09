<?php session_start();    
if (!($_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )){
    //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<!-- HEADER -->
<?php include("header.php"); ?>  
<script type="text/javascript"> 
$(document).ready(inicio);
function inicio(){  
        $.blockUI();
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);
        $.unblockUI();
        
        //Si el usuario es diferente de company...
        if(tipo_usuario != '2'){

            //Declaramos dialogo:
            $('#dialog_select_company').dialog({
                modal: true,
                autoOpen: false,
                width : 550,
                height : 200,
                resizable : false,
                dialogClass: "without-close-button",
                buttons : {
                    'CONTINUE' : function() {
                        var clave = $('#dialog_select_company #iConsecutivoCompania').val();
                        var name  = $('#dialog_select_company #iConsecutivoCompania option:selected').text(); 
                        //Crear variable de sesion Temp:
                        $.ajax({             
                            type:"POST", 
                            url:"funciones_endorsements.php", 
                            data:{accion:"definir_compania",'iConsecutivoCompania':clave},
                            async : true,
                            dataType : "json",
                            success : function(data){
                                switch(data.error){
                                    case '0':
                                        $("#ct_endorsement_co h2").empty().append('APPLICATIONS TO THE COMPANY:' + name); 
                                        fn_endorsement_co.init();
                                        fn_endorsement_co.fillgrid();
                                        $('#dialog_select_company').dialog('close');
                                    break;
                                    case '1':  fn_solotrucking.mensaje('Please select a valid company.');break;  
                                }
                                
                                
                            }
                        });
                    },
                     'CANCEL' : function(){
                        $(this).dialog('close');
                        location.href= "inicio.php";
                    }
                }
            });
            
            //Cargamos catalogo:
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_companies"},
                async : true,
                dataType : "json",
                success : function(data){$("#dialog_select_company #iConsecutivoCompania").empty().append(data.select);}
            });
            
            $('#dialog_select_company').dialog('open');      
        }else{
           fn_endorsement_co.init();
           fn_endorsement_co.fillgrid(); 
            
        }
 
        $('#dialog_delete_endorsement_co').dialog({
            modal: true,
            autoOpen: false,
            width : 300,
            height : 200,
            resizable : false,
            buttons : {
                'YES' : function() {
                    clave = $('#id_endorsement_co').val();
                    $(this).dialog('close');
                    
                    fn_endorsement_co.delete_endorsement_co(clave);             
                },
                 'NO' : function(){
                    $(this).dialog('close');
                }
            }
        }); 
        $('#dialog_quote_unit').dialog({
            modal: true,
            autoOpen: false,
            width : 700,
            height : 180,
            resizable : false,
            buttons : {
                'I NEED A QUOTE FIRST' : function() {
                    clave = $('#id_endorsement').val();
                    $(this).dialog('close');
                    fn_endorsement_co.send_quote(clave);             
                },
                 'SEND ENDORSEMENT TO PROCESS' : function(){
                    clave = $('#id_endorsement').val(); 
                    $(this).dialog('close');
                    fn_endorsement_co.endorsement_email_send(clave);
                },
                 'CANCEL' : function(){
                    $(this).dialog('close');
                }
            }
        }); 
        $('#dialog_types').dialog({
            modal: true,
            autoOpen: false,
            width : 350,
            height : 230,
            resizable : false,
            buttons : {
                'CONTINUE' : function() { 
                    type = $('#dialog_types #TipoEndoso').val();
                    
                    if(type != ""){
                        fn_endorsement_co.new_endorsement(type);
                        $(this).dialog('close');
                    }else{
                        fn_solotrucking.mensaje('Please select a valid type.');
                    }
                },
                'CANCEL' : function(){$(this).dialog('close');}
            }
        }); 
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}  }                   
var fn_endorsement_co = {
        domroot:"#ct_endorsement_co",
        data_grid: "#data_grid_endorsement_co",
        filtro : "",
        pagina_actual : "",
        sort : "DESC",
        orden : "dFechaAplicacion",
        init : function(){
            $('.num').keydown(fn_solotrucking.inputnumero); 
            $('.decimals').keydown(fn_solotrucking.inputdecimals);
            //Filtrado con la tecla enter
            $(fn_endorsement_co.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_endorsement_co.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_endorsement_co.filtraInformacion();
                }
            });    
            //Verificar Polizas PD:
            $.ajax({             
                type:"POST", 
                url:"funciones_endorsements.php", 
                data:{accion:"validate_policies"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    if(data.error == '0'){
                        if(data.valida_pd == '1'){
                           $("#endorsements_co_edit_form #pd_information").empty().append(data.pd_information); 
                           $('#endorsements_co_edit_form #iPDApply').val('0');
                        }
                        $("#info_policies table tbody").empty().append(data.policies_information);
                        $("#policies_endorsement").empty().append(data.checkpolicies);
                    }
                }
            }); 
            //#BOTON PARA CARGAR ARCHIVO PDF:
            new AjaxUpload('#btnsLicenciaPDF', {
                action: 'funciones_endorsements.php',
                onSubmit : function(file , ext){
                    if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext))  )){
                        var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: Invalid file format, please upload a JPG or PDF File.</p>';
                        fn_solotrucking.mensaje(mensaje);
                        return false;
                    }else{
                        this.setData({'accion': 'guarda_pdf_driver','eArchivo' : 'LICENSE','iConsecutivo':$('#iConsecutivoLicenciaPDF').val()});
                        $('#txtsLicenciaPDF').val('loading...');
                        this.disable(); 
                    }
                },
                onComplete : function(file,response){  
                    var respuesta = JSON.parse(response);
                    switch(respuesta.error){
                        case '0':
                            $('#txtsLicenciaPDF').val(respuesta.name_file);
                            this.enable();
                            $('#iConsecutivoLicenciaPDF').val(respuesta.id_file);
                            fn_solotrucking.mensaje(respuesta.mensaje);
                        break;
                        case '1':
                           fn_solotrucking.mensaje(respuesta.mensaje);
                           $('#txtsLicenciaPDF').val(''); 
                           this.enable();
                        break;
                    }   
                }        
            });
            //#BOTON PARA CARGAR ARCHIVO MVR PDF:
            new AjaxUpload('#btnsMVRPDF', {
                action: 'funciones_endorsements.php',
                onSubmit : function(file , ext){
                    if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext))  )){ 
                        var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The file format is not valid.</p>';
                        fn_solotrucking.mensaje(mensaje);
                        return false;
                    }else{
                        this.setData({'accion': 'guarda_pdf_driver','eArchivo' : 'MVR','iConsecutivo':$('#iConsecutivoMVRPDF').val()});
                        $('#txtsMVRPDF').val('loading...');
                        this.disable(); 
                    }
                },
                onComplete : function(file,response){  
                    var respuesta = JSON.parse(response);
                    switch(respuesta.error){
                        case '0':
                            $('#txtsMVRPDF').val(respuesta.name_file);
                            this.enable();
                            $('#iConsecutivoMVRPDF').val(respuesta.id_file);
                            fn_solotrucking.mensaje(respuesta.mensaje);
                        break;
                        case '1':
                           fn_solotrucking.mensaje(respuesta.mensaje);
                           $('#txtsMVRPDF').val(''); 
                           this.enable();
                        break;
                    }   
                }        
            });
            //#BOTON PARA CARGAR ARCHIVO LTM PDF:
            new AjaxUpload('#btnsLTMPDF', {
                action: 'funciones_endorsements.php',
                onSubmit : function(file , ext){
                    if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext))  )){ 
                        var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The file format is not valid.</p>';
                        fn_solotrucking.mensaje(mensaje);
                        return false;
                    }else{
                        this.setData({'accion': 'guarda_pdf_driver','eArchivo' : 'LONGTERM','iConsecutivo':$('#iConsecutivoLTMPDF').val()});
                        $('#txtsLTMPDF').val('loading...');
                        this.disable(); 
                    }
                },
                onComplete : function(file,response){  
                    var respuesta = JSON.parse(response);
                    switch(respuesta.error){
                        case '0':
                            $('#txtsLTMPDF').val(respuesta.name_file);
                            this.enable();
                            $('#iConsecutivoLTMPDF').val(respuesta.id_file);
                            fn_solotrucking.mensaje(respuesta.mensaje);
                        break;
                        case '1':
                           fn_solotrucking.mensaje(respuesta.mensaje);
                           $('#txtsLTMPDF').val(''); 
                           this.enable();
                        break;
                    }   
                }        
            });
            //#BOTON PARA CARGAR ARCHIVO TITLE:
            new AjaxUpload('#btnsTituloPDF', {
                action: 'funciones_endorsements.php',
                onSubmit : function(file , ext){
                    if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext))  )){ 
                        var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The file format is not valid.</p>';
                        fn_solotrucking.mensaje(mensaje);
                        return false;
                    }else{
                        this.setData({'accion': 'guarda_pdf_unit','eArchivo' : 'TITLE','iConsecutivo':$('#iConsecutivoTituloPDF').val()});
                        $('#txtsTituloPDF').val('loading...');
                        this.disable(); 
                    }
                },
                onComplete : function(file,response){  
                    var respuesta = JSON.parse(response);
                    switch(respuesta.error){
                        case '0':
                            $('#txtsTituloPDF').val(respuesta.name_file);
                            this.enable();
                            $('#iConsecutivoTituloPDF').val(respuesta.id_file);
                            fn_solotrucking.mensaje(respuesta.mensaje);
                        break;
                        case '1':
                           fn_solotrucking.mensaje(respuesta.mensaje);
                           $('#txtsTituloPDF').val(''); 
                           this.enable();
                        break;
                    }   
                }        
            });
            //#UNITS DELETE FILES:
            new AjaxUpload('#btnsDAPDF', {
                action: 'funciones_endorsements.php',
                onSubmit : function(file , ext){
                    if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext))  )){ 
                        var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The file format is not valid.</p>';
                        fn_solotrucking.mensaje(mensaje);
                        return false;
                    }else{
                        this.setData({'accion': 'guarda_pdf_unit','eArchivo' : $('#DeleteCause').val(),'iConsecutivo':$('#iConsecutivoDAPDF').val()});
                        $('#txtsDAPDF').val('loading...');
                        this.disable(); 
                    }
                },
                onComplete : function(file,response){  
                    var respuesta = JSON.parse(response);
                    switch(respuesta.error){
                        case '0':
                            $('#txtsDAPDF').val(respuesta.name_file);
                            this.enable();
                            $('#iConsecutivoDAPDF').val(respuesta.id_file);
                            fn_solotrucking.mensaje(respuesta.mensaje);
                        break;
                        case '1':
                           fn_solotrucking.mensaje(respuesta.mensaje);
                           $('#txtsDAPDF').val(''); 
                           this.enable();
                        break;
                    }   
                }        
            });
            //#BOTON PARA CARGAR ARCHIVO PSP:
            new AjaxUpload('#btnsPSPFile', {
                action: 'funciones_endorsements.php',
                onSubmit : function(file , ext){
                    if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext))  )){ 
                        var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The file format is not valid.</p>';
                        fn_solotrucking.mensaje(mensaje);
                        return false;
                    }else{
                        this.setData({'accion': 'guarda_pdf_driver','eArchivo' : 'PSP','iConsecutivo':$('#iConsecutivoPSPFile').val()});
                        $('#txtsPSPFile').val('loading...');
                        this.disable(); 
                    }
                },
                onComplete : function(file,response){  
                    var respuesta = JSON.parse(response);
                    switch(respuesta.error){
                        case '0':
                            $('#txtsPSPFile').val(respuesta.name_file);
                            this.enable();
                            $('#iConsecutivoPSPFile').val(respuesta.id_file);
                            fn_solotrucking.mensaje(respuesta.mensaje);
                        break;
                        case '1':
                           fn_solotrucking.mensaje(respuesta.mensaje);
                           $('#txtsPSPFile').val(''); 
                           this.enable();
                        break;
                    }   
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
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_endorsements.php", 
                data:{
                    accion:"get_endorsements",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_endorsement_co.pagina_actual, 
                    filtroInformacion : fn_endorsement_co.filtro,  
                    ordenInformacion : fn_endorsement_co.orden,
                    sortInformacion : fn_endorsement_co.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_endorsement_co.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_endorsement_co.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_endorsement_co.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_endorsement_co.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_endorsement_co.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_endorsement_co.pagina_actual = data.pagina;
                    fn_endorsement_co.edit();
                    fn_endorsement_co.view();
                    fn_endorsement_co.delete_confirm();
                    fn_endorsement_co.quote_confirmation();
                    fn_endorsement_co.endorsement_resend_email();
                }
            }); 
        },
        view : function (){
            $(fn_endorsement_co.data_grid + " tbody td .view").bind("click",function(){
                fn_endorsement_co.add();
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                $.post("funciones_endorsements.php",{accion:"get_endorsement", clave: clave, domroot : "endorsements_co_edit_form"},
                function(data){
                    if(data.error == '0'){
                       $('#endorsements_co_edit_form input, #endorsements_co_edit_form select').val('').removeClass('error');
                       
                       //cargamos polizas:
                       fn_endorsement_co.cargar_policies_check();
                       
                       $('#policies_endorsement :checkbox').prop("checked",""); 
                       eval(data.fields);
                       $('#frm_general_information .policies_endorsement').empty().append(data.policies_table);
 
                       if($('#endorsements_co_edit_form #iConsecutivoTipoEndoso_endoso').val() == '2'){
                            $('#action_information, #frm_driver_information').show();
                            fn_endorsement_co.validate_country();
                            fn_endorsement_co.validade_action();
                            if($('#endorsements_co_edit_form #iConsecutivoLTMPDF').val() != ''){$('#endorsements_co_edit_form .file_longterm').show();}
                            else{$('#endorsements_co_edit_form .file_longterm').hide();}    
                       }else if($('#endorsements_co_edit_form #iConsecutivoTipoEndoso_endoso').val() == '1'){
                            $('#action_information , #frm_unit_information').show();
                            if($('#eAccion_endoso').val() == 'D'){
                                $('#endorsements_co_edit_form .file_uda, #endorsements_co_edit_form .delete_field').show();
                                $('#endorsements_co_edit_form .file_ut, #endorsements_co_edit_form .add_field').hide();
                            }else{  
                                $('#endorsements_co_edit_form .file_ut, #endorsements_co_edit_form .add_field').show();
                                $('#endorsements_co_edit_form .file_uda, #endorsements_co_edit_form .delete_field').hide();
                                //mostrar pd:
                                if($('#pd_information #iPDApply_endoso')){
                                    $('#endorsements_co_edit_form #pd_information').show();
                                    fn_endorsement_co.validate_pdapply(); 
                                }
                            }  
                            
                       }
                       $('#endorsements_co_edit_form .mensaje_valido + .field_item, #endorsements_co_edit_form .mensaje_valido').hide(); 
                       $('#endorsements_co_edit_form input').addClass('readonly').attr('readonly','readonly');
                       $('#endorsements_co_edit_form select').addClass('readonly').attr('disabled','disabled'); 
                       $('#endorsements_co_edit_form .btn-1').hide();
                        
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json"); 
          });  
        },
        delete_confirm : function(){
          $(fn_endorsement_co.data_grid + " tbody .btn_delete").bind("click",function(){
               var clave = $(this).parent().parent().find("td:eq(0)").html();
               $('#dialog_delete_endorsement_co #id_endorsement_co').val(clave);
               $('#dialog_delete_endorsement_co').dialog( 'open' );
               return false;
           });  
        },
        delete_endorsement_co : function(id){
          $.post("funciones_endorsements.php",{accion:"delete_endorsement_co", 'clave': id},
           function(data){
                fn_solotrucking.mensaje(data.msj);
                fn_endorsement_co.fillgrid();
           },"json");  
        },
        validade_action : function(){
            if($('#eAccion_endoso').val() == 'D'){
               fn_endorsement_co.validate_endorsementtype();   
               $('#iPDAmount_endoso').addClass('readonly').attr('readonly','readonly').val(''); 
               $('#iPDApply_endoso').addClass('readonly').attr('disabled','disabled').val('0');
               
               //Verificar que tipo de endoso es:
               if($('#endorsements_co_edit_form #iConsecutivoTipoEndoso_endoso').val() == '2'){ //<--- si es para operadores:
                  $('#frm_driver_information .files').hide();
               }else if($('#endorsements_co_edit_form #iConsecutivoTipoEndoso_endoso').val() == '1'){ //<--- si tipo unidad
                   $('#frm_unit_information .delete_field').show();
                   $('#frm_unit_information .file_ut, #frm_unit_information .add_field').hide();
               }
               
                //ocultar pd:
                if($('#pd_information #iPDApply_endoso')){
                      $('#endorsements_co_edit_form #pd_information').hide();
                      $('#pd_information input').val(''); 
                      $('#pd_information #iPDApply_endoso').val(0); 
                }
                
            }else if($('#eAccion_endoso').val() == 'A'){
               fn_endorsement_co.validate_endorsementtype();  
               $('#iPDApply_endoso').removeClass('readonly').removeAttr('disabled').val('0');
               //Verificar que tipo de endoso es:
               if($('#endorsements_co_edit_form #iConsecutivoTipoEndoso_endoso').val() == '2'){ //<--- si es para operadores:
                     $('.file_dl.files').show();  
                     fn_endorsement_co.validate_country();
                     fn_endorsement_co.validate_age();
                     //ocultar pd:
                     if($('#pd_information #iPDApply_endoso')){
                          $('#endorsements_co_edit_form #pd_information').hide();
                          $('#pd_information input').val(''); 
                          $('#pd_information #iPDApply_endoso').val(0); 
                     } 
               }else if($('#endorsements_co_edit_form #iConsecutivoTipoEndoso_endoso').val() == '1'){ //<--- si tipo unidad
                   $('#frm_unit_information .file_uda, #frm_unit_information .delete_field').hide(); 
                   $('#frm_unit_information .file_ut, #frm_unit_information .add_field').show();
                   //mostrar pd:
                   if($('#pd_information #iPDApply_endoso')){
                      $('#endorsements_co_edit_form #pd_information').show();
                      $('#pd_information input').val(''); 
                      $('#pd_information #iPDApply_endoso').val(0);
                      //$('#pd_information #iPDApply_endoso').val(1).attr('readonly','readonly').addClass('readonly');
                      //fn_endorsement_co.validate_pdapply();  
                   }
               }
            }else{
                if($('#endorsements_co_edit_form #iConsecutivoTipoEndoso_endoso').val() == '1'){$('#frm_unit_information .files').hide(); } 
            }
        },
        quote_confirmation : function(){
          $(fn_endorsement_co.data_grid + " tbody .btn_send_email_brokers").bind("click",function(){   
               var clave = $(this).parent().parent().find("td:eq(0)").html();
               var action  =  $(this).parent().parent().find("td:eq(2)").html();
               var type  =  $(this).parent().parent().find("td:eq(2)").attr('class'); 
               
               if(type == 'UNIT' && action == 'ADD'){
                   $('#dialog_quote_unit #id_endorsement').val(clave);
                   $('#dialog_quote_unit').dialog( 'open' );
               }else{
                   fn_endorsement_co.endorsement_email_send(clave);
               }
               
          });  
        },
        send_quote : function(clave){
            msg = "<p style=\"text-align:center;\">Please wait, we are sending to us your endorsement data...<br><img src=\"images/ajax-loader.gif\" alt=\"ajax-loader.gif\" style=\"margin-top:10px;\"><br></p>";
            $('#Wait').empty().append(msg).dialog('open');
               $.post("funciones_endorsements.php",{accion:"send_quote_email", clave: clave},
                function(data){
                    $('#Wait').empty().dialog('close');
                    fn_solotrucking.mensaje(data.msj);
                    if(data.error == '0'){fn_endorsement_co.fillgrid();}     
            },"json");    
        },
        endorsement_email_send : function(clave){
            
           msg = "<p style=\"text-align:center;\">Please wait, we are sending to us your endorsement data...<br><img src=\"images/ajax-loader.gif\" alt=\"ajax-loader.gif\" style=\"margin-top:10px;\"><br></p>";
           $('#Wait').empty().append(msg).dialog('open');
           $.post("funciones_endorsements.php",{accion:"send_endorsement_data", clave: clave},
            function(data){
                $('#Wait').empty().dialog('close');
                fn_solotrucking.mensaje(data.msj);
                if(data.error == '0'){fn_endorsement_co.fillgrid();}     
            },"json");
        },
        endorsement_resend_email : function(){
            $(fn_endorsement_co.data_grid + " tbody .btn_resend_email").bind("click",function(){
               var clave = $(this).parent().parent().find("td:eq(0)").html();
               msg = "<p style=\"text-align:center;\">Please wait, we are sending to us your endorsement data...<br><img src=\"images/ajax-loader.gif\" alt=\"ajax-loader.gif\" style=\"margin-top:10px;\"><br></p>";
               $('#Wait').empty().append(msg).dialog('open');
               $.post("funciones_endorsements.php",{accion:"resend_endorsement_data", clave: clave},
                function(data){
                    $('#Wait').empty().dialog('close');
                    fn_solotrucking.mensaje(data.msj);
                    if(data.error == '0'){fn_endorsement_co.fillgrid();}     
                },"json");
           });  
        },
        firstPage : function(){
            if($(fn_endorsement_co.data_grid+" #pagina_actual").val() != "1"){
                fn_endorsement_co.pagina_actual = "";
                fn_endorsement_co.fillgrid();
            }
        },
        previousPage : function(){
                if($(fn_endorsement_co.data_grid+" #pagina_actual").val() != "1"){
                    fn_endorsement_co.pagina_actual = (parseInt($(fn_endorsement_co.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_endorsement_co.fillgrid();
                }
        },
        nextPage : function(){
                if($(fn_endorsement_co.data_grid+" #pagina_actual").val() != $(fn_endorsement_co.data_grid+" #paginas_total").val()){
                    fn_endorsement_co.pagina_actual = (parseInt($(fn_endorsement_co.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_endorsement_co.fillgrid();
                }
        },
        lastPage : function(){
                if($(fn_endorsement_co.data_grid+" #pagina_actual").val() != $(fn_endorsement_co.data_grid+" #paginas_total").val()){
                    fn_endorsement_co.pagina_actual = $(fn_endorsement_co.data_grid+" #paginas_total").val();
                    fn_endorsement_co.fillgrid();
                }
        }, 
        ordenamiento : function(campo,objeto){
                $(fn_endorsement_co.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_endorsement_co.orden){
                    if(fn_endorsement_co.sort == "ASC"){
                        fn_endorsement_co.sort = "DESC";
                        $(fn_endorsement_co.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_endorsement_co.sort = "ASC";
                        $(fn_endorsement_co.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_endorsement_co.sort = "ASC";
                    fn_endorsement_co.orden = campo;
                    $(fn_endorsement_co.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_endorsement_co.fillgrid();

                return false;
        }, 
        filtraInformacion : function(){
                fn_endorsement_co.pagina_actual = 0;
                fn_endorsement_co.filtro = "";
                if($(fn_endorsement_co.data_grid+" .flt_id").val() != ""){ fn_endorsement_co.filtro += "A.iConsecutivo|"+$(fn_endorsement_co.data_grid+" .flt_id").val()+","}
                if($(fn_endorsement_co.data_grid+" .flt_category").val() != ""){ fn_endorsement_co.filtro += "B.sDescripcion|"+$(fn_endorsement_co.data_grid+" .flt_category").val()+","} 
                if($(fn_endorsement_co.data_grid+" .flt_action").val() != ""){ fn_endorsement_co.filtro += "eAccion|"+$(fn_endorsement_co.data_grid+" .flt_action").val()+","} 
                if($(fn_endorsement_co.data_grid+" .flt_datein").val() != ""){ fn_endorsement_co.filtro += "A.dFechaAplicacion|"+$(fn_endorsement_co.data_grid+" .flt_datein").val()+","} 
                if($(fn_endorsement_co.data_grid+" .flt_status").val() != ""){ fn_endorsement_co.filtro += "eStatus|"+$(fn_endorsement_co.data_grid+" .flt_status").val()+","}
                fn_endorsement_co.fillgrid();
        },
        //NUEVAS FUNCIONES:
        new_endorsement : function(type){
            if(type == '1'){ var form = "frm_endorsements_unit";} 
            else if(type == '2'){var form = "frm_endorsements_driver";}

            if(form != ""){
                $('#'+form+' input, #'+form+' select').removeClass('readonly').removeAttr('readonly').removeAttr('disabled');
                $('#'+form+' :text, #'+form+' select').val('').removeClass('error');
                $('#'+form+' .mensaje_valido').empty().append('The fields containing an (<span style="color:#ff0000;">*</span>) are required.');
                $('#'+form+' .btn-1').show(); 
                $('#'+form+' .msg-error').empty().hide();
                $('#'+form+' #policies_endorsement').empty();
                $('#'+form+' .frm_information').hide();              
                $('#'+form+' :checkbox').prop("checked","");
                $('#'+form+' .mensaje_valido + .field_item, #'+form+' .mensaje_valido').show(); 
                $('#'+form+' #iConsecutivoTipoEndoso').val(type); 
                fn_popups.resaltar_ventana(form);
            }else{
                
            }
             
        },
        edit : function (){
            $(fn_endorsement_co.data_grid + " tbody td .edit").bind("click",function(){
                
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                
                //Revisamos que tipo de endoso es:
                var TipoEndoso = $(this).parent().parent().find("td:eq(2)").prop('class'); 
                if(TipoEndoso == 'UNIT'){
                    var domroot = "frm_endorsements_unit"; 
                    var funcion = 'units';
                }
                else if(TipoEndoso == 'DRIVER'){
                    var domroot = "frm_endorsements_driver";
                    var funcion = 'drivers'; 
                }
                
                $.post("funciones_endorsements.php",{accion:"cargar_endoso", clave: clave, domroot : domroot},
                function(data){
                    if(data.error == '0'){
                       
                        $('#'+domroot+' input, #'+domroot+' select').val('').removeClass('error');
                        
                        //UNITS:
                        if(funcion == 'units'){
                           fn_endorsement_co.new_endorsement('1'); 
                           //Cargamos el action:
                           eval(data.action);
                           var action = $('#frm_endorsements_unit #frm_general_information #eAccion').val();
                           fn_endorsement_co.units.valid_action(action);
                           //Deseleccionamos las polizas para que se seleccionen con el eval dependiendo cuales agregaron al endoso:
                           $("#frm_endorsements_unit #policies_endorsement input:checkbox").prop('checked',''); 
                        }
                        //DRIVERS:
                        else if(funcion == 'drivers'){
                           fn_endorsement_co.new_endorsement('2');
                           eval(data.action);
                           fn_endorsement_co.drivers.valid_action($('#frm_endorsements_driver #frm_general_information #eAccion').val());
                           //Deseleccionamos las polizas para que se seleccionen con el eval dependiendo cuales agregaron al endoso:
                           $("#frm_endorsements_driver #policies_endorsement input:checkbox").prop('checked',''); 
                        }
                        
                        eval(data.fields);
                        if(action == 'D' && funcion == 'units'){fn_endorsement_co.units.valid_delete_atachments();} 
                        $('#frm_general_information .policies_endorsement').empty().append(data.policies_table);  
                        
                        if(funcion == 'drivers'){
                            //Revisar si manejan otros archivos aparte de la licencia:
                            if($('#frm_driver_add #iConsecutivoLTMPDF').val() != ''){$('#frm_driver_add .file_longterm').show();}else{$('#frm_driver_add .file_longterm').hide();}  
                            fn_endorsement_co.drivers.valid_country();
                        }

                       $('#'+domroot+' .mensaje_valido + .field_item, #'+domroot+' .mensaje_valido').show(); 
                       if(data.denied != ''){$('#'+domroot+' .msg-error').empty().append(data.denied).show();}
                        
                    }else{fn_solotrucking.mensaje(data.msj);}       
                },"json"); 
          });  
        },
        filtrar_polizas : function(iConsecutivo,tipo){
            $.ajax({
                type    : "POST",
                url     : "funciones_endorsements.php",
                data    : {'accion' : 'filtrar_polizas','iConsecutivo': iConsecutivo, 'tipo': tipo},
                async   : false,
                dataType: "json",
                success : function(data) {
                   switch(data.error){
                       case "0": 
                            //Deseleccionamos las polizas:
                            $("#frm_endorsements_"+tipo.toLowerCase()+" #policies_endorsement input:checkbox").prop('checked','').removeAttr('disabled');
                            $("#frm_endorsements_"+tipo.toLowerCase()+" #policies_endorsement label.check-label").css("opacity","1");
                            eval(data.polizas);
                            $("#frm_endorsements_"+tipo.toLowerCase()+" #policies_endorsement .sNumPolizas").each(function(index){
                                if(!(this.checked)){
                                  $(this).prop('disabled','disabled').attr('title','The unit does not in this policy.');
                                  $(this).parent().find('label.check-label').css("opacity","0.4");
                               }
                                 
                            });
                       break;
                       case "1": break;
                   }
                         
                   
                }
            });     
        },
        units : {
            cargar_units : function(){
               $.ajax({
                    type    : "POST",
                    url     : "funciones_endorsements.php",
                    data    : {'accion' : 'get_units'},
                    async   : false,
                    dataType: "json",
                    success : function(data) {
                       switch(data.error){
                           case "0": $("#frm_unit_delete #iConsecutivo").empty().append(data.select); break;
                           case "1": break;
                       }
                             
                       
                    }
               }); 
            },
            valid_action : function(action){
                $('#frm_endorsements_unit .frm_information').hide();
                if(action == 'D'){
                    $('#frm_endorsements_unit #frm_unit_delete').show();
                    fn_endorsement_co.units.cargar_units();
                    
                }else if(action == 'A'){
                    $('#frm_endorsements_unit #frm_unit_add').show();
                    $('#frm_endorsements_unit .file_ut, #frm_endorsements_unit .add_field').show();
                    fn_endorsement_co.units.cargar_catalogos();   
                }
                fn_endorsement_co.units.cargar_policies(); 
            },
            valid_delete_atachments : function(){

                 if($('#frm_unit_delete #DeleteCause').val() == ''){
                    $('#frm_unit_delete .file_uda').hide();
                    
                 }else if($('#frm_unit_delete #DeleteCause').val() == 'DA'){
                    
                    $('#frm_unit_delete .file_uda > label').empty().append("<label>Delease Agreement *: <span style='color:#9e2e2e;'>Please upload a copy of Unit's Delease Agreement in PDF format.</span></label>");
                
                 }else if($('#frm_unit_delete #DeleteCause').val() == 'BS'){
                    $('#frm_unit_delete .file_uda > label').empty().append("<label>Bill of Sale *: <span style='color:#9e2e2e;'>Please upload a copy of Unit's Bill of Sale in PDF format.</span></label>");
                
                 }else if($('#frm_unit_delete #DeleteCause').val() == 'NOR'){
                    $('#frm_unit_delete .file_uda > label').empty().append("<label>Non-Op Registration *: <span style='color:#9e2e2e;'>Please upload a copy of Unit's Non-Op Registration in PDF format.</span></label>");
                
                 }else if($('#frm_unit_delete #DeleteCause').val() == 'PTL'){
                    $('#frm_unit_delete .file_uda > label').empty().append("<label>Proof of Total Loss *: <span style='color:#9e2e2e;'>Please upload a copy of Unit's Proof of Total Loss in PDF format.</span></label>");
                 }
                 if($('#frm_unit_delete #DeleteCause').val() != ''){$('#frm_unit_delete .file_uda').show();}
            },
            valid_files : function(){
                $("#frm_endorsements_unit .files" ).each(function( index ){
                    if($('input.id_file',this).val() != '' && $('#frm_endorsements_unit #iConsecutivo_endoso').val() == ''){
                       $.ajax({             
                            type:"POST", 
                            url:"funciones_endorsements.php", 
                            data:{accion:"delete_unit_files", iConsecutivoFile : $('input.id_file',this).val()},
                            async : true,
                            dataType : "json",
                            success : function(data){}
                       });   
                    
                    }    
                });
                fn_popups.cerrar_ventana('frm_endorsements_unit'); 
            },
            cargar_catalogos : function(){
                //Cargar Modelos para unidades:
                $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsements.php", 
                    data:{accion:"get_unit_models"},
                    async : false,
                    dataType : "json",
                    success : function(data){                               
                        $("#frm_unit_add #iModelo").empty().append(data.select);
                         
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
                        $("#frm_unit_add #iConsecutivoRadio").empty().append(data.select);
                         
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
                        $("#frm_unit_add #iYear").empty().append(data.select);
                         
                    }
                });   
            },
            cargar_policies : function(){
                $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsements.php", 
                    data:{accion:"cargar_polizas"},
                    async : false,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){
                            $("#frm_endorsements_unit #policies_endorsement").empty().append(data.checkpolicies);
                        }
                    }
                }); 
                
            },
            valid_data : function(){
                  //Validate Fields:
                  var todosloscampos = $('#frm_endorsements_unit input, #frm_endorsements_unit select');
                  todosloscampos.removeClass("error");  
                  var valid = true;
                  
                  //1- Variables Generales del Endoso:
                  var Eaccion  = $("#frm_endorsements_unit #frm_general_information #eAccion");
                  var Eid      = $("#frm_endorsements_unit #frm_general_information #iConsecutivo");
                  var Etipo    = $("#frm_endorsements_unit #frm_general_information #iConsecutivoTipoEndoso");
                  
                  //2- Revisamos si se esta Editando el Endoso:
                  if(Eid.val() != ''){var edit_mode = "true";}else{var edit_mode = "false";} 
                  
                  //- Revisamos que por lo menos hayan seleccionado una poliza...
                  var sNumPolizas = "";
                   $("#frm_endorsements_unit #policies_endorsement .sNumPolizas").each(function(index){
                       if(this.checked){
                          if(sNumPolizas != ''){sNumPolizas += "|" + this.value;}
                          else{sNumPolizas += this.value;}
                          
                           if($(this).hasClass("PD") && Eaccion.val() == 'A'){  //<--- Si la poliza seleccionada es de tipo PD, revisamos PD Amount:
                              var PDAmount = $("#frm_unit_add #iPDAmount").val(); 
                              if(PDAmount == "" || parseInt(PDAmount) <= 0){
                                  valid = false;
                                  $("#frm_unit_add #iPDAmount").addClass('error');
                                  fn_solotrucking.mensaje('Please add a PD amount for your unit.');
                                  return false; 
                              }
                           } 
                       
                       }
                         
                   });
                   if(sNumPolizas == ""){
                       valid = false;
                       fn_solotrucking.mensaje('Please select the policies in which you want to apply the endorsement.');
                       return false;
                   }  
                  
                  //3 - Revisar Accion del Endoso: 
                  if(Eaccion.val() == 'D'){ 
                      //DELETE
                      var unidad_id = $("#frm_unit_delete #iConsecutivo");  
                      // - Revisamos que hayan seleccionado una unidad para eliminar de la lista...
                      if(unidad_id.val() == "" ){
                         unidad_id.addClass('error');
                         fn_solotrucking.mensaje('Please select first an unit/trailer from your list.');
                         valid = false; return false;  
                      }
                      
                      // - Revisamos los archivos subidos para hacer el Delete:
                      if($('#frm_unit_delete #DeleteCause').val() == '' || $('#frm_unit_delete #iConsecutivoDAPDF').val() == ''){
                          fn_solotrucking.mensaje('To delete a unit is necessary upload a least one of this files, please check.'); 
                          $('#DeleteCause').addClass('error'); 
                          valid = false; return false;
                      }
                      if(valid){
                          var iConsecutivoFiles  = $("#frm_unit_delete #iConsecutivoDAPDF").val();
                          //ACTUALIZAMOS LOS ARCHIVOS:  
                          fn_endorsement_co.units.update_files(iConsecutivoFiles,unidad_id.val()); 
                          //GUARDAMOS ENDOSO
                          fn_endorsement_co.units.save(Eid.val(),unidad_id.val(),Etipo.val(),Eaccion.val(),edit_mode,sNumPolizas);
                      } 
                      
                      
                  }else if(Eaccion.val() == 'A'){
                      //ENDOSO TIPO ADD
                      //YEAR, MAKE, RADIUS:
                       if($('#frm_unit_add div.required_field input').val() == '' || $('#frm_unit_add div.required_field select').val() == ''){
                             $('#frm_unit_add > div.required_field').each(function(index){
                                if($('input',this).val() == '' || $('select',this).val() == ''){
                                   $('input',this).addClass('error'); 
                                   $('select',this).addClass('error'); 
                                } 
                             }); 
                             valid = false;
                             fn_solotrucking.mensaje("Please make sure that all required fields are containing a not null value.");  
                             return false;  
                       }
                       
                       //VIN
                       valid = valid && fn_solotrucking.checkLength($('#frm_unit_add #sVIN'), "VIN Number", 17, 18);
                       var iConsecutivoFiles  = $("#frm_unit_add #iConsecutivoTituloPDF").val();

                       if(valid){ 
                           $.ajax({             
                                type:"POST", 
                                url:"funciones_endorsements.php", 
                                data:{
                                    'accion' : 'unidad_guardar',
                                    'iConsecutivo' : $('#frm_unit_add #iConsecutivo').val(),
                                    'iTotalPremiumPD' : $("#frm_unit_add #iPDAmount").val(),
                                    'sVIN' : $('#frm_unit_add #sVIN').val(),
                                    'iConsecutivoRadio' : $("#frm_unit_add #iConsecutivoRadio").val(),
                                    'iYear' : $("#frm_unit_add #iYear").val(),
                                    'iModelo' : $("#frm_unit_add #iModelo").val(),
                                    'sTipo' :  $("#frm_unit_add #sTipo").val(),
                                    'iValue' : $("#frm_unit_add #iValue").val(),
                                    'eModoIngreso' : "ENDORSEMENT",
                                },
                                async : false,
                                dataType : "json",
                                success : function(data){                               
                                    switch(data.error){
                                     case '0':
                                        var iConsecutivoUnidad = data.iConsecutivoUnidad; 
                                         //ACTUALIZAMOS LOS ARCHIVOS:
                                         if(iConsecutivoFiles != ""){fn_endorsement_co.units.update_files(iConsecutivoFiles,iConsecutivoUnidad);}
                                         //GUARDAMOS ENDOSO
                                         var iPDAmount = $("#frm_unit_add #iPDAmount").val();
                                         fn_endorsement_co.units.save(Eid.val(),iConsecutivoUnidad,Etipo.val(),Eaccion.val(),edit_mode,sNumPolizas,iPDAmount);    
                                     break;
                                     case '1': fn_solotrucking.mensaje(data.msj); break;
                                    }
                                }
                           }); 
                       }
       
                      
                  }else{
                     Eaccion.addClass('error');
                     valid = false;
                     fn_solotrucking.mensaje('Please select an action to the Endorsement.');
                     return false; 
                  }
            },
            save : function(iConsecutivo,iConsecutivoUnidad,iConsecutivoTipoEndoso,eAccion,edit_mode,sNumPolizas,iPDAmount){

                $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsements.php", 
                    data:{
                        'accion': "guardar_endoso",
                        'edit_mode' : edit_mode,
                        'iConsecutivo' : iConsecutivo,
                        'iConsecutivoTipoEndoso' : iConsecutivoTipoEndoso,
                        'eAccion' : eAccion,
                        'iConsecutivoUnidad' : iConsecutivoUnidad,
                        'sNumPolizas' : sNumPolizas,
                        'iPDAmount' : iPDAmount,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        switch(data.error){
                         case '0':
                            fn_solotrucking.mensaje(data.msj);
                            fn_endorsement_co.fillgrid();
                            fn_popups.cerrar_ventana('frm_endorsements_unit');
                         break;
                         case '1': fn_solotrucking.mensaje(data.msj); break;
                        }
                    }
                }); 
            },
            update_files : function(iConsecutivoFiles,iConsecutivoUnidad){
                $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsements.php", 
                    data:{'accion' : 'unidad_actualiza_archivos','iConsecutivoUnidad' : iConsecutivoUnidad,'iConsecutivo' : iConsecutivoFiles},
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        //fn_solotrucking.mensaje(data.mensaje);
                    }
               });     
            },
        }, 
        drivers : {
            cargar_drivers : function(){
               $.ajax({
                    type    : "POST",
                    url     : "funciones_endorsements.php",
                    data    : {'accion' : 'get_drivers'},
                    async   : false,
                    dataType: "json",
                    success : function(data) {
                       switch(data.error){
                           case "0": $("#frm_driver_delete #iConsecutivo").empty().append(data.select);break;
                           case "1": break;
                       }
                             
                       
                    }
               }); 
            },
            valid_country : function(){
               if($('#frm_endorsements_driver #eAccion').val() != 'D'){
                   if($('#frm_driver_add #eTipoLicencia').val() == 'COMMERCIAL/CDL-A'){  //<---- SI ES AMERICANO....
                        $('#frm_driver_add .file_mvr').show(); 
                        $('#frm_driver_add .file_psp').hide();
                           
                   }else if($('#frm_driver_add #eTipoLicencia').val() == 'FEDERAL/B1'){ //<-- SI ES MEXICANO
                        $('#frm_driver_add .file_psp').show();
                        $('#frm_driver_add .file_mvr').hide();
                   }
                   
               }else{$('#frm_driver_add .file_mvr, #frm_driver_add .file_psp').hide();}  
                
            }, 
            validate_age : function(){ 
                fecha = $('#frm_driver_add #dFechaNacimiento').val();
                fecha = fecha.split('/');
                fecha = fecha[1] + '/' + fecha[0] + '/' + fecha[2];
                var hoy = new Date();
                var sFecha = fecha || (hoy.getDate() + "/" + (hoy.getMonth() +1) + "/" + hoy.getFullYear());
                var sep = sFecha.indexOf('/') != -1 ? '/' : '-'; 
                var aFecha = sFecha.split(sep);
                var fecha = aFecha[2]+'/'+aFecha[1]+'/'+aFecha[0];
                fecha= new Date(fecha);
                  
                ed = parseInt((hoy-fecha)/365/24/60/60/1000);
                if(ed >= 65 && $('#frm_endorsements_driver #eAccion').val() != 'D'){
                   
                    $('#frm_driver_add .file_longterm').show(); 
                   
                }else{$('#frm_driver_add .file_longterm').hide();}
            },
            valid_files : function(){
                $("#frm_endorsements_driver .files" ).each(function( index ){
                    if($('input.id_file',this).val() != '' && $('#frm_endorsements_driver #iConsecutivo').val() == ''){
                       $.ajax({             
                            type:"POST", 
                            url:"funciones_endorsements.php", 
                            data:{accion:"delete_driver_files", iConsecutivoFile : $('input.id_file',this).val()},
                            async : true,
                            dataType : "json",
                            success : function(data){}
                       });   
                    
                    }    
                });
                fn_popups.cerrar_ventana('frm_endorsements_driver'); 
            }, 
            valid_action : function(action){
                $('#frm_endorsements_driver .frm_information').hide();
                if(action == 'D'){
                    $('#frm_endorsements_driver #frm_driver_delete').show();
                    fn_endorsement_co.drivers.cargar_drivers();
                    
                }else if(action == 'A'){
                    $('#frm_endorsements_driver #frm_driver_add').show();
                }
                fn_endorsement_co.drivers.cargar_policies(); 
            },
            cargar_policies : function(){
                $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsements.php", 
                    data:{accion:"cargar_polizas"},
                    async : false,
                    dataType : "json",
                    success : function(data){                               
                        if(data.error == '0'){
                            $("#frm_endorsements_driver #policies_endorsement").empty().append(data.checkpolicies);
                        }
                    }
                }); 
                
            },
            valid_data : function(){
                var todosloscampos = $('#frm_endorsements_unit input, #frm_endorsements_unit select');
                todosloscampos.removeClass("error");  
                var valid = true;
                
                //1- Variables Generales del Endoso:
                var Eaccion  = $("#frm_endorsements_driver #frm_general_information #eAccion");
                var Eid      = $("#frm_endorsements_driver #frm_general_information #iConsecutivo");
                var Etipo    = $("#frm_endorsements_driver #frm_general_information #iConsecutivoTipoEndoso");
              
                //2- Revisamos si se esta Editando el Endoso:
                if(Eid.val() != ''){var edit_mode = "true";}else{var edit_mode = "false";} 
              
                //- Revisamos que por lo menos hayan seleccionado una poliza...
                var sNumPolizas = "";
                $("#frm_endorsements_driver #policies_endorsement .sNumPolizas").each(function(index){
                    if(this.checked){
                      if(sNumPolizas != ''){sNumPolizas += "|" + this.value;} else{sNumPolizas += this.value;}
                   }
                     
                });
                if(sNumPolizas == ""){
                   valid = false;
                   fn_solotrucking.mensaje('Please select the policies in which you want to apply the endorsement.');
                   return false;
                }
                
                //Revisamos accion del endoso:
                if(Eaccion.val() == 'D'){
                    var driver_id = $("#frm_driver_delete #iConsecutivo");  
                    // - Revisamos que hayan seleccionado un driver para eliminar de la lista...
                    if(driver_id.val() == "" ){
                         driver_id.addClass('error');
                         fn_solotrucking.mensaje('Please select first a driver from your list.');
                         valid = false; return false;  
                    }
                    
                    if(valid){
                        //GUARDAMOS ENDOSO   
                        fn_endorsement_co.drivers.save(Eid.val(),driver_id.val(),Etipo.val(),Eaccion.val(),edit_mode,sNumPolizas);
                    }
                    
                }
                else if(Eaccion.val() == 'A'){
                  //CAMPOS REQUERIDOS...
                  if($('#frm_driver_add div.required_field input').val() == '' || $('#frm_driver_add div.required_field select').val() == ''){
                         $('#frm_driver_add > div.required_field').each(function(index){
                            if($('input',this).val() == '' || $('select',this).val() == ''){
                               $('input',this).addClass('error'); 
                               $('select',this).addClass('error'); 
                            } 
                         }); 
                         valid = false;
                         fn_solotrucking.mensaje("Please make sure that all required fields are containing a not null value.");  
                         return false;  
                  }  
                  //Validando años de experiencia del operador (mayor a 2):
                   if($('#frm_driver_add #iExperienciaYear').val() < 2){
                       valid = false;
                       fn_solotrucking.mensaje('The driver must have at least two years of experience, please check it');
                       return false;
                   } 
                   //Validando archivos...
                   if($('#frm_driver_add #txtsLicenciaPDF').val() == '' || $('#frm_driver_add #iConsecutivoLicenciaPDF').val() == ''){
                     valid = false;
                     fn_solotrucking.actualizarMensajeAlerta('Please upload the license of driver in PDF Format.');
                     $('#frm_driver_add #txtsLicenciaPDF').addClass('error'); 
                     return false; 
                        
                   }
                   if($('#frm_driver_add .file_longterm').is(":visible") && ($('#frm_driver_add #txtsLTMPDF').val() == '' || $('#frm_driver_add #iConsecutivoLTMPDF').val() == '')){
                     valid = false;
                     fn_solotrucking.actualizarMensajeAlerta('Please upload the Long Term Medical of the driver in PDF Format.');
                     $('#frm_driver_add #txtsLTMPDF').addClass('error'); 
                     return false;    
                   }
                   
                   //validando campos fecha:
                   $("#frm_driver_add .fecha" ).each(function( index ){
                         valid = valid && fn_solotrucking.checkRegexp($(this), /^(0[1-9]|1[012])[- \/.](0[1-9]|[12][0-9]|3[01])[- \/.](19|20)\d\d$/);
                         if(!valid){$(this).addClass('error');} 
                         return valid;   
                   });
                   if(!valid){fn_solotrucking.actualizarMensajeAlerta("The date format is not valid, please check it..");return false;}
                   
                   if(valid){
                       $.ajax({             
                            type:"POST", 
                            url:"funciones_endorsements.php", 
                            data:{
                                'accion'           : 'driver_guardar',
                                'iConsecutivo'     : $('#frm_driver_add #iConsecutivo').val(),
                                'sNombre'          : $("#frm_driver_add #sNombre").val(),
                                'dFechaNacimiento' : $('#frm_driver_add #dFechaNacimiento').val(),
                                'eTipoLicencia'    : $("#frm_driver_add #eTipoLicencia").val(),
                                'iNumLicencia'     : $("#frm_driver_add #iNumLicencia").val(),
                                'dFechaExpiracionLicencia' : $("#frm_driver_add #dFechaExpiracionLicencia").val(),
                                'iExperienciaYear' :  $("#frm_driver_add #iExperienciaYear").val(),
                                'eModoIngreso' : "ENDORSEMENT",
                            },
                            async : false,
                            dataType : "json",
                            success : function(data){                               
                                switch(data.error){
                                 case '0':
                                    var iConsecutivoDriver = data.iConsecutivoDriver; 
                                    //ACTUALIZAMOS LOS ARCHIVOS:
                                    var iConsecutivosFiles = "";
                                    $("#frm_driver_add .files .id_file" ).each(function(index){
                                        if(this.value != ""){
                                          if(iConsecutivosFiles != ''){iConsecutivosFiles += "|" + this.value;} else{iConsecutivosFiles += this.value;}
                                        }   
                                    });
                                    
                                    fn_endorsement_co.drivers.update_files(iConsecutivosFiles,iConsecutivoDriver);
                                    //GUARDAMOS ENDOSO
                                    fn_endorsement_co.drivers.save(Eid.val(),iConsecutivoDriver,Etipo.val(),Eaccion.val(),edit_mode,sNumPolizas);    
                                 break;
                                 case '1': fn_solotrucking.mensaje(data.msj); break;
                                }
                            }
                       });    
                   }
                    
                }     
            },
            update_files : function(iConsecutivosFiles,iConsecutivoDriver){
                $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsements.php", 
                    data:{'accion' : 'driver_actualiza_archivos','iConsecutivoOperador' : iConsecutivoDriver,'iConsecutivosFiles' : iConsecutivosFiles},
                    async : true,
                    dataType : "json",
                    success : function(data){}
               });     
            },
            save : function(iConsecutivo,iConsecutivoDriver,iConsecutivoTipoEndoso,eAccion,edit_mode,sNumPolizas,iPDAmount){

                $.ajax({             
                    type:"POST", 
                    url:"funciones_endorsements.php", 
                    data:{
                        'accion': "guardar_endoso",
                        'edit_mode' : edit_mode,
                        'iConsecutivo' : iConsecutivo,
                        'iConsecutivoTipoEndoso' : iConsecutivoTipoEndoso,
                        'eAccion' : eAccion,
                        'iConsecutivoOperador' : iConsecutivoDriver,
                        'sNumPolizas' : sNumPolizas,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        switch(data.error){
                         case '0':
                            fn_solotrucking.mensaje(data.msj);
                            fn_endorsement_co.fillgrid();
                            fn_popups.cerrar_ventana('frm_endorsements_driver');
                         break;
                         case '1': fn_solotrucking.mensaje(data.msj); break;
                        }
                    }
                }); 
            },
        },
  
}    

 
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_endorsement_co" class="container">
        <div class="page-title">
            <h1>ENDORSEMENTS</h1>
            <h2 style="margin-bottom: 5px;">APPLICATIONS</h2>
            <div class="help-information">
                <div class="btn-text help btn-right" title="Endorsement help information"  onclick="fn_popups.resaltar_ventana('endorsements_help_info');"><i class="fa fa-question-circle"></i><span>Help Information</span></div>  
            </div>
        </div>
        <div id="info_policies">
        <h3 class="popup-gridtit clear"></h3>
        <p style="width: 98%;margin: 10px auto 5px;">Your endorsements will apply in following policies:</p>
        <table class="popup-datagrid">
            <thead>
                <tr id="grid-head2"> 
                    <td class="etiqueta_grid">Policy Number</td>
                    <td class="etiqueta_grid">Broker</td>
                    <td class="etiqueta_grid">Insurance</td> 
                    <td class="etiqueta_grid">Policy Type</td>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        </div>
        <br>
        <table id="data_grid_endorsement_co" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'>
                <input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td>
                    <select class="flt_category" type="text" onblur="fn_endorsement_co.filtraInformacion();">
                        <option value="">Select a Type...</option>
                        <option value="Driver">Driver</option>
                        <option value="Unit">Unit or Trailer</option>  
                    </select>
                </td>
                <td><input class="flt_action" type="text" placeholder="Action:"></td> 
                <td><input class="flt_datein" type="text" placeholder="Application Date:"></td>
                <td><select class="flt_status" onblur="fn_endorsement_co.filtraInformacion();">
                        <option value="">Select an option...</option>
                        <option value="S">NEW APLICATION</option>
                        <option value="SB">SENT TO BROKERS</option>
                        <option value="D">DENIED</option>
                        <option value="P">IN PROGRESS</option>
                        <option value="A">APPROVED</option>
                    </select></td>
                </td>  
                <td style='width:160px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_endorsement_co.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="New Endorsement +"  onclick="$('#dialog_types').dialog('open');"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid"      onclick="fn_endorsement_co.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_endorsement_co.ordenamiento('B.sDescripcion',this.cellIndex);">type / Description</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement_co.ordenamiento('eAccion',this.cellIndex);">Action</td> 
                <td class="etiqueta_grid up"   onclick="fn_endorsement_co.ordenamiento('A.dFechaAplicacion',this.cellIndex);">Application Date</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement_co.ordenamiento('eStatus',this.cellIndex);">Status</td>
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
                        <button id="pgn-inicio"    onclick="fn_endorsement_co.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_endorsement_co.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_endorsement_co.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_endorsement_co.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>    
    </div>
</div>
<!--- formulario help --->
<div id="endorsements_help_info" class="popup-form">
    <div class="p-header">
        <h2>ENDORSEMENTS HELP INFORMATION</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('endorsements_help_info');"><i class="fa fa-times"></i></div>
    </div>
    <div id="accordion_help" class="p-container">
    <ul>                           
        <br>
        <li>Module Description</li>
        <div>
            In following picture you can see a table with the list of applications for existing endorsements or message "No data Available" if you have not discharged any yet.
            <br><br>
            <img src="./documentos/system_help/endorsement_help/img_1.jpg" border="0" width="824" height="271" alt="img_1.jpg (87,497 bytes)">
            <ul style="text-align:left;margin:0;list-style:decimal;">
               <li>Displays data of your current policies in which you can apply an endorsement.</li> 
               <li>Displays your endorsement applications and its description.</li>
            </ul>
        </div>
        <li>Additional Options: Filter, Sorting and Pagination.</li>
        <div>
            Each module provides data handle more easily and dynamically for you, then explains each of the options and how to use it:
            <br><br>
            <img src="./documentos/system_help/endorsement_help/img_2.jpg" border="0" width="824" height="271" alt="img_2.jpg (87,497 bytes)">
            <ul style="text-align:left;margin:0;list-style:none;">
               <li>A. FILTERS: Used to purge the data shown in the table, writing a value according to the column where the filter is. Once written the value only just press the "Enter" key or click on the "search" button.</li> 
               <li>B. SORTING: They are used to sort the records by clicking on the desired column. (ASC or DESC)</li>
               <li>C. PAGINATION: Are the options for managing the pages of the table if it contains more than 15 entries.</li>
            </ul>
        </div>
    </ul>   
    </div>
</div>
<!--- DIALOGUES --->
<div id="dialog_delete_endorsement_co" title="SYSTEM ALERT" style="display:none;">
    <p>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
    <form id="elimina" method="post">
           <input type="hidden" name="id_endorsement_co" id="id_endorsement_co">
    </form>  
</div> 
<div id="dialog_quote_unit" title="SYSTEM MESSAGE" style="display:none;">
    <p>For unit add endorsements, we recommend send first a quote to verify the amount for add this unit to your policies.  Please select an of following options:</p>
    <form method="post">
           <input type="hidden" name="id_endorsement" id="id_endorsement">
    </form>  
</div> 
<!---- NUEVOS FORMULARIOS PARA ENDOSOS---->
<!---- UNITS --->
<div id="frm_endorsements_unit" class="popup-form">
    <div class="p-header">
        <h2>ENDORSEMENTS - UNITS / TRAILERS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_endorsement_co.units.valid_files();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <p class="msg-error error" style="padding:10px;margin-bottom:10px;display:none;"></p>
    <div>
        <form>
            <fieldset>
                <legend>Endorsement Information</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <div id="frm_general_information">
                <div class="field_item">
                    <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                    <input id="iConsecutivoTipoEndoso" type="hidden" value="">                 
                </div>
                <div class="field_item"> 
                  <label>Action <span style="color:#ff0000;">*</span>: </label>
                  <Select id="eAccion" onblur="fn_endorsement_co.units.valid_action(this.value);">
                    <option value="">Select an option...</option> 
                    <option value="A">ADD</option>
                    <option value="D">DELETE</option>
                  </select>
                </div>
                <div id="policies_endorsement" class="field_item"></div>
            </div> 
            </fieldset>
            <!--- UNITS DELETE -->
            <fieldset id="frm_unit_delete" class="frm_information" style="display:none;">
                <legend>Unit Information</legend>
                <div class="field_item required_field"> 
                    <label>VIN# <span style="color:#ff0000;">*</span>: </label>
                    <Select id="iConsecutivo" onchange="fn_endorsement_co.filtrar_polizas(this.value,'UNIT');"><option value="">Select an option...</option> </select>
                </div>
                <div class="field_item delete_field">
                    <label>Attachment File <span style="color:#ff0000;">*</span>: </label>
                    <Select id="DeleteCause" onblur="fn_endorsement_co.units.valid_delete_atachments();">
                        <option value="">Select an option...</option>
                        <option value="DA">Delease Agreement</option>   
                        <option value="BS">Bill of Sale</option>   
                        <option value="NOP">Non-Op Registration</option>   
                        <option value="PTL">Proof of Total Loss</option>   
                    </select> 
                </div> 
                <div class="file_uda files" style="display:none;"> 
                    <label>Delease Agreement <span style="color:#ff0000;">*</span>: <span style="color:#9e2e2e;">Please upload a copy of Unit's Delease Agreement in PDF or JPG format.</span></label>
                    <input type="text" id="txtsDAPDF" readonly="readonly" value="" size="40" style="width:85%;" />
                    <button id="btnsDAPDF" type="button">Upload File</button>
                    <input id="iConsecutivoDAPDF" class="id_file" type="hidden">
                </div> 
            </fieldset>
            <!--- UNITS ADD --->
            <fieldset id="frm_unit_add" class="frm_information" style="display:none;">
                <legend>Unit Information</legend>
                <!---- only if the company has a PD Policy --->
                <div id="pd_information" class="field_item" style="display:none;">
                    <label>PD Amount $ <span style="color:#ff0000;">*</span>:</label>
                    <input id="iPDAmount" name="iPDAmount" type="text" class="decimals"> 
                </div>
                <!---- /only if the company has a PD Policy --->
                <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                <div class="field_item required_field field-add"> 
                      <label>Type <span style="color:#ff0000;">*</span>: </label>
                      <Select id="sTipo">
                        <option value="">Select an option...</option>
                        <option value="UNIT">Unit</option>
                        <option value="TRAILER">Trailer</option>
                      </select>
                </div>
                <div class="field_item required_field"> 
                    <label>Year <span style="color:#ff0000;">*</span>: </label>
                    <Select id="iYear"><option value="">Select an option...</option></select> 
                </div>
                <div class="field_item required_field"> 
                    <label>VIN Number <span style="color:#ff0000;">*</span>: </label>
                    <input id="sVIN" type="text" class="txt-uppercase" maxlength="18">
                </div>
                <div class="field_item required_field"> 
                      <label>Make: </label>
                      <Select id="iModelo"><option value="">Select an option...</option></select>
                </div>
                <div class="field_item required_field"> 
                      <label>Radius <span style="color:#ff0000;">*</span>: </label>
                      <Select id="iConsecutivoRadio" onblur=""><option value="">Select an option...</option></select>
                </div>
                <div class="file_ut files"> 
                    <label>Title: <span style="color:#9e2e2e;">Please upload a copy of Unit's Title in PDF or JPG format.</span></label>
                    <input type="text" id="txtsTituloPDF" readonly="readonly" value="" size="40" style="width:85%;" />
                    <button id="btnsTituloPDF" type="button">Upload File</button>
                    <input id="iConsecutivoTituloPDF" class="id_file" type="hidden">
                </div>
            </fieldset>
            <br>  
            <button type="button" class="btn-1" onclick="fn_endorsement_co.units.valid_data();">SAVE</button>
            <button type="button" class="btn-1" onclick="fn_endorsement_co.units.valid_files();" style="margin-right:10px;background:#e8051b;">CLOSE</button>
        </form> 
    </div>
    </div>
</div>
<!--- DRIVERS--->
<div id="frm_endorsements_driver" class="popup-form">
    <div class="p-header">
        <h2>ENDORSEMENTS - DRIVERS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_endorsement_co.drivers.valid_files();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <p class="msg-error error" style="padding:10px;margin-bottom:10px;display:none;"></p>
    <div>
        <form>
            <fieldset>
                <legend>Endorsement Information</legend>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <div id="frm_general_information">
                <div class="field_item">
                    <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                    <input id="iConsecutivoTipoEndoso" type="hidden" value="">                 
                </div>
                <div class="field_item"> 
                  <label>Action <span style="color:#ff0000;">*</span>: </label>
                  <Select id="eAccion" onblur="fn_endorsement_co.drivers.valid_action(this.value);">
                    <option value="">Select an option...</option> 
                    <option value="A">ADD</option>
                    <option value="D">DELETE</option>
                  </select>
                </div>
                <div id="policies_endorsement" class="field_item"></div>
            </div> 
            </fieldset>
            <!---- DELETE ----> 
            <fieldset id="frm_driver_delete" class="frm_information" style="display:none;">
                <legend>Driver Information</legend>
                <div class="field_item required_field"> 
                    <label>NAME / LICENSE <span style="color:#ff0000;">*</span>: </label>
                    <Select id="iConsecutivo" onchange="fn_endorsement_co.filtrar_polizas(this.value,'DRIVER');"><option value="">Select an option...</option> </select>
                </div>
            </fieldset>
            <!---- ADD ---->
            <fieldset id="frm_driver_add" class="frm_information" style="display:none;">
                <legend>Driver Information</legend> 
                <input id="iConsecutivo" name="iConsecutivo" type="hidden"> 
                <div class="field_item required_field">
                    <label>Name <span style="color:#ff0000;">*</span>: </label>
                    <input id="sNombre" type="text" placeholder="Please write a name..." class="txt-uppercase">
                </div>
                <div class="field_item required_field"> 
                    <label>Birthdate <span style="color:#ff0000;">*</span>: </label>
                    <input id="dFechaNacimiento" type="text" class="txt-uppercase fecha" onblur="fn_endorsement_co.drivers.validate_age();">
                </div>
                <div class="field_item"> 
                      <label>Licence Type: </label>
                      <Select id="eTipoLicencia" onblur="fn_endorsement_co.drivers.valid_country();">
                        <option value="">Select an option...</option>
                        <option value="FEDERAL/B1">FEDERAL / B1</option>
                        <option value="COMMERCIAL/CDL-A">COMMERCIAL / CDL-1</option>
                      </select>
                </div>
                <div class="field_item required_field"> 
                    <label>License Number <span style="color:#ff0000;">*</span>: </label>
                    <input id="iNumLicencia" class="txt-uppercase" maxlength="10" type="text" placeholder="Please write the license number...">
                </div>
                <div class="field_item"> 
                    <label>Expiration Date: </label>
                    <input id="dFechaExpiracionLicencia" type="text" class="txt-uppercase fecha">
                </div>
                <div class="field_item required_field"> 
                    <label>Experience Years <span style="color:#ff0000;">*</span>: </label>
                    <input id="iExperienciaYear" class="num txt-uppercase" type="text" placeholder="Please write only the number.">
                </div>
                <div class="file_dl files"> 
                    <label>Driver License <span style="color:#ff0000;">*</span>: <span style="color:#9e2e2e;">Please upload a copy of driver's license in PDF or JPG format.</span></label>
                    <input type="text" id="txtsLicenciaPDF" readonly="readonly" value="" size="40" style="width:85%;" />
                    <button id="btnsLicenciaPDF" type="button">Upload File</button>
                    <input id="iConsecutivoLicenciaPDF" class="id_file" type="hidden">
                </div>
                <div class="file_mvr files" style="display:none;"> 
                    <label>MVR (Motor Vehicle Record) <span style="color:#ff0000;">*</span>: <span style="color:#9e2e2e;">Please upload a copy of the record of the driver in PDF or JPG format.</span></label>
                    <input  id="txtsMVRPDF" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                    <button id="btnsMVRPDF" type="button">Upload File</button>
                    <input  id="iConsecutivoMVRPDF" class="id_file" type="hidden">
                    <div style="display:none;">
                        <label style="display: block;float: left;margin: 8px 5px 0px;">If you don't have the Driver MVR, We can process it for you: </label><div class="btn-text buy btn-left" title="Order processing of MVR / $15 Dlls + TAX"  onclick="" style="width: auto!important;"><i class="fa fa-usd"></i><span>Order MVR</span></div>
                    </div>
                </div>
                <div class="file_longterm files" style="display:none;"> 
                    <label>Long Term Medical Form <span style="color:#ff0000;">*</span>: <span style="color:#9e2e2e;">Please upload a copy of the form in PDF or JPG format.</span></label> 
                    <input  id="txtsLTMPDF" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                    <button id="btnsLTMPDF" type="button">Upload File</button>
                    <input  id="iConsecutivoLTMPDF" class="id_file" type="hidden">
                </div> 
                <div class="file_psp files" style="display:none;"> 
                    <label title="(This file is only required if you have a policy with Northern Star)">PSP: <span style="color:#9e2e2e;">Please upload a copy of Pre-employment Screening Program in PDF or JPG format.</span></label>
                    <input  id="txtsPSPFile" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                    <button id="btnsPSPFile" type="button">Upload File</button>
                    <input  id="iConsecutivoPSPFile" class="id_file" type="hidden">
                </div>
            </fieldset>
            <br>  
            <button type="button" class="btn-1" onclick="fn_endorsement_co.drivers.valid_data();">SAVE</button>
            <button type="button" class="btn-1" onclick="fn_endorsement_co.drivers.valid_files();" style="margin-right:10px;background:#e8051b;">CLOSE</button>
        </form> 
    </div>
    </div>
</div>
<!---- TERMINAN NUEVO FORMULARIOS PARA ENDOSOS --->
<!--- DIALOGS -->
<div id="dialog_types" title="SYSTEM MESSAGE" style="display:none;">
    <p>Please select the endorsement type:</p>
    <form>  
    <div class="field_item_operador"> 
      <Select id="TipoEndoso">
        <option value="">Select an option...</option>
        <option value="1">Unit or Trailer</option>
        <option value="2">Driver</option>
      </select>
    </div>
    </form>   
</div> 
<div id="dialog_select_company" title="SYSTEM MESSAGE" style="display:none;">
    <p>Please select a company first:</p>
    <form><div> <Select id="iConsecutivoCompania"><option value="">Select an option...</option></select></div></form>   
</div> 

<!---- FOOTER ----->
<?php include("footer.php"); ?> 
</body>
</html>
<?php } ?>
