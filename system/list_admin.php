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
            fn_admin.init();
            fn_admin.fillgrid();
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
                        fn_admin.files.delete_file(clave);             
                    },
                     'NO' : function(){
                        $(this).dialog('close');
                    }
                }
            });  
               
    }  
    function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}  }                   
    var fn_admin = {
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
                $(fn_admin.data_grid + ' #grid-head1 input').keyup(function(event){
                    if (event.keyCode == '13') {
                        event.preventDefault();
                        fn_admin.filtraInformacion();
                    }
                    if(event.keyCode == '27'){
                       event.preventDefault();
                       $(this).val(''); 
                       fn_admin.filtraInformacion();
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
                        pagina_actual : fn_admin.pagina_actual, 
                        filtroInformacion : fn_admin.filtro,  
                        ordenInformacion : fn_admin.orden,
                        sortInformacion : fn_admin.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_admin.data_grid+" tbody").empty().append(data.tabla);
                        $(fn_admin.data_grid+" tbody tr:even").addClass('gray');
                        $(fn_admin.data_grid+" tbody tr:odd").addClass('white');
                        $(fn_admin.data_grid + " tfoot #paginas_total").val(data.total);
                        $(fn_admin.data_grid + " tfoot #pagina_actual").val(data.pagina);
                        fn_admin.pagina_actual = data.pagina;
                        fn_admin.edit();
                        
                    }
                }); 
            },
            edit : function (){
              $(fn_admin.data_grid + " tbody td .btn_units_list").bind("click",function(){
                    
                    var clave   = $(this).parent().parent().find("td:eq(0)").html();
                    var company = $(this).parent().parent().find("td:eq(1)").text();
                     
                    $('#frm_list_vehicles .popup-gridtit').empty().text('VEHICLES OF ' + company ); 
                    fn_admin.list.id_company = clave;
                    fn_admin.list.units_init();
                    fn_popups.resaltar_ventana('frm_list_vehicles'); 
              });  
              $(fn_admin.data_grid + " tbody td .btn_drivers_list").bind("click",function(){
                    
                    var clave   = $(this).parent().parent().find("td:eq(0)").html();
                    var company = $(this).parent().parent().find("td:eq(1)").text();
                     
                    $('#frm_list_vehicles .popup-gridtit').empty().text('VEHICLES OF ' + company ); 
                    fn_admin.list.id_company = clave;
                    fn_admin.list.drivers_init();
                    fn_popups.resaltar_ventana('frm_list_drivers'); 
              });  
            },  
            firstPage : function(){
                if($(fn_admin.data_grid+" #pagina_actual").val() != "1"){
                    fn_admin.pagina_actual = "";
                    fn_admin.fillgrid();
                }
            },
            previousPage : function(){
                    if($(fn_admin.data_grid+" #pagina_actual").val() != "1"){
                        fn_admin.pagina_actual = (parseInt($(fn_admin.data_grid+" #pagina_actual").val()) - 1) + "";
                        fn_admin.fillgrid();
                    }
                },
            nextPage : function(){
                    if($(fn_admin.data_grid+" #pagina_actual").val() != $(fn_admin.data_grid+" #paginas_total").val()){
                        fn_admin.pagina_actual = (parseInt($(fn_admin.data_grid+" #pagina_actual").val()) + 1) + "";
                        fn_admin.fillgrid();
                    }
                },
            lastPage : function(){
                    if($(fn_admin.data_grid+" #pagina_actual").val() != $(fn_admin.data_grid+" #paginas_total").val()){
                        fn_admin.pagina_actual = $(fn_admin.data_grid+" #paginas_total").val();
                        fn_admin.fillgrid();
                    }
                }, 
            ordenamiento : function(campo,objeto){
                    $(fn_admin.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                    if(campo == fn_admin.orden){
                        if(fn_admin.sort == "ASC"){
                            fn_admin.sort = "DESC";
                            $(fn_admin.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                        }else{
                            fn_admin.sort = "ASC";
                            $(fn_admin.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                        }
                    }else{
                        fn_admin.sort = "ASC";
                        fn_admin.orden = campo;
                        $(fn_admin.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                    fn_admin.fillgrid();

                    return false;
                }, 
            filtraInformacion : function(){
                    fn_admin.pagina_actual = 0;
                    fn_admin.filtro = "";
                    if($(fn_admin.data_grid+" .flt_id").val() != ""){ fn_admin.filtro += "A.iConsecutivo|"+$(fn_admin.data_grid+" .flt_id").val()+","}
                    if($(fn_admin.data_grid+" .flt_company").val() != ""){ fn_admin.filtro += "A.sNombreCompania|"+$(fn_admin.data_grid+" .flt_company").val()+","} 
                    fn_admin.fillgrid();
           },
            files : {
                id_endorsement : "",
                tipo : "",
                filtro : "",
                pagina_actual : "",
                sort : "ASC",
                orden : "iConsecutivo",
                init : function(){
                    fn_admin.files.fillgrid();
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
                                            'Categoria' : fn_admin.files.tipo,
                                            'iConsecutivoEndoso' : fn_admin.files.id_endorsement,
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
                                    fn_admin.files.fillgrid();
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
                                            'Categoria' : fn_admin.files.tipo, 
                                            'iConsecutivoEndoso' : fn_admin.files.id_endorsement,
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
                                    fn_admin.files.fillgrid();
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
                        iConsecutivo :  fn_admin.files.id_endorsement,
                        tipo : fn_admin.files.tipo,
                        registros_por_pagina : "15", 
                        pagina_actual : fn_admin.files.pagina_actual, 
                        filtroInformacion : fn_admin.files.filtro,  
                        ordenInformacion : fn_admin.files.orden,
                        sortInformacion : fn_admin.files.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $("#data_files_grid tbody").empty().append(data.tabla);
                        $("#data_files_grid tbody tr:even").addClass('gray');
                        $("#data_files_grid tbody tr:odd").addClass('white');
                        $("#data_files_grid tfoot .paginas_total").val(data.total);
                        $("#data_files_grid tfoot .pagina_actual").val(data.pagina);
                        fn_admin.files.pagina_actual = data.pagina;
                        fn_admin.files.delete_confirm();
                        
                    }
                   }); 
                },
                filtraInformacion : function(){
                    fn_admin.files.pagina_actual = 0;
                    fn_admin.files.filtro = "";
                    if($("#data_files_grid .flt_filename").val() != ""){ fn_admin.files.filtro += "sNombreArchivo|"+$("#data_files_grid .flt_filename").val()+","}
                    if($("#data_files_grid .flt_filecategory").val() != ""){ fn_admin.files.filtro += "eArchivo|"+$("#data_files_grid .flt_filecategory").val()+","} 
                    fn_admin.files.fillgrid();
                },
                firstPage : function(){
                    if($("#data_files_grid .pagina_actual").val() != "1"){
                        fn_admin.files.pagina_actual = "";
                        fn_admin.files.fillgrid();
                    }
                },
                previousPage : function(){
                    if($("#data_files_grid .pagina_actual").val() != "1"){
                        fn_admin.files.pagina_actual = (parseInt($("#data_files_grid .pagina_actual").val()) - 1) + "";
                        fn_admin.files.fillgrid();
                    }
                },
                nextPage : function(){
                    if($("#data_files_grid .pagina_actual").val() != $("#data_files_grid .paginas_total").val()){
                        fn_admin.files.pagina_actual = (parseInt($("#data_files_grid .pagina_actual").val()) + 1) + "";
                        fn_admin.files.fillgrid();
                    }
                },
                lastPage : function(){
                    if($("#data_files_grid .pagina_actual").val() != $("#data_files_grid .paginas_total").val()){
                        fn_admin.files.pagina_actual = $("#data_files_grid .paginas_total").val();
                        fn_admin.files.fillgrid();
                    }
                },
                add : function(){
                      $('#add_file_form .p-header h2').empty().text('ENDORSEMENTS FILES - ADD NEW FILE TO ENDORSEMENT ' + fn_admin.files.id_endorsement);
                      $('#drivers_files, #units_files ').hide(); 
                      if(fn_admin.files.tipo == '2'){
                          $('#add_file_form #drivers_files').show();
                          $('#drivers_files input, #drivers_files select').val('');
                          fn_admin.files.validate_file($('#eArchivo_driver').val());
                      }else if(fn_admin.files.tipo == '1'){
                          $('#add_file_form #units_files').show();
                          $('#units_files input, #units_files select').val('');
                          fn_admin.files.validate_file($('#eArchivo_unit').val());
                      } 
                      $('#add_file_form').show();
                },
                validate_file : function(filetype){
                    if(filetype =='OTHERS'){
                       if(fn_admin.files.tipo == '2'){  
                            $('#drivers_files .file_name_field').show(); 
                       }else if(fn_admin.files.tipo == '1'){$('#units_files .file_name_field').show();  }
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
                  $.post("list_admin_server.php",{accion:"delete_file", 'iConsecutivoFile': id, 'Categoria' : fn_admin.files.tipo },
                   function(data){
                        fn_solotrucking.mensaje(data.msj);
                        fn_admin.files.fillgrid();
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
                    if(fn_admin.files.tipo == '2'){
                          $('#add_file_form #drivers_files').show();
                          $('#drivers_files input, #drivers_files select').val('');
                          fn_admin.files.validate_file($('#eArchivo_driver').val());
                    }else if(fn_admin.files.tipo == '1'){
                          $('#add_file_form #units_files').show();
                          $('#units_files input, #units_files select').val('');
                          fn_admin.files.validate_file($('#eArchivo_unit').val());
                    } 
                    $('#add_file_form').show();
              });  
            }, */    
           },
            list : {
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
                        data:{accion:"get_company_policies",company : fn_admin.list.id_company},
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
                //DRIVERS
                drivers_filtraInformacion : function(){
                    fn_admin.list.drivers_pagina_actual = 0;
                    fn_admin.list.filtro = "";
                    if($("#datagrid_endosos_drivers .flt_name").val() != "")  {fn_admin.list.filtro += "sNombre|"          +$("#datagrid_endosos_drivers .flt_name").val()+","}
                    if($("#datagrid_endosos_drivers .flt_dob").val() != "")    {fn_admin.list.filtro += "dFechaNacimiento|"+$("#datagrid_endosos_drivers .flt_dob").val()+","} 
                    if($("#datagrid_endosos_drivers .flt_license").val() != ""){fn_admin.list.filtro += "iNumLicencia|"    +$("#datagrid_endosos_drivers .flt_license").val()+","} 
                    if($("#datagrid_endosos_drivers .flt_expire").val() != "") {fn_admin.list.filtro += "eTipoLicencia|"   +$("#datagrid_endosos_drivers .flt_expire").val() +","}  
                    fn_admin.list.drivers_fillgrid();
                },
                drivers_init : function(){
                    //limpiar filtros:
                    $("#datagrid_endosos_drivers .grid-head1 input").val('');
                    $("#datagrid_endosos_drivers tbody").empty().append('<div style="width:100%;margin:5px auto;text-align:center;"><img src="images/ajax-loader.gif" border="0" width="16" height="16" alt="ajax-loader.gif (673 bytes)"></div>');
                    fn_admin.list.drivers_filtraInformacion();    
                },    
                drivers_fillgrid : function(){
                     $.ajax({             
                        type:"POST", 
                        url:"list_admin_server.php", 
                        data:{
                            "accion"               : "get_drivers_active",
                            "registros_por_pagina" : "40", 
                            "iConsecutivoPoliza"   : fn_admin.list.id_policy,
                            "iConsecutivoCompania" : fn_admin.list.id_company, 
                            "pagina_actual"        : fn_admin.list.drivers_pagina_actual, 
                            "filtroInformacion"    : fn_admin.list.filtro,  
                            "ordenInformacion"     : fn_admin.list.orden_driver,
                            "sortInformacion"      : fn_admin.list.sort,
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $("#datagrid_endosos_drivers tbody").empty().append(data.tabla);
                            $("#datagrid_endosos_drivers > tbody > tr:even").addClass('gray');
                            $("#datagrid_endosos_drivers > tbody > tr:odd").addClass('white');
                            $("#datagrid_endosos_drivers tfoot .paginas_total").val(data.total);
                            $("#datagrid_endosos_drivers tfoot .pagina_actual").val(data.pagina);
                            fn_admin.list.drivers_pagina_actual = data.pagina; 
                            fn_admin.list.drivers_edit();
                        }
                    }); 
                },
                drivers_firstPage : function(){
                    if($("#datagrid_endosos_drivers .pagina_actual").val() != "1"){
                        fn_admin.list.drivers_pagina_actual = "";
                        fn_admin.list.drivers_fillgrid();
                    }
                },
                drivers_previousPage : function(){
                    if($("#datagrid_endosos_drivers .pagina_actual").val() != "1"){
                        fn_admin.list.drivers_pagina_actual = (parseInt($("#datagrid_endosos_drivers .pagina_actual").val()) - 1) + "";
                        fn_admin.list.drivers_fillgrid();
                    }
                },
                drivers_nextPage : function(){
                    if($("#datagrid_endosos_drivers .pagina_actual").val() != $("#datagrid_endosos_drivers .paginas_total").val()){
                        fn_admin.list.drivers_pagina_actual = (parseInt($("#datagrid_endosos_drivers .pagina_actual").val()) + 1) + "";
                        fn_admin.list.drivers_fillgrid();
                    }
                },
                drivers_lastPage : function(){
                    if($("#datagrid_endosos_drivers .pagina_actual").val() != $("#datagrid_endosos_drivers .paginas_total").val()){
                        fn_admin.list.drivers_pagina_actual = $("#datagrid_endosos_drivers .paginas_total").val();
                        fn_admin.list.drivers_fillgrid();
                    }
                },
                drivers_add : function(){
                    $('#drivers_edit_form :text ').val(''); 
                    $('#drivers_edit_form #iConsecutivoCompania').val(fn_admin.list.id_company);
                    fn_admin.list.cargar_polizas('#drivers_edit_form');
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
                                fn_admin.list.drivers_fillgrid(); 
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
                        fn_admin.list.cargar_polizas('#drivers_edit_form');
                        $.ajax({             
                        type:"POST", 
                        url:"funciones_policies.php", 
                        data:{
                            accion:"get_driver", 
                            clave: clave, 
                            company : fn_admin.list.id_company, 
                            domroot : "drivers_edit_form"
                        },
                        async : false,
                        dataType : "json",
                        success : function(data){                               
                            if(data.error == '0'){
                               $('#drivers_edit_form input:text, #drivers_edit_form select').val('').removeClass('error'); 
                               //$(fn_admin.form + ' #sNumeroPoliza ,' + fn_admin.form + ' #iConsecutivoCompania').attr('readonly','readonly').addClass('readonly');
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
                units_init : function(){
                    //limpiar filtros:
                    $("#datagrid_endosos_units .grid-head1 input").val('');
                    $("#datagrid_endosos_units tbody").empty().append('<div style="width:100%;margin:5px auto;text-align:center;"><img src="images/ajax-loader.gif" border="0" width="16" height="16" alt="ajax-loader.gif (673 bytes)"></div>');
                    fn_admin.list.units_filtraInformacion();
                },
                units_fillgrid : function(){
                     $.ajax({             
                        type:"POST", 
                        url:"list_admin_server.php", 
                        data:{
                            "accion"               : "get_units_active",
                            "registros_por_pagina" : "40", 
                            "iConsecutivoPoliza"   : fn_admin.list.id_policy,
                            "iConsecutivoCompania" : fn_admin.list.id_company,
                            "pagina_actual"        : fn_admin.list.units_pagina_actual, 
                            "filtroInformacion"    : fn_admin.list.filtro,  
                            "ordenInformacion"     : fn_admin.list.orden_unit,
                            "sortInformacion"      : fn_admin.list.sort,
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $("#datagrid_endosos_units tbody").empty().append(data.tabla);
                            $("#datagrid_endosos_units > tbody > tr:even").addClass('gray');
                            $("#datagrid_endosos_units > tbody > tr:odd").addClass('white');
                            $("#datagrid_endosos_units tfoot .paginas_total").val(data.total);
                            $("#datagrid_endosos_units tfoot .pagina_actual").val(data.pagina);
                            fn_admin.list.units_pagina_actual = data.pagina; 
                            fn_admin.list.unit_edit(); 
                        }
                    }); 
                },
                units_filtraInformacion : function(){
                    fn_admin.list.units_pagina_actual = 0;
                    fn_admin.list.filtro = "";
                    if($("#datagrid_endosos_units .flt_year").val() != ""){fn_admin.list.filtro += "iYear|" +$("#datagrid_endosos_units .flt_year").val()+","}
                    if($("#datagrid_endosos_units .flt_make").val() != ""){fn_admin.list.filtro += "sAlias|"+$("#datagrid_endosos_units .flt_make").val()+","} 
                    if($("#datagrid_endosos_units .flt_vin").val() != ""){ fn_admin.list.filtro += "sVIN|"  +$("#datagrid_endosos_units .flt_vin").val()+","} 
                    if($("#datagrid_endosos_units .flt_val").val() != ""){ fn_admin.list.filtro += "iTotalPremiumPD|"+$("#datagrid_endosos_units .flt_val").val()+","} 
                    
                    fn_admin.list.units_fillgrid();
                },
                units_firstPage : function(){
                    if($("#datagrid_endosos_units .pagina_actual").val() != "1"){
                        fn_admin.list.units_pagina_actual = "";
                        fn_admin.list.units_fillgrid();
                    }
                },
                units_previousPage : function(){
                    if($("#datagrid_endosos_units .pagina_actual").val() != "1"){
                        fn_admin.list.units_pagina_actual = (parseInt($("#datagrid_endosos_units .pagina_actual").val()) - 1) + "";
                        fn_admin.list.units_fillgrid();
                    }
                },
                units_nextPage : function(){
                    if($("#datagrid_endosos_units .pagina_actual").val() != $("#datagrid_endosos_units .paginas_total").val()){
                        fn_admin.list.units_pagina_actual = (parseInt($("#datagrid_endosos_units .pagina_actual").val()) + 1) + "";
                        fn_admin.list.units_fillgrid();
                    }
                },
                units_lastPage : function(){
                    if($("#datagrid_endosos_units .pagina_actual").val() != $("#datagrid_endosos_units .paginas_total").val()){
                        fn_admin.list.units_pagina_actual = $("#datagrid_endosos_units .paginas_total").val();
                        fn_admin.list.units_fillgrid();
                    }
                },
                unit_add : function(){
                    $('#unit_edit_form :text,#unit_edit_form select').val(''); 
                    $('#unit_edit_form #iConsecutivoCompania').val(fn_admin.list.id_company);
                    fn_admin.list.cargar_polizas('#unit_edit_form');
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
                                fn_admin.list.drivers_fillgrid(); 
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
                        fn_admin.list.cargar_polizas('#unit_edit_form');
                        $.ajax({             
                        type:"POST", 
                        url:"funciones_policies.php", 
                        data:{
                            accion:"get_unit", 
                            clave: clave, 
                            company : fn_admin.list.id_company, 
                            domroot : "unit_edit_form"
                        },
                        async : false,
                        dataType : "json",
                        success : function(data){                               
                            if(data.error == '0'){
                               eval(data.fields); 
                               $('#units_active_table').hide();
                               $('#unit_edit_form').show();
                               fn_admin.list.units_fillgrid();  
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
                    fn_admin.list.get_policies(company);
                    
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
                <td><div class="btn-icon-2 btn-left" title="Search" onclick="fn_admin.filtraInformacion();"><i class="fa fa-search"></i></div></td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid"      onclick="fn_admin.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_admin.ordenamiento('A.sNombreCompania',this.cellIndex);">COMPANY</td>
                <td class="etiqueta_grid"      onclick="fn_admin.ordenamiento('',this.cellIndex);">Drivers Total</td>
                <td class="etiqueta_grid"      onclick="fn_admin.ordenamiento('',this.cellIndex);">Vehicles Total</td> 
                <td class="etiqueta_grid"      onclick="fn_admin.ordenamiento('',this.cellIndex);">Endorsements Total</td> 
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
                        <button id="pgn-inicio"    onclick="fn_admin.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_admin.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_admin.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_admin.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>    
    </div>
</div>
<!-- FORMULARIOS -->
<!-- vehicles-->
<div id="frm_list_vehicles" class="popup-form" style="width:95%;">
    <div class="p-header">
        <h2>LIST ADMINISTRATOR / HISTORY BY VEHICLES </h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('frm_list_vehicles');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <h3 class="popup-gridtit">VEHICLES OF</h3>
        <table id="datagrid_endosos_units" class="popup-datagrid">
            <thead>
                <tr class="grid-head1">
                        <td style="width: 50px;"><input class="flt_year" type="text" placeholder="Year:"></td>  
                        <td style="width: 80px;"><input class="flt_make" type="text" placeholder="Make:"></td>
                        <td><input class="flt_vin" type="text"  placeholder="VIN:"></td>
                        <td style="width: 100px;"><input class="flt_val" type="text"  placeholder="Value:"></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td style='width:120px;'>
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_admin.list.units_filtraInformacion();"><i class="fa fa-search"></i></div>
                        </td> 
                </tr>
                <tr class="grid-head2">
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
                    <td></td>
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
                            <button class="pgn-inicio"    onclick="fn_admin.list.units_firstPage();" title="First page"><span></span></button>
                            <button class="pgn-anterior"  onclick="fn_admin.list.units_previousPage();" title="Previous"><span></span></button>
                            <button class="pgn-siguiente" onclick="fn_admin.list.units_nextPage();" title="Next"><span></span></button>
                            <button class="pgn-final"     onclick="fn_admin.list.units_lastPage();" title="Last Page"><span></span></button>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
        <br>
    </div>
    </div>
</div>
<!-- drivers -->
<div id="frm_list_drivers" class="popup-form" style="width:95%;">
    <div class="p-header">
        <h2>LIST ADMINISTRATOR / HISTORY BY DRIVERS </h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('frm_list_drivers');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <h3 class="popup-gridtit">DRIVERS OF</h3>
        <table id="datagrid_endosos_drivers" class="popup-datagrid">
            <thead>
                <tr class="grid-head1">
                        <td style="width: 300px;"><input class="flt_name" type="text" placeholder="Name:"></td>  
                        <td style="width: 80px;"><input class="flt_dob" type="text" placeholder="Date of Birth:"></td>
                        <td><input class="flt_license" type="text"  placeholder="License No:"></td>
                        <td style="width: 100px;"><input class="flt_expire" type="text"  placeholder="Expire Date:"></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td style='width:120px;'>
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_admin.list.drivers_filtraInformacion();"><i class="fa fa-search"></i></div>
                        </td> 
                </tr>
                <tr class="grid-head2">
                    <td class="etiqueta_grid">NAME</td>
                    <td class="etiqueta_grid">DOB</td> 
                    <td class="etiqueta_grid">LICENSE NUMBER</td>
                    <td class="etiqueta_grid">EXPIRE DATE</td>
                    <td class="etiqueta_grid">ACTION</td>
                    <td class="etiqueta_grid">DATE</td>
                    <td class="etiqueta_grid">END AL#</td>
                    <td class="etiqueta_grid">AL</td>
                    <td class="etiqueta_grid">END MTC#</td>
                    <td class="etiqueta_grid">CARGO</td>
                    <td class="etiqueta_grid">END PD#</td>
                    <td class="etiqueta_grid">PD</td>
                    <td></td>
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
                            <button class="pgn-inicio"    onclick="fn_admin.list.drivers_firstPage();" title="First page"><span></span></button>
                            <button class="pgn-anterior"  onclick="fn_admin.list.drivers_previousPage();" title="Previous"><span></span></button>
                            <button class="pgn-siguiente" onclick="fn_admin.list.drivers_nextPage();" title="Next"><span></span></button>
                            <button class="pgn-final"     onclick="fn_admin.list.drivers_lastPage();" title="Last Page"><span></span></button>
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
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_admin.files.filtraInformacion();"><i class="fa fa-search"></i></div>
                            <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_admin.files.add();"><i class="fa fa-plus"></i></div>
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
                            <button class="pgn-inicio"    onclick="fn_admin.files.firstPage();" title="First page"><span></span></button>
                            <button class="pgn-anterior"  onclick="fn_admin.files.previousPage();" title="Previous"><span></span></button>
                            <button class="pgn-siguiente" onclick="fn_admin.files.nextPage();" title="Next"><span></span></button>
                            <button class="pgn-final"     onclick="fn_admin.files.lastPage();" title="Last Page"><span></span></button>
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
        <div class="btn-close" title="Close Window" onclick="$('#add_file_form').hide();fn_admin.files.fillgrid();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p>
    <form>  
    <fieldset id="drivers_files" style="display:none;">
        <div class="field_item">
            <input id="iConsecutivo_driver"  name="iConsecutivo"  type="hidden">
            <label>Category <span style="color:#ff0000;">*</span>:</label>  
            <select tabindex="1" id="eArchivo_driver"  name="eArchivo_driver" onblur="fn_admin.files.validate_file(this.value);">
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
        <button type="button" class="btn-1" onclick="$('#add_file_form').hide();fn_admin.files.fillgrid();" style="margin-right:10px;background:#e8051b;">CLOSE</button>   
    </fieldset>
    <fieldset id="units_files" style="display:none;">
        <div class="field_item">
            <input id="iConsecutivo_unit"  name="iConsecutivo_unit"  type="hidden">
            <label>Category <span style="color:#ff0000;">*</span>:</label>  
            <select tabindex="1" id="eArchivo_unit"  name="eArchivo_unit" onblur="fn_admin.files.validate_file(this.value);">
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
            <button type="button" class="btn-1" onclick="$('#add_file_form').hide();fn_admin.files.fillgrid();" style="margin-right:10px;background:#e8051b;">CLOSE</button>   
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