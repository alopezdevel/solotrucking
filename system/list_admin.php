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
            var tipo_usuario   = <?php echo json_encode($_SESSION['acceso']);?> 
            validapantalla(usuario_actual);
            fn_endorsement.init();
            fn_endorsement.fillgrid();
            $.unblockUI();
            
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
            sort : "ASC",
            orden : "A.iConsecutivo",
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
               
            },
            fillgrid: function(){
                   $.ajax({             
                    type:"POST", 
                    url:"list_admin_server.php", 
                    data:{
                        accion:"get_datagrit",
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
                        
                    }
                }); 
            },
            edit : function (){
              $(fn_endorsement.data_grid + " tbody td .btn_units_list").bind("click",function(){
                    
                    var clave   = $(this).parent().parent().find("td:eq(0)").html();
                    var company = $(this).parent().parent().find("td:eq(1)").text();
                     
                    $('#frm_list_vehicles .popup-gridtit').empty().text('VEHICLES OF' + company ); 
                    fn_endorsement.list.id_company = clave;
                    //fn_endorsement.files.init();
                    fn_popups.resaltar_ventana('frm_list_vehicles'); 
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
                    if($(fn_endorsement.data_grid+" .flt_company").val() != ""){ fn_endorsement.filtro += "A.sNombreCompania|"+$(fn_endorsement.data_grid+" .flt_company").val()+","} 
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
                        action: 'list_admin_server.php',
                        onSubmit : function(file , ext){
                            if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext))  )){ 
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
                                        if($('#iConsecutivo_driver').val() == ''){var edit_mode = 'false';}else{var edit_mode = 'true'; }
                                        this.setData({
                                            'accion': 'save_file',
                                            'edit_mode' : edit_mode,
                                            'eArchivo' : $('#eArchivo_driver').val(),
                                            'sNombreArchivo':$('#sNombreArchivo_driver').val(),
                                            'iConsecutivo':$('#iConsecutivo_driver').val(),
                                            'Categoria' : fn_endorsement.files.tipo,
                                            'iConsecutivoEndoso' : fn_endorsement.files.id_endorsement,
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
                        action: 'list_admin_server.php',
                        onSubmit : function(file , ext){
                            if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext))  )){ 
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
                                        if($('#iConsecutivo_unit').val() == ''){var edit_mode = 'false';}else{var edit_mode = 'true'; }
                                        this.setData({
                                            'accion': 'save_file',
                                            'edit_mode' : edit_mode,
                                            'eArchivo' : $('#eArchivo_unit').val(),
                                            'sNombreArchivo':$('#sNombreArchivo_unit').val(),
                                            'iConsecutivo':$('#iConsecutivo_unit').val(),
                                            'Categoria' : fn_endorsement.files.tipo, 
                                            'iConsecutivoEndoso' : fn_endorsement.files.id_endorsement,
                                        });
                                        this.disable();
                                    }
                                }else{
                                   fn_solotrucking.mensaje('Error: Please Select a file category.'); 
                                   $('#eArchivo_unit').addClass('error');
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
                    url:"list_admin_server.php", 
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
                  $.post("list_admin_server.php",{accion:"delete_file", 'iConsecutivoFile': id, 'Categoria' : fn_endorsement.files.tipo },
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
    }    
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_endorsement" class="container">
        <div class="page-title">
            <h1>POLICIES</h1>
            <h2>LIST ADMINISTRATOR BY COMPANY (DRIVERS/VEHICLES)</h2>
        </div>
        <table id="data_grid_endorsement" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'><input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_company" type="text" placeholder="Company:"></td>
                <td></td> 
                <td></td> 
                <td></td> 
                <td><div class="btn-icon-2 btn-left" title="Search" onclick="fn_endorsement.filtraInformacion();"><i class="fa fa-search"></i></div></td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('A.sNombreCompania',this.cellIndex);">COMPANY</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('',this.cellIndex);">Drivers Total</td>
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('',this.cellIndex);">Vehicles Total</td> 
                <td class="etiqueta_grid"      onclick="fn_endorsement.ordenamiento('',this.cellIndex);">Endorsements Total</td> 
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
<div id="frm_list_vehicles" class="popup-form">
    <div class="p-header">
        <h2>LIST ADMINISTRATOR / HISTORY BY VEHICLES </h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('endorsements_edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <h3 class="popup-gridtit">VEHICLES OF</h3>
        <table id="datagrid_endosos_units" class="popup-datagrid">
            <thead>
                <tr class="grid-head1">
                        <td><input class="flt_filename" type="text" placeholder="Name:"></td>  
                        <td><input class="flt_filecategory" type="text" placeholder="Category:"></td> 
                        <td style='width:120px;'>
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_endorsement.files.filtraInformacion();"><i class="fa fa-search"></i></div>
                            <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_endorsement.files.add();"><i class="fa fa-plus"></i></div>
                        </td> 
                </tr>
                <tr class="grid-head2">
                    <td class="etiqueta_grid">ID</td>
                    <td class="etiqueta_grid">YEAR</td>
                    <td class="etiqueta_grid">MAKE</td> 
                    <td class="etiqueta_grid">VIN</td>
                    <td class="etiqueta_grid">VALUE</td>
                    <td class="etiqueta_grid">ACTION</td>
                    <td class="etiqueta_grid">DATE</td>
                    <td class="etiqueta_grid">END AL#</td>
                    <td class="etiqueta_grid">AL</td>
                    <td class="etiqueta_grid">END MTC#</td>
                    <td class="etiqueta_grid">CARGO</td>
                    <td class="etiqueta_grid">END PD#</td>
                    <td class="etiqueta_grid">PD</td>
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
    </div>
    </div>
</div>
<div id="endorsements_edit_form" class="popup-form">
    <div class="p-header">
        <h2>ENDORSEMENTS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('endorsements_edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <h3 class="popup-gridtit"></h3>
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
            <select tabindex="1" id="eArchivo_driver"  name="eArchivo_driver" onblur="fn_endorsement.files.validate_file(this.value);">
                <option value="">Select an option...</option>
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
            <select tabindex="1" id="eArchivo_unit"  name="eArchivo_unit" onblur="fn_endorsement.files.validate_file(this.value);">
                <option value="">Select an option...</option>
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
<!-- DIALOGUES -->
<div id="dialog_delete_file" title="SYSTEM ALERT" style="display:none;">
    <p>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
    <form><input type="hidden" name="id_file" id="id_file"></form>  
</div>  
<!-- FOOTER -->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>