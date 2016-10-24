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
        fn_policies.init();
        fn_policies.fillgrid();
        $.unblockUI();
        
        $('#dialog_delete_policy').dialog({
            modal: true,
            autoOpen: false,
            width : 300,
            height : 200,
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
        
        
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
var fn_policies = {
        domroot:"#ct_policies",
        data_grid: "#data_grid_policies",
        form : "#policies_edit_form",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "A.iConsecutivo",
        init : function(){
            fn_policies.fillgrid();
            $('.num').keydown(fn_solotrucking.inputnumero());  
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
                }
            });
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_brokers"},
                async : true,
                dataType : "json",
                success : function(data){$(fn_policies.form + " #iConsecutivoBrokers").empty().append(data.select);}
            });
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_policy_types"},
                async : true,
                dataType : "json",
                success : function(data){$(fn_policies.form + " #iTipoPoliza").empty().append(data.select);}
            });
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_insurances"},
                async : true,
                dataType : "json",
                success : function(data){$(fn_policies.form + " #iConsecutivoAseguranza").empty().append(data.select);}
            });
            //Filtrado con la tecla enter
            $(fn_policies.data_grid + ' #grid-head1 input').keyup(function(event){
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
                                'iConsecutivo':$('#iConsecutivoArchivo').val(), 
                                'iConsecutivoCompania':$('#iConsecutivoCompania').val(),
                                'sNombreCompania':$('#iConsecutivoCompania option:selected').text(),
                                'iConsecutivoPoliza':$('#iConsecutivo').val(),
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
                                'iConsecutivo':$('#iConsecutivoArchivoPFA').val(), 
                                'iConsecutivoCompania':$('#iConsecutivoCompania').val(),
                                'sNombreCompania':$('#iConsecutivoCompania option:selected').text(),
                                'iConsecutivoPoliza':$('#iConsecutivo').val(),
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
            
            /*----- DRIVER UNIT TXT -------*/
            new AjaxUpload('#btnFile', {
                    action: 'funciones_policies.php',
                    onSubmit : function(file , ext){
                        if (!(ext && (/^(txt)$/i.test(ext))  )){
                            var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: Invalid file format, please a TXT File.</p>';
                            fn_solotrucking.mensaje(mensaje);
                            return false;
                        }else{
                            
                            var policies_selected = "";
                            $("#file_edit_form .company_policies .num_policies" ).each(function( index ){
                                   if(this.checked){
                                      if(policies_selected != ''){policies_selected += "," + this.value; }else{policies_selected += this.value;} 
                                   }
                                     
                            });
                            
                            if(policies_selected != '' && $('#file_edit_form #iConsecutivoCompania').val() != '' && $('#file_edit_form #TypeFile').val() != ''){
                                if($('#file_edit_form #TypeFile').val() == 'D'){var action = 'upload_driver_txt';}else if($('#file_edit_form #TypeFile').val() == 'U'){var action = 'upload_unit_txt';}
                                this.setData({
                                    'accion':action, 
                                    'iConsecutivoCompania':$('#file_edit_form #iConsecutivoCompania').val(),
                                    'iConsecutivoPolizas':policies_selected,
                                });
                                $('#txtFile').val('loading...');
                                this.disable();
                            }else{
                                fn_solotrucking.actualizarMensajeAlerta('Please verify if all required fields has a value.');
                            }
                        }
                    },
                    onComplete : function(file,response){  
                        var respuesta = JSON.parse(response);
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
           $('.policy_jacker_file').hide();
           $(fn_policies.form + ' #sNumeroPoliza ,' + fn_policies.form + ' #iConsecutivoCompania').removeAttr('readonly').removeClass('readonly');
           fn_popups.resaltar_ventana('policies_edit_form');  
        },
        edit : function (){
            $(fn_policies.data_grid + " tbody td .edit").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                $.post("funciones_policies.php",{accion:"get_policy", clave: clave, domroot : "policies_edit_form"},
                function(data){
                    if(data.error == '0'){
                       $(fn_policies.form+' input, '+fn_policies.form+' select').val('').removeClass('error'); 
                       $(fn_policies.form + ' #sNumeroPoliza ,' + fn_policies.form + ' #iConsecutivoCompania').attr('readonly','readonly').addClass('readonly');
                       eval(data.fields); 
                       fn_policies.validate_type();
                       $('.policy_jacker_file').show(); 
                       fn_popups.resaltar_ventana('policies_edit_form');
                         
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json"); 
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
           if(iConsecutivoBrokers.val() == ''){
               fn_solotrucking.mensaje('Please check the broker field has a value.');iConsecutivoBrokers.addClass('error');
               valid=false;
               return false;
           }
           if(iTipoPoliza.val() == ''){
               fn_solotrucking.mensaje('Please check the field for the type has a value.');iTipoPoliza.addClass('error');
               valid=false;
               return false;
           }
          
           if(valid){
             if($(fn_policies.form + ' #sNumeroPoliza').attr("readonly")){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
             struct_data_post.action="save_policy";
             struct_data_post.domroot= fn_policies.form; 
                $.post("funciones_policies.php",struct_data_post.parse(),
                function(data){
                    switch(data.error){
                     case '0':
                        fn_solotrucking.mensaje(data.msj);
                        //fn_policies.fillgrid();
                        //fn_popups.cerrar_ventana('policies_edit_form');
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
               var clave = $(this).parent().parent().find("td:eq(0)").html();
               $('#dialog_delete_policy #id_policy').val(clave);
               $('#dialog_delete_policy').dialog( 'open' );
               return false;
           });  
        },
        delete_policy : function(id){
          $.post("funciones_policies.php",{accion:"delete_policy", 'clave': id},
           function(data){
                fn_solotrucking.mensaje(data.msj);
                fn_policies.fillgrid();
           },"json");  
        },
        firstPage : function(){
            if($(fn_policies.data_grid+" #pagina_actual").val() != "1"){
                fn_policies.pagina_actual = "";
                fn_policies.fillgrid();
            }
        },
        previousPage : function(){
            if($(fn_policies.data_grid+" #pagina_actual").val() != "1"){
                fn_policies.pagina_actual = (parseInt($(fn_policies.data_grid+" #pagina_actual").val()) - 1) + "";
                fn_policies.fillgrid();
            }
        },
        nextPage : function(){
            if($(fn_policies.data_grid+" #pagina_actual").val() != $(fn_policies.data_grid+" #paginas_total").val()){
                fn_policies.pagina_actual = (parseInt($(fn_policies.data_grid+" #pagina_actual").val()) + 1) + "";
                fn_policies.fillgrid();
            }
        },
        lastPage : function(){
            if($(fn_policies.data_grid+" #pagina_actual").val() != $(fn_policies.data_grid+" #paginas_total").val()){
                fn_policies.pagina_actual = $(fn_policies.data_grid+" #paginas_total").val();
                fn_policies.fillgrid();
            }
        }, 
        ordenamiento : function(campo,objeto){
            $(fn_policies.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_policies.orden){
                if(fn_policies.sort == "ASC"){
                    fn_policies.sort = "DESC";
                    $(fn_policies.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_policies.sort = "ASC";
                    $(fn_policies.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_policies.sort = "ASC";
                fn_policies.orden = campo;
                $(fn_policies.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
            }
            fn_policies.fillgrid();

            return false;
        }, 
        filtraInformacion : function(){
            fn_policies.pagina_actual = 0;
            fn_policies.filtro = "";
            if($(fn_policies.data_grid+" .flt_pid").val() != ""){ fn_policies.filtro += "A.iConsecutivo|"+$(fn_policies.data_grid+" .flt_pid").val()+","}
            if($(fn_policies.data_grid+" .flt_pcompany").val() != ""){ fn_policies.filtro += "sNombreCompania|"+$(fn_policies.data_grid+" .flt_pcompany").val()+","} 
            if($(fn_policies.data_grid+" .flt_policynumber").val() != ""){ fn_policies.filtro += "sNumeroPoliza|"+$(fn_policies.data_grid+" .flt_policynumber").val()+","} 
            if($(fn_policies.data_grid+" .flt_pbroker").val() != ""){ fn_policies.filtro += "sName|"+$(fn_policies.data_grid+" .flt_pbroker").val()+","}  
            if($(fn_policies.data_grid+" .flt_policytype").val() != ""){ fn_policies.filtro += "sDescripcion|"+$(fn_policies.data_grid+" .flt_policytype").val()+","} 
            if($(fn_policies.data_grid+" .flt_policystartdate").val() != ""){ fn_policies.filtro += "dFechaInicio|"+$(fn_policies.data_grid+" .flt_policystartdate").val()+","} 
            if($(fn_policies.data_grid+" .flt_policyexpdate").val() != ""){ fn_policies.filtro += "dFechaCaducidad|"+$(fn_policies.data_grid+" .flt_policyexpdate").val()+","}    
            fn_policies.fillgrid();
        }, 
        upload_file : function(){
            $(fn_policies.data_grid + " tbody td .btn_upload_policy").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                
           });  
        }, 
        //FUNCIONES PARA SUBIR TXT DE DRIVER OR UNITS:
        get_company_policies : function(){
            var company = $('#file_edit_form #iConsecutivoCompania').val();
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
        get_list_description : function(policy,company){

            fn_policies.list.id_policy = policy; 
            fn_policies.list.id_company = company;
            fn_policies.list.fill_drivers_actives();
            fn_policies.list.fill_units_actives();
            $("#driver_tabs" ).tabs('option', 'active', 0); 
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
            filtraInformacion : function(){
                fn_policies.list.drivers_pagina_actual = 0;
                fn_policies.list.filtro = "";
                if($(fn_policies.list.domroot_nav+" .flt_dName").val() != ""){ fn_policies.list.filtro += "sNombre|"+$(fn_policies.list.domroot_nav+" .flt_dName").val()+","}
                if($(fn_policies.list.domroot_nav+" .flt_dDob").val() != ""){ fn_policies.list.filtro += "dFechaNacimiento|"+$(fn_policies.list.domroot_nav+" .flt_dDob").val()+","} 
                if($(fn_policies.list.domroot_nav+" .flt_dLicense").val() != ""){ fn_policies.list.filtro += "iNumLicencia|"+$(fn_policies.list.domroot_nav+" .flt_dLicense").val()+","} 
                if($(fn_policies.list.domroot_nav+" .flt_dtype").val() != ""){ fn_policies.list.filtro += "eTipoLicencia|"+$(fn_policies.list.domroot_nav+" .flt_dtype").val()+","}  
                if($(fn_policies.list.domroot_nav+" .flt_dExpire").val() != ""){ fn_policies.list.filtro += "dFechaExpiracionLicencia|"+$(fn_policies.list.domroot_nav+" .flt_dExpire").val()+","} 
                if($(fn_policies.list.domroot_nav+" .flt_dYears").val() != ""){ fn_policies.list.filtro += "iExperienciaYear|"+$(fn_policies.list.domroot_nav+" .flt_dYears").val()+","} 
                fn_policies.list.fill_drivers_actives();
            },    
            fill_drivers_actives : function(){
                 $.ajax({             
                    type:"POST", 
                    url:"funciones_policies.php", 
                    data:{
                        accion:"get_drivers_active",
                        registros_por_pagina : "15", 
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
                        $(fn_policies.list.domroot_nav+" #drivers_active_table tbody tr:even").addClass('gray');
                        $(fn_policies.list.domroot_nav+" #drivers_active_table tbody tr:odd").addClass('white');
                        $(fn_policies.list.domroot_nav+" #drivers_active_table tfoot .paginas_total").val(data.total);
                        $(fn_policies.list.domroot_nav+" #drivers_active_table tfoot .pagina_actual").val(data.pagina);
                        fn_policies.list.drivers_pagina_actual = data.pagina; 
                        //fn_policies.list.edit();
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
            fill_units_actives : function(){
                 $.ajax({             
                    type:"POST", 
                    url:"funciones_policies.php", 
                    data:{
                        accion:"get_units_active",
                        registros_por_pagina : "15", 
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
                        $(fn_policies.list.domroot_nav+" #units_active_table tbody tr:even").addClass('gray');
                        $(fn_policies.list.domroot_nav+" #units_active_table tbody tr:odd").addClass('white');
                        $(fn_policies.list.domroot_nav+" #units_active_table tfoot .paginas_total").val(data.total);
                        $(fn_policies.list.domroot_nav+" #units_active_table tfoot .pagina_actual").val(data.pagina);
                        fn_policies.list.units_pagina_actual = data.pagina; 
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
        }
               
}    

 
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_policies" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
            <h2>INSURANCE POLICIES</h2>
            <img src="images/data-grid/policy_status.jpg" alt="policy_status.jpg" style="float:right;position: relative;top: -110px;margin-bottom: -100px;"> 
        </div>
        <table id="data_grid_policies" class="data_grid">
        <thead id="grid-head2">
            <tr id="grid-head1">
                <td style="width:50px!important;"><input class="flt_pid" type="text" placeholder="ID:"></td> 
                <td style="width:350px;"><input class="flt_pcompany" type="text" placeholder="Company:"></td>
                <td><input class="flt_policynumber" type="text" placeholder="Policy Numer:"></td>
                <td><input class="flt_pbroker" type="text" placeholder="Broker:"></td> 
                <td><input class="flt_policytype" type="text" placeholder="Policy Type:"></td> 
                <td><input class="flt_policystartdate" type="text" placeholder="MM/DD/YY"></td> 
                <td><input class="flt_policyexpdate" type="text" placeholder="MM/DD/YY"></td>  
                <td style='width:200px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_policies.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_policies.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <tr id="grid-head-tools">
                <td colspan="100%">
                    <ul>
                        <li><div class="btn-icon add btn-left" title="Upload drivers or units of a company"  onclick="fn_policies.upload_file_form();"><i class="fa fa-upload"></i></div></li>
                    </ul>
                </td>
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_policies.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('sNombreCompania',this.cellIndex);">Company Name</td> 
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('sNumeroPoliza',this.cellIndex);">Policy Number</td>
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('sName',this.cellIndex);">Broker</td>
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('sDescripcion',this.cellIndex);">Type</td>
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('dFechaInicio',this.cellIndex);">EFFECTIVE DATE </td> 
                <td class="etiqueta_grid"      onclick="fn_policies.ordenamiento('dFechaCaducidad',this.cellIndex);">Expiration Date</td> 
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
<!---- FORMULARIOS ------>
<div id="policies_edit_form" class="popup-form">
    <div class="p-header">
        <h2>EDIT OR ADD COMPANY POLICY</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('policies_edit_form');fn_policies.fillgrid();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div id="policy_information">
        <form>
            <fieldset>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <div class="field_item">
                    <input id="iConsecutivo"  name="iConsecutivo"  type="hidden">
                    <label>Company <span style="color:#ff0000;">*</span>:</label>  
                    <select tabindex="1" id="iConsecutivoCompania"  name="iConsecutivoCompania"><option value="">Select an option...</option></select>
                </div> 
                <div class="field_item">
                    <label>Number <span style="color:#ff0000;">*</span>:</label> 
                    <input class="txt-uppercase" tabindex="2" id="sNumeroPoliza" name="sNumeroPoliza" type="text" placeholder="" maxlength="30">
                </div>                
                <div class="field_item"> 
                    <label>Effective Date <span style="color:#ff0000;">*</span>: </label><input id="dFechaInicio" type="text" class="txt-uppercase fecha">
                </div>
                <div class="field_item"> 
                    <label>Expiration Date <span style="color:#ff0000;">*</span>: </label><input id="dFechaCaducidad" type="text" class="txt-uppercase fecha">
                </div>
                <div class="field_item"> 
                    <label>Type <span style="color:#ff0000;">*</span>:</label> 
                    <select tabindex="3" id="iTipoPoliza"  name="iTipoPoliza"><option value="">Select an option...</option></select>
                </div>
                <div class="field_item">
                    <label>Broker <span style="color:#ff0000;">*</span>:</label>  
                    <select tabindex="3" id="iConsecutivoBrokers"  name="iConsecutivoBrokers"><option value="">Select an option...</option></select> 
                </div>
                <div class="field_item">
                    <label>Insurance Company :</label>  
                    <select tabindex="3" id="iConsecutivoAseguranza"  name="iConsecutivoAseguranza"><option value="">Select an option...</option></select> 
                </div>
                <div class="policy_jacker_file" style="display:none;">
                    <legend>POLICY FILES</legend>
                    <div class="file_certificate files"> 
                        <label>Policy Jacker: <span style="color:#9e2e2e;">Please upload the policy jacker in PDF format.</span></label> 
                        <input  id="txtPolicyJacker" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                        <button id="btnPolicyJacker" type="button">Upload file</button>
                        <input  id="iConsecutivoArchivo" type="hidden">
                    </div>
                    <div class="file_certificate files"> 
                        <label>PFA: <span style="color:#9e2e2e;">Please upload the PFA in PDF format.</span></label> 
                        <input  id="txtPolicyPFA" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                        <button id="btnPolicyPFA" type="button">Upload file</button>
                        <input  id="iConsecutivoArchivoPFA" type="hidden">
                    </div>
                </div> 
                <br> 
                <button type="button" class="btn-1" onclick="fn_policies.save();">SAVE</button> 
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('policies_edit_form');fn_policies.fillgrid();" style="margin-right:10px;background:#e8051b;">CLOSE</button> 
            </fieldset>
        </form>
    </div>
    </div>
</div>
<!---- upload file form --->
<div id="file_edit_form" class="popup-form">
    <div class="p-header">
        <h2>UPLOAS DRIVER/UNITS FILE</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('file_edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <form>
            <fieldset>
                <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
                <div class="field_item">
                    <label>Company <span style="color:#ff0000;">*</span>:</label>  
                    <select tabindex="1" id="iConsecutivoCompania"  name="File_iConsecutivoCompania" onblur="fn_policies.get_company_policies(this.value);">
                        <option value="">Select an option...</option>
                    </select>
                </div>                
                <div class="field_item">
                    <label>Policies: <span style="color:#ff0000;">*</span>:</label>  
                    <div class="company_policies" style="padding:10px;">
                        
                    </div>
                    <br>
                </div>
                <div class="field_item">
                    <label>Type <span style="color:#ff0000;">*</span>:</label>  
                    <select tabindex="3" id="TypeFile"  name="File_TypeFile">
                        <option value="">Select an option...</option>
                        <option value="D">Drivers</option> 
                        <option value="U">Units</option> 
                    </select> 
                </div>
                <div class="field_item"> 
                    <label>File: <span style="color:#9e2e2e;">Please upload an TXT File with the content.</span></label> 
                    <input id="txtFile" type="text" readonly="readonly" value="" size="40" />
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
<!----- DRIVERS & UNITS --->
<!---- upload file form --->
<div id="driver_list_form" class="popup-form" style="width:90%!important;margin-left: -45%;">
    <div class="p-header">
        <h2>LIST OF DRIVERS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('driver_list_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div id="driver_tabs">
      <ul>
        <li><a href="#tabs-1" onclick="fn_policies.list.fill_drivers_actives();">Drivers actives in this policy</a></li>
        <li><a href="#tabs-2" onclick="fn_policies.list.fill_units_actives();">Units actives in this policy</a></li>
      </ul>
      <div id="tabs-1">
        <table id="drivers_active_table" class="popup-datagrid">
            <thead>
                    <tr id="grid-head1">
                        <td style="width:500px;"><input class="flt_dName" type="text" placeholder="Name:"></td> 
                        <td><input class="flt_dDob flt_fecha"  type="text" placeholder="MM-DD-YY"></td>
                        <td><input class="flt_dLicense" type="text" placeholder="License #:"></td> 
                        <td>
                            <select class="flt_dtype" type="text" onblur="fn_policies.list.drivers_filtraInformacion();">
                                <option value="">Select an option...</option>
                                <option value="1">Federal / B1</option> 
                                <option value="2">Commercial / CDL - A</option> 
                            </select>
                        </td> 
                        <td><input class="flt_dExpire flt_fecha" type="text" placeholder="MM-DD-YY"></td> 
                        <td style="width:80px;"><input class="flt_dYears num" type="text" placeholder="Years:"></td>  
                        <td style='width:120px;'>
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_policies.list.drivers_filtraInformacion();"><i class="fa fa-search"></i></div>
                        </td> 
                    </tr>
                    <tr id="grid-head2">
                        <td class="etiqueta_grid">Name</td>
                        <td class="etiqueta_grid">DOB</td>
                        <td class="etiqueta_grid">LICENSE NUMBER</td>
                        <td class="etiqueta_grid">LICENSE TYPE</td> 
                        <td class="etiqueta_grid">EXPIRE DATE</td> 
                        <td class="etiqueta_grid">EXPERIENCE YEARS</td>
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
                                <button class="pgn-inicio"    onclick="fn_policies.list.drivers_firstPage();" title="First page"><span></span></button>
                                <button class="pgn-anterior"  onclick="fn_policies.list.drivers_previousPage();" title="Previous"><span></span></button>
                                <button class="pgn-siguiente" onclick="fn_policies.list.drivers_nextPage();" title="Next"><span></span></button>
                                <button class="pgn-final"     onclick="fn_policies.list.drivers_lastPage();" title="Last Page"><span></span></button>
                            </div>
                        </td>
                    </tr>
                </tfoot>
        </table>
      </div>
      <div id="tabs-2">
        <table id="units_active_table" class="popup-datagrid">
            <thead>
                <tr id="grid-head1">
                        <td style="width:500px;"><input class="flt_uVIN" type="text" placeholder="Name:"></td> 
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
                        <td style='width:120px;'>
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_policies.list.units_filtraInformacion();"><i class="fa fa-search"></i></div>
                        </td> 
                    </tr>
                    <tr id="grid-head2">
                        <td class="etiqueta_grid">VIN</td>
                        <td class="etiqueta_grid">RADIO</td>
                        <td class="etiqueta_grid">YEAR</td>
                        <td class="etiqueta_grid">MAKE</td> 
                        <td class="etiqueta_grid">TYPE</td> 
                        <td class="etiqueta_grid">WEIGHT</td> 
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
                                <button class="pgn-inicio"    onclick="fn_policies.list.units_firstPage();" title="First page"><span></span></button>
                                <button class="pgn-anterior"  onclick="fn_policies.list.units_previousPage();" title="Previous"><span></span></button>
                                <button class="pgn-siguiente" onclick="fn_policies.list.units_nextPage();" title="Next"><span></span></button>
                                <button class="pgn-final"     onclick="fn_policies.list.units_lastPage();" title="Last Page"><span></span></button>
                            </div>
                        </td>
                    </tr>
                </tfoot>
        </table>
      </div>
    </div>
    <div>
    </div>
    </div>
</div>
<!--- DIALOGUES --->
<div id="dialog_delete_policy" title="SYSTEM ALERT" style="dipolicies_edit_formsplay:none;">
    <p>If you check the policy as canceled this will no longer be visible in the module. Are you sure?</p>
    <form id="elimina" method="post">
           <input type="hidden" name="id_policy" id="id_policy">
    </form>  
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>