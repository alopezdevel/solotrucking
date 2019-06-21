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
        var tipo_usuario   = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);  
        $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); });
        fn_endosos.init();
        //$.unblockUI();
        /*---- Dialogos ---*/
        $('#dialog_delete').dialog({
            modal: true,
            autoOpen: false,
            width : 550,
            height : 200,
            resizable : false,
            dialogClass: "without-close-button",
            buttons : {
                'CONFIRM' : function() {
                    var clave = $('#dialog_delete input[name=iConsecutivo]').val();
                    fn_endosos.borrar(clave);
                    $(this).dialog('close');
                },
                'CANCEL' : function(){$(this).dialog('close');}
            }
        });
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
var fn_endosos = {
        domroot:"#ct_endosos",
        data_grid: "#data_grid_endosos",
        filtro : "",
        pagina_actual : "",
        sort : "DESC",
        orden : "iConsecutivo",
        init : function(){
            fn_endosos.fillgrid();
            $('.num').keydown(fn_solotrucking.inputnumero); 
            $('.decimal').keydown(fn_solotrucking.inputdecimals);
            //Filtrado con la tecla enter
            $(fn_endosos.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_endosos.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_endosos.filtraInformacion();
                }
            });  
            //Cargar Lista de companias:
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_companies"},
                async : true,
                dataType : "json",
                success : function(data){
                    $("#frm_edit_new select[name=iConsecutivoCompania], #frm_edit_estatus select[name=iConsecutivoCompania]").empty().append(data.select);
                }
            });
            
            //DATEPICKERS
            var dates_rp = $("#frm_edit_new input[name=dFechaInicio], #frm_edit_new input[name=dFechaFin]" ).datepicker({
                changeMonth: true,
                dateFormat: 'mm/dd/yy',
                onSelect: function( selectedDate ) {
                    var option = this.id == "dFechaInicio" ? "minDate" : "maxDate",
                    instance = $( this ).data( "datepicker" );
                    date = $.datepicker.parseDate(
                    instance.settings.dateFormat ||
                    $.datepicker._defaults.dateFormat,
                    selectedDate, instance.settings );
                    dates_rp.not( this ).datepicker( "option", option, date );
                },
            }); 
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"endorsement_month_server.php", 
                data:{
                    accion               : "get_datagrid",
                    registros_por_pagina : "15", 
                    pagina_actual        : fn_endosos.pagina_actual, 
                    filtroInformacion    : fn_endosos.filtro,  
                    ordenInformacion     : fn_endosos.orden,
                    sortInformacion      : fn_endosos.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_endosos.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_endosos.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_endosos.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_endosos.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_endosos.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_endosos.pagina_actual = data.pagina; 
                    fn_endosos.edit();
                    fn_endosos.estatus_edit();
                    fn_endosos.delete_confirm();
                    fn_solotrucking.btn_tooltip();
                }
            }); 
        },
        add : function(){
           $('#frm_edit_new :text, #frm_edit_new select,#frm_edit_new input ').val('').removeClass('error');
           var fechas = fn_solotrucking.obtener_fechas();
           $("#frm_edit_new input[name=dFechaInicio]").val(fechas[1]); 
           $("#frm_edit_new input[name=dFechaFin]").val(fechas[2]);
           //Habilitar campos:
           $('#dFechaInicio, #dFechaFin').removeProp('readonly').removeClass('readonly');
           $('#frm_edit_new select[name=iConsecutivoCompania], #frm_edit_new select[name=iConsecutivoBroker],#frm_edit_new select[name=iTipoReporte], #frm_edit_new select[name=iConsecutivoPoliza]').removeProp('disabled').removeClass('readonly'); 
           $("#frm_edit_new input[name=dFechaInicio], #frm_edit_new input[name=dFechaFin]" ).datepicker( "option", "disabled", false );
           $("#frm_edit_new textarea[name=sMensajeEmail]").val("Please create new endorsement for the following insured."); 
           $('#frm_edit_new #data_grid_detalle,#frm_edit_new .btns_only_edit').hide();
           fn_endosos.get_policies_co("#frm_edit_new");
           fn_popups.resaltar_ventana('frm_edit_new');  
        },
        edit : function (){
            $(fn_endosos.data_grid + " tbody td .btn_edit").bind("click",function(){
                var clave    = $(this).parent().parent().find("td:eq(0)").prop('id');
                    clave    = clave.split('_');
                    clave    = clave[1];
                fn_endosos.get_data(clave);
          });  
        },
        get_data : function(clave){
           if(clave != ""){
                $.post("endorsement_month_server.php",{accion:"get_data", clave: clave, domroot : "frm_edit_new"},
                function(data){
                    if(data.error == '0'){
                        $('#frm_edit_new :text, #frm_edit_new select,#frm_edit_new input ').val('').removeClass('error');
                        //Deshabilitar campos:
                        //$('#dFechaInicio, #dFechaFin').addClass('readonly').prop('readonly','readonly');
                        $('#frm_edit_new select[name=iConsecutivoCompania], #frm_edit_new select[name=iConsecutivoBroker],#frm_edit_new select[name=iTipoReporte], #frm_edit_new select[name=iConsecutivoPoliza]').prop('disabled','disabled').addClass('readonly');
                        //$("#frm_edit_new input[name=dFechaInicio], #frm_edit_new input[name=dFechaFin]" ).datepicker( "option", "disabled", true );
                        $('#frm_edit_new #data_grid_detalle, #frm_edit_new .btns_only_edit').show();
                        eval(data.fields);
                        fn_endosos.get_policies_co("#frm_edit_new",data.idPoliza);  
                        fn_endosos.detalle.iConsecutivo = clave;
                        fn_endosos.detalle.fillgrid(); 
                        fn_popups.resaltar_ventana('frm_edit_new');
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json");      
           } 
        },
        save : function (ghost){
            
           if(!(ghost)){ghost = false;}
           
           //Validate Fields:
           var valid = true;
           var msj   = "";
           $("#frm_edit_new input.required-field, #frm_edit_new select.required-field").removeClass("error");
           $("#frm_edit_new input.required-field, #frm_edit_new select.required-field").each(function(){
              if($(this).val() == ""){valid = false; $(this).addClass('error');msj = "<li>You must capture the required fields.</li>";} 
           });
           
           if(valid){
             if($('#frm_edit_new #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
             struct_data_post.action  = "save_data";
             struct_data_post.domroot = "#data_general"; 
                $.post("endorsement_month_server.php",struct_data_post.parse(),
                function(data){
                    switch(data.error){
                     case '0':
                        if(!(ghost)){
                            fn_solotrucking.mensaje(data.msj);
                            fn_endosos.get_data(data.iConsecutivo);
                        }  
                     break;
                     case '1': fn_solotrucking.mensaje(data.msj); break;
                    }
                },"json");
           }
           else{fn_solotrucking.mensaje('<p>Please check the following::</p><ul>'+msj+'</ul>');} 
        },
        firstPage : function(){
            if($(fn_endosos.data_grid+" #pagina_actual").val() != "1"){
                fn_endosos.pagina_actual = "";
                fn_endosos.fillgrid();
            }
        },
        previousPage : function(){
            if($(fn_endosos.data_grid+" #pagina_actual").val() != "1"){
                fn_endosos.pagina_actual = (parseInt($(fn_endosos.data_grid+" #pagina_actual").val()) - 1) + "";
                fn_endosos.fillgrid();
            }
        },
        nextPage : function(){
            if($(fn_endosos.data_grid+" #pagina_actual").val() != $(fn_endosos.data_grid+" #paginas_total").val()){
                fn_endosos.pagina_actual = (parseInt($(fn_endosos.data_grid+" #pagina_actual").val()) + 1) + "";
                fn_endosos.fillgrid();
            }
        },
        lastPage : function(){
            if($(fn_endosos.data_grid+" #pagina_actual").val() != $(fn_endosos.data_grid+" #paginas_total").val()){
                fn_endosos.pagina_actual = $(fn_endosos.data_grid+" #paginas_total").val();
                fn_endosos.fillgrid();
            }
        }, 
        ordenamiento : function(campo,objeto){
            $(fn_endosos.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_endosos.orden){
                if(fn_endosos.sort == "ASC"){
                    fn_endosos.sort = "DESC";
                    $(fn_endosos.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_endosos.sort = "ASC";
                    $(fn_endosos.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_endosos.sort = "ASC";
                fn_endosos.orden = campo;
                $(fn_endosos.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
            }
            fn_endosos.fillgrid();

            return false;
        }, 
        filtraInformacion : function(){
            fn_endosos.pagina_actual = 0;
            fn_endosos.filtro = "";
            //if($(fn_endosos.data_grid+" .flt_id").val() != ""){ fn_endosos.filtro += "A.iConsecutivo|"+$(fn_endosos.data_grid+" .flt_id").val()+","}
            if($(fn_endosos.data_grid+" .flt_name").val() != ""){ fn_endosos.filtro += "B.sNombreCompania|"+$(fn_endosos.data_grid+" .flt_name").val()+","} 
            if($(fn_endosos.data_grid+" .flt_tipo").val() != ""){ fn_endosos.filtro += "iTipoReporte|"+$(fn_endosos.data_grid+" .flt_tipo").val()+","} 
            if($(fn_endosos.data_grid+" .flt_datefrom").val() != ""){ fn_endosos.filtro += "dFechaInicio|"+$(fn_endosos.data_grid+" .flt_datefrom").val()+","} 
            if($(fn_endosos.data_grid+" .flt_dateto").val() != ""){ fn_endosos.filtro += "dFechaFin|"+$(fn_endosos.data_grid+" .flt_dateto").val()+","} 
            if($(fn_endosos.data_grid+" .flt_broker").val() != ""){ fn_endosos.filtro += "C.sName|"+$(fn_endosos.data_grid+" .flt_broker").val()+","} 
            if($(fn_endosos.data_grid+" .flt_email").val() != ""){ fn_endosos.filtro += "A.sEmail|"+$(fn_endosos.data_grid+" .flt_email").val()+","} 
            if($(fn_endosos.data_grid+" .flt_date").val() != ""){ fn_endosos.filtro += "A.dFechaAplicacion|"+$(fn_endosos.data_grid+" .flt_date").val()+","}    
            fn_endosos.fillgrid();
        },  
        delete_confirm : function(){
          $(fn_endosos.data_grid + " tbody .btn_delete").bind("click",function(){
               var clave    = $(this).parent().parent().find("td:eq(0)").prop('id');
                   clave    = clave.split('_');
                   clave    = clave[1];
               var name  = $(this).parent().parent().find("td:eq(0)").text();
               $('#dialog_delete input[name=iConsecutivo]').val(clave);
               $('#dialog_delete .name').empty().html(name);
               $('#dialog_delete').dialog( 'open' );
               return false;
           });  
        },
        borrar : function(clave){
          $.post("endorsement_month_server.php",{accion:"delete_data", 'clave': clave},
           function(data){
                fn_solotrucking.mensaje(data.msj);
                fn_endosos.filtraInformacion();
           },"json");  
        },
        get_broker_data : function(){
            var broker = $("#frm_edit_new select[name=iConsecutivoBroker]").val();
            if(broker != ""){
               $.ajax({             
                    type:"POST", 
                    url:"endorsement_month_server.php", 
                    data:{accion:"get_broker_data","iConsecutivoBroker":broker},
                    async : true,
                    dataType : "text",
                    success : function(data){
                        $("#frm_edit_new input[name=sEmail").val(data);
                    }
               });
               fn_endosos.get_policies_co("#frm_edit_new");  
            }
        },
        get_policies_co : function(form,poliza){
           var broker   = $(form+" select[name=iConsecutivoBroker]").val();
           var compania = $(form+" select[name=iConsecutivoCompania]").val();
           $.ajax({             
                type:"POST", 
                url:"endorsement_month_server.php", 
                data:{accion:"get_policies_data","iConsecutivoBroker":broker,"iConsecutivoCompania":compania},
                async : false,
                dataType : "text",
                success : function(data){
                    $(form+" select[name=iConsecutivoPoliza").empty().append(data);
                    
                    if(poliza != ""){$(form+" select[name=iConsecutivoPoliza]").val(poliza);}
                }
           });  
             
        },
        //Detalle:
        detalle : {
            iConsecutivo : "",
            iTipo : "",
            data_grid : "#data_grid_detalle .popup-datagrid",
            pagina_actual : "",
            filtro : "",
            orden  : "A.dFechaAplicacion",
            sort   : "DESC",
            fillgrid: function(){
                if(fn_endosos.detalle.iConsecutivo != ""){
                    fn_endosos.detalle.iTipo = $("#frm_edit_new select[name=iTipoReporte]").val();
                    $.ajax({             
                        type:"POST", 
                        url:"endorsement_month_server.php", 
                        data:{
                            accion               : "detalle_get_datagrid",
                            iConsecutivo         : fn_endosos.detalle.iConsecutivo,
                            iTipoReporte         : fn_endosos.detalle.iTipo,
                            registros_por_pagina : "15", 
                            pagina_actual        : fn_endosos.detalle.pagina_actual, 
                            filtroInformacion    : fn_endosos.detalle.filtro,  
                            ordenInformacion     : fn_endosos.detalle.orden,
                            sortInformacion      : fn_endosos.detalle.sort,
                            iEditable            : 'true',
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $(fn_endosos.detalle.data_grid+" tbody").empty().append(data.tabla);
                            $(fn_endosos.detalle.data_grid+" tbody tr:even").addClass('gray');
                            $(fn_endosos.detalle.data_grid+" tbody tr:odd").addClass('white');
                            $(fn_endosos.detalle.data_grid+" tfoot .total_endosos").empty().text(data.total_endosos);
                            //$(fn_endosos.detalle.data_grid+" tfoot .paginas_total").val(data.total);
                            //$(fn_endosos.detalle.data_grid+" tfoot .pagina_actual").val(data.pagina);
                            fn_endosos.detalle.pagina_actual = data.pagina;
                            //$("#frm_edit_new select[name=iConsecutivoPoliza").val(data.iConsecutivoPoliza); 
                            fn_endosos.detalle.borrar();
                            fn_solotrucking.btn_tooltip();
                        }
                    });     
                }
            },
            firstPage : function(){
                if($(fn_endosos.detalle.data_grid+" .pagina_actual").val() != "1"){
                    fn_endosos.detalle.pagina_actual = "";
                    fn_endosos.detalle.fillgrid();
                }
            },
            previousPage : function(){
                if($(fn_endosos.detalle.data_grid+" .pagina_actual").val() != "1"){
                    fn_endosos.detalle.pagina_actual = (parseInt($(fn_endosos.detalle.data_grid+" .pagina_actual").val()) - 1) + "";
                    fn_endosos.detalle.fillgrid();
                }
            },
            nextPage : function(){
                if($(fn_endosos.detalle.data_grid+" .pagina_actual").val() != $(fn_endosos.detalle.data_grid+" .paginas_total").val()){
                    fn_endosos.detalle.pagina_actual = (parseInt($(fn_endosos.detalle.data_grid+" .pagina_actual").val()) + 1) + "";
                    fn_endosos.detalle.fillgrid();
                }
            },
            lastPage : function(){
                if($(fn_endosos.detalle.data_grid+" .pagina_actual").val() != $(fn_endosos.detalle.data_grid+" .paginas_total").val()){
                    fn_endosos.detalle.pagina_actual = $(fn_endosos.detalle.data_grid+" .paginas_total").val();
                    fn_endosos.detalle.fillgrid();
                }
            },
            borrar : function(){
              $(fn_endosos.detalle.data_grid + " tbody .btn_delete_detalle").bind("click",function(){
                   var clave    = $(this).parent().parent().find("td:eq(0)").prop('id');
                       clave    = clave.split('_');
                       clave    = clave[1];
                   $.post("endorsement_month_server.php",{accion:"detalle_delete_data", 'clave': clave,'claveReporte':fn_endosos.detalle.iConsecutivo},
                   function(data){
                        fn_solotrucking.mensaje(data.msj);
                        fn_endosos.detalle.fillgrid();
                   },"json"); 
               });  
            }, 
            actualiza : function(){
                $.ajax({             
                    type:"POST", 
                    url:"endorsement_month_server.php", 
                    data:{
                        accion               : "actualiza_detalle",
                        iConsecutivo         : fn_endosos.detalle.iConsecutivo,
                        iTipoReporte         : fn_endosos.detalle.iTipo,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        if(data.estatus == 'OK'){fn_endosos.detalle.fillgrid();}
                        else{fn_solotrucking.mensaje(data.mensaje);}
                    }
                });    
            } 
        },
        download_excel : function(iConsecutivoReporte){
           if(iConsecutivoReporte != ""){
                window.open('endorsement_month_xlsx.php?idReport='+iConsecutivoReporte);
           }else{fn_solotrucking.mensaje("Please select before a valid dates."); } 
        },
        //Email:
        email : { 
            send : function(){
              var iConsecutivo  = $('#data_general input[name=iConsecutivo]').val();
              fn_endosos.save(true);
              $.ajax({             
                type:"POST", 
                url:"endorsement_month_server.php", 
                data:{'accion' : 'send_email','iConsecutivoReporte' : iConsecutivo},
                async : true,
                dataType : "json",
                success : function(data){                               
                    if(data.error == '0'){
                          fn_solotrucking.mensaje(data.msj);
                          fn_endosos.fillgrid();
                    }
                    
                }
              });    
            },
            send_confirm : function(){
               $('#dialog_send_email').dialog('open');   
            }, 
        }, 
        //Estatus:
        estatus_edit : function(){
            $(fn_endosos.data_grid + " tbody td .btn_change_status").bind("click",function(){
                var clave    = $(this).parent().parent().find("td:eq(0)").prop('id');
                    clave    = clave.split('_');
                    clave    = clave[1];
                $.post("endorsement_month_server.php",{accion:"estatus_get_data", clave: clave, domroot : "frm_edit_estatus"},
                function(data){
                    if(data.error == '0'){
                        $('#frm_edit_estatus :text, #frm_edit_estatus select,#frm_edit_estatus input ').val('').removeClass('error');
                        //Deshabilitar campos:
                        $('#frm_edit_estatus input[name=dFechaInicio], #frm_edit_estatus input[name=dFechaFin],#frm_edit_estatus input[name=iRatePercent]').addClass('readonly').prop('readonly','readonly');
                        $('#frm_edit_estatus select[name=iConsecutivoCompania], #frm_edit_estatus select[name=iConsecutivoBroker],#frm_edit_estatus select[name=iTipoReporte], #frm_edit_estatus select[name=iConsecutivoPoliza]').prop('disabled','disabled').addClass('readonly');
                        eval(data.fields);
                        fn_endosos.get_policies_co("#frm_edit_estatus",data.poliza);
                        fn_endosos.detalle_enviados.iConsecutivo = clave;
                        fn_endosos.detalle_enviados.fillgrid(); 
                        fn_popups.resaltar_ventana('frm_edit_estatus');
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
                },"json");   
          }); 
        },
        estatus_save : function(){
           //Validate Fields:
           var valid = true;
           var msj   = "";
           $("#data_general_estatus select.required-field").removeClass("error");
           $("#data_general_estatus select.required-field").each(function(){
              if($(this).val() == ""){valid = false; $(this).addClass('error');msj = "<li>You must capture the required fields.</li>";} 
           });
           
           if(valid){
             if($('#data_general_estatus input[name=iConsecutivo]').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}  
 
                $.post("endorsement_month_server.php",{
                    'accion'       : "estatus_save_data",
                    'iConsecutivo' : $('#data_general_estatus input[name=iConsecutivo]').val(),
                    'eStatus'      : $("#data_general_estatus select[name=eStatus]").val(),
                    'sComentarios' : $("#data_general_estatus textarea[name=sComentarios]").val(),
                    'iConsecutivoPoliza' : $("#frm_edit_estatus select[name=iConsecutivoPoliza]").val(),
                },
                function(data){
                    fn_solotrucking.mensaje(data.msj);
                },"json");
           }
           else{fn_solotrucking.mensaje('<p>Please check the following::</p><ul>'+msj+'</ul>');} 
        },
        //detalle_enviados:
        detalle_enviados : {
            iConsecutivo : "",
            iTipo        : "",
            data_grid    : "#data_grid_detalle_enviados .popup-datagrid",
            pagina_actual: "",
            filtro       : "",
            orden        : "A.dFechaAplicacion",
            sort         : "DESC",
            fillgrid: function(){
                if(fn_endosos.detalle_enviados.iConsecutivo != ""){
                    fn_endosos.detalle_enviados.iTipo = $("#frm_edit_estatus select[name=iTipoReporte]").val();
                    $.ajax({             
                        type:"POST", 
                        url:"endorsement_month_server.php", 
                        data:{
                            accion               : "detalle_get_datagrid",
                            iConsecutivo         : fn_endosos.detalle_enviados.iConsecutivo,
                            iTipoReporte         : fn_endosos.detalle_enviados.iTipo,
                            registros_por_pagina : "15", 
                            pagina_actual        : fn_endosos.detalle_enviados.pagina_actual, 
                            filtroInformacion    : fn_endosos.detalle_enviados.filtro,  
                            ordenInformacion     : fn_endosos.detalle_enviados.orden,
                            sortInformacion      : fn_endosos.detalle_enviados.sort,
                            iEditable            : 'false',
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){                               
                            $(fn_endosos.detalle_enviados.data_grid+" tbody").empty().append(data.tabla);
                            $(fn_endosos.detalle_enviados.data_grid+" tbody tr:even").addClass('gray');
                            $(fn_endosos.detalle_enviados.data_grid+" tbody tr:odd").addClass('white');
                            $(fn_endosos.detalle_enviados.data_grid+" tfoot .paginas_total").val(data.total);
                            $(fn_endosos.detalle_enviados.data_grid+" tfoot .pagina_actual").val(data.pagina);
                            fn_endosos.detalle_enviados.pagina_actual = data.pagina;
                            $("#frm_edit_estatus select[name=iConsecutivoPoliza").val(data.iConsecutivoPoliza); 
                            fn_solotrucking.btn_tooltip();
                        }
                    });     
                }
            },
            firstPage : function(){
                if($(fn_endosos.detalle_enviados.data_grid+" .pagina_actual").val() != "1"){
                    fn_endosos.detalle_enviados.pagina_actual = "";
                    fn_endosos.detalle_enviados.fillgrid();
                }
            },
            previousPage : function(){
                if($(fn_endosos.detalle_enviados.data_grid+" .pagina_actual").val() != "1"){
                    fn_endosos.detalle_enviados.pagina_actual = (parseInt($(fn_endosos.detalle_enviados.data_grid+" .pagina_actual").val()) - 1) + "";
                    fn_endosos.detalle_enviados.fillgrid();
                }
            },
            nextPage : function(){
                if($(fn_endosos.detalle_enviados.data_grid+" .pagina_actual").val() != $(fn_endosos.detalle_enviados.data_grid+" .paginas_total").val()){
                    fn_endosos.detalle_enviados.pagina_actual = (parseInt($(fn_endosos.detalle_enviados.data_grid+" .pagina_actual").val()) + 1) + "";
                    fn_endosos.detalle_enviados.fillgrid();
                }
            },
            lastPage : function(){
                if($(fn_endosos.detalle_enviados.data_grid+" .pagina_actual").val() != $(fn_endosos.detalle_enviados.data_grid+" .paginas_total").val()){
                    fn_endosos.detalle_enviados.pagina_actual = $(fn_endosos.detalle_enviados.data_grid+" .paginas_total").val();
                    fn_endosos.detalle_enviados.fillgrid();
                }
            },
        },
        
}     
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_endosos" class="container">
        <div class="page-title">
            <h1>ENDORSEMENTS / ENDOSOS</h1>
            <h2>Monthly / MENSUAL</h2>
        </div>
        <table id="data_grid_endosos" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style="min-width:400px;"><input class="flt_name" type="text" placeholder="Company Name:"></td>
                <td>
                <select class="flt_tipo">
                    <option value="">All</option>
                    <option value="">Drivers</option>
                    <option value="">Vehicles</option>
                </select>
                </td>
                <td style="width: 85px;"><input class="flt_datefrom" type="text" placeholder="MM/DD/YYYY"></td> 
                <td style="width: 85px;"><input class="flt_dateto" type="text" placeholder="MM/DD/YYYY"></td> 
                <td style="min-width: 300px;"><input class="flt_broker" type="text" placeholder="Broker Name:"></td> 
                <td><input class="flt_email" type="text" placeholder="E-mails:"></td> 
                <td style="width: 85px;"><input class="flt_date" type="text" placeholder="MM/DD/YYYY"></td> 
                <td style='width: 90px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_endosos.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_endosos.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <!--<tr id="grid-head-tools">
                <td colspan="100%">
                    <ul>
                        <li><div class="btn-icon report btn-left" title="Generate a Report"><i class="fa fa-folder-open"></i></div></li>  
                    </ul>
                </td>
            </tr>-->
            <tr id="grid-head2">
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('sNombreCompania',this.cellIndex);">Company</td>
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('iTipoReporte',this.cellIndex);">Type</td>
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('dFechaInicio',this.cellIndex);">Date from</td>
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('dFechaFin',this.cellIndex);">Date To</td>
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('sNombreBroker',this.cellIndex);">Broker</td>
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('sEmail',this.cellIndex);">E-mails</td>
                <td class="etiqueta_grid"      onclick="fn_endosos.ordenamiento('dFechaAplicacion',this.cellIndex);">Sent Date</td>
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
                        <button id="pgn-inicio"    onclick="fn_endosos.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_endosos.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_endosos.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_endosos.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>
        
    </div>
</div>
<!-- FORMULARIOS -->
<div id="frm_edit_new" class="popup-form" style="width: 80%;">
    <div class="p-header">
        <h2>EDIT OR ADD AN Endorsement</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('frm_edit_new');fn_endosos.fillgrid();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <form>
            <fieldset style="padding-bottom: 10px;">
                <legend style="margin-bottom:0px!important;">General Information</legend>
                <table id="data_general" style="width:100%;">
                    <tr><td colspan="100%"><p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p></td></tr>
                    <tr>
                        <td style="width:50%!important;">
                            <input id="iConsecutivo" name="iConsecutivo" type="hidden">
                            <div class="field_item">
                                <label>Company <span style="color:#ff0000;">*</span>:</label>  
                                <select tabindex="1" id="iConsecutivoCompania"  name="iConsecutivoCompania" class="required-field" style="height: 25px!important;width: 99%!important;" onchange="fn_endosos.get_policies_co();"><option value="">Select an option...</option></select>
                            </div>
                        </td>
                        <td style="width:50%!important;">
                            <div class="field_item">
                                <label>Report Type <span style="color:#ff0000;">*</span>:</label>  
                                <select tabindex="2" id="iTipoReporte"  name="iTipoReporte" style="height: 25px!important;width: 99%!important;" class="required-field">
                                    <option value="">Select an opction...</option>
                                    <option value="1">Vehicles (Unit/Trailer)</option>
                                    <option value="2">Drivers</option>
                                </select>
                            </div> 
                        </td>
                    </tr>
                    <tr>
                        <td style="width:50%!important;">
                            <div class="field_item">
                                <label>Broker <span style="color:#ff0000;">*</span>:</label>  
                                <select tabindex="3" id="iConsecutivoBroker"  name="iConsecutivoBroker" class="required-field" style="height: 25px!important;width: 99%!important;" onchange="fn_endosos.get_broker_data();">
                                    <option value="">Select an option...</option>
                                    <option value="5">CRC INSURANCE SERVICES INC</option>
                                </select>
                            </div> 
                        </td>
                        <td style="width:50%!important;">
                            <div class="field_item">
                                <label>Policy <span style="color:#ff0000;">*</span>:</label>  
                                <select tabindex="4" id="iConsecutivoPoliza"  name="iConsecutivoPoliza" class="required-field" style="height: 25px!important;width: 99%!important;"><option value="">Select an option...</option></select>
                            </div> 
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label title="If you need to write more than one email, please separate them by comma symbol (,).">Broker E-mail <span style="color:#ff0000;">*</span>:</label>  
                                <input tabindex="5" id="sEmail"  name="sEmail" class="required-field" style="width: 99%!important;" title="If you need to write more than one email, please separate them by comma symbol (,)."/>
                            </div> 
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>Message to send: <span style="color:#044c8b;font-size:10px;">(This message will be displayed before the endorsement information.)</span></label> 
                            <textarea tabindex="6" id="sMensajeEmail" name="sMensajeEmail" maxlenght="1000" style="resize: none;" title="Max. 1000 characters." style="height: 50px!important;"></textarea>
                        </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:50%!important;vertical-align: top;">
                            <div class="field_item"> 
                                <label style="padding: 9px 0px;"><span style="color:#ff0000;">*</span> Application date from </label><br>
                                <div>
                                    <input tabindex="7" id="dFechaInicio" name="dFechaInicio" type="text"  placeholder="MM/DD/YY" style="width: 40%;margin: 5px 0px!important;" class="required-field">
                                    <label class="check-label" style="position: relative;top: 0px;color: #000!important;padding: 0;width: 10%;display: inline-block;margin: 0;text-align: center;">To</label>
                                    <input tabindex="8" id="dFechaFin" name="dFechaFin"   type="text"  placeholder="MM/DD/YY" style="width: 40%;margin: 5px 0px!important;" class="required-field">
                                </div>
                            </div> 
                        </td>
                        <td style="width:50%!important;vertical-align: top;">
                        <div class="field_item">
                            <label>Rate %:</label> 
                            <input tabindex="9" id="iRatePercent" name="iRatePercent" value="" type="text" class="decimal"/>
                        </div>
                        </td>
                    </tr>
                </table>
                <table style="width: 100%;" cellpadding="0" cellspacing="0" style="margin-bottom: 5px;">
                <tr id="data_grid_detalle">
                    <td colspan="2">
                    <h4 class="popup-gridtit">Endorsement Description</h4>
                    <table class="popup-datagrid" style="margin-bottom: 10px;" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr id="grid-head2">
                                <td class="etiqueta_grid txt-c" style="width: 80px;">App Date</td>
                                <td class="etiqueta_grid" style="width: 100px;">Policy No.</td>
                                <td class="etiqueta_grid">Descripcion</td>
                                <td class="etiqueta_grid" style="width: 90px;text-align: center;">
                                    <div class="btn-icon edit btn-left" title="Update Endorsement List" onclick="fn_endosos.detalle.actualiza();" style="width: auto!important;"><i class="fa fa-refresh"></i><span style="margin-left:5px;font-size: 10px!important;">Update list</span></div>
                                </td>
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="100%" style="padding-top: 3px;">
                                    <label> Endorsements Total: </label><span class="total_endosos"></span>
                                </td>
                            </tr>
                        </tfoot>
                        <!--<tfoot>
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
                                        <button class="pgn-inicio"    onclick="fn_endosos.detalle.firstPage();" title="First page" type="button"><span></span></button>
                                        <button class="pgn-anterior"  onclick="fn_endosos.detalle.previousPage();" title="Previous" type="button"><span></span></button>
                                        <button class="pgn-siguiente" onclick="fn_endosos.detalle.nextPage();" title="Next" type="button"><span></span></button>
                                        <button class="pgn-final"     onclick="fn_endosos.detalle.lastPage();" title="Last Page" type="button"><span></span></button>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>-->
                    </table>
                    </td>
                </tr> 
                </table> 
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('frm_edit_new');fn_endosos.fillgrid();" style="margin-right:10px;background:#e8051b;" title="Close">CLOSE</button>
                <button type="button" class="btn-1 btns_only_edit" onclick="fn_endosos.save(true);fn_solotrucking.mensaje('The data has been sent!, please check the account customerservice@solo-trucking.com to receive the response from the brokers.');window.open('endorsement_month_xlsx.php?idReport='+$('#frm_edit_new input[name=iConsecutivo]').val()+'&mail=1');" style="margin-right:10px;background: #87c540;width: 140px;" title="Send report to the broker">SEND E-MAIL</button>
                <!--<button type="button" class="btn-1 btns_only_edit" onclick="fn_endosos.email.preview();" style="margin-right:10px;background:#5ec2d4;width: 140px;">PREVIEW E-MAIL</button>--> 
                <button type="button" class="btn-1 btns_only_edit" onclick="fn_endosos.download_excel($('#frm_edit_new input[name=iConsecutivo]').val());" style="margin-right:10px;background: #87c540;width: 180px;" title="Download report without send">DOWNLOAD EXCEL FILE</button> 
                <button type="button" class="btn-1" onclick="fn_endosos.save();" style="margin-right:10px;">SAVE</button> 
            </fieldset>
        </form>
    </div>
    </div>
</div>
<div id="frm_edit_estatus" class="popup-form" style="width: 80%;">
    <div class="p-header">
        <h2>Endorsement - EDIT Estatus</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('frm_edit_estatus');fn_endosos.fillgrid();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <form>
            <fieldset style="padding-bottom: 10px;">
                <legend style="margin-bottom:0px!important;">General Information</legend>
                <table id="data_general_estatus" style="width:100%;">
                    <tr>
                        <td style="width:50%!important;">
                            <input name="iConsecutivo" type="hidden">
                            <div class="field_item">
                                <label>Company:</label>  
                                <select name="iConsecutivoCompania" style="height: 25px!important;width: 99%!important;"><option value="">Select an option...</option></select>
                            </div>
                        </td>
                        <td style="width:50%!important;">
                            <div class="field_item">
                                <label>Report Type:</label>  
                                <select name="iTipoReporte" style="height: 25px!important;width: 99%!important;">
                                    <option value="">Select an opction...</option>
                                    <option value="1">Vehicles (Unit/Trailer)</option>
                                    <option value="2">Drivers</option>
                                </select>
                            </div> 
                        </td>
                    </tr>
                    <tr>
                        <td style="width:50%!important;">
                            <div class="field_item">
                                <label>Broker:</label>  
                                <select name="iConsecutivoBroker" style="height: 25px!important;width: 99%!important;">
                                    <option value="">Select an option...</option>
                                    <option value="5">CRC INSURANCE SERVICES INC</option>
                                </select>
                            </div> 
                        </td>
                        <td style="width:50%!important;">
                            <div class="field_item">
                                <label>Policy:</label>  
                                <select name="iConsecutivoPoliza" style="height: 25px!important;width: 99%!important;"><option value="">Select an option...</option></select>
                            </div> 
                        </td>
                    </tr>
                    <tr>
                        <td style="width:50%!important;vertical-align: top;">
                            <div class="field_item"> 
                                <label style="padding: 9px 0px;">Application date from </label><br>
                                <div>
                                    <input name="dFechaInicio" type="text"  placeholder="MM/DD/YY" style="width: 40%;margin: 5px 0px!important;">
                                    <label class="check-label" style="position: relative;top: 0px;color: #000!important;padding: 0;width: 10%;display: inline-block;margin: 0;text-align: center;">To</label>
                                    <input name="dFechaFin"   type="text"  placeholder="MM/DD/YY" style="width: 40%;margin: 5px 0px!important;">
                                </div>
                            </div> 
                        </td>
                        <td style="width:50%!important;vertical-align: top;">
                        <div class="field_item">
                            <label>Rate %:</label> 
                            <input name="iRatePercent" value="" type="text" class="decimal"/>
                        </div>
                        </td>
                    </tr>
                    <tr><td colspan="100%"><p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p></td></tr>
                    <tr>
                        <td colspan="100%">
                            <div class="field_item">
                                <label>Status<span style="color:#ff0000;">*</span>: <span style="color:#044c8b;font-size:10px;">(This estatus will be displayed to the customer in our system.)</span></label>
                                <Select tabindex="1" id="eStatus" name="eStatus" style="width:99%!important;height: 25px!important;" class="required-field">
                                    <option value="">Select an option...</option>
                                    <option value="SB">Sent to Brokers - Your endorsement has been sent to the brokers.</option>
                                    <option value="P">In Process - Your endorsement is being in process by the brokers.</option>
                                    <option value="A">Approved - Your endorsement has been approved successfully.</option>
                                    <option value="D">Canceled - Your endorsement has been canceled.</option>
                                </select>
                            </div> 
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%">
                        <div class="field_item">
                            <label>General Comments: <span style="color:#044c8b;font-size:10px;">(This field will be displayed only to Solo-Trucking users.)</span></label> 
                            <textarea tabindex="2" id="sComentarios" name="sComentarios" maxlenght="1000" style="resize: none;" title="Max. 1000 characters." style="height: 30px!important;"></textarea>
                        </div>
                        </td>
                    </tr>
                </table>
                <table style="width: 100%;" cellpadding="0" cellspacing="0" style="margin-bottom: 5px;">
                <tr id="data_grid_detalle_enviados">
                    <td colspan="2">
                    <h4 class="popup-gridtit">Endorsement Description</h4>
                    <table class="popup-datagrid" style="margin-bottom: 10px;" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr id="grid-head2">
                                <td class="etiqueta_grid txt-c" style="width: 80px;">App Date</td>
                                <td class="etiqueta_grid" style="width: 100px;">Policy No.</td>
                                <td class="etiqueta_grid">Descripcion</td>
                                <td class="etiqueta_grid" style="width: 80px;text-align: center;"></td>
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
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
                                        <button class="pgn-inicio"    onclick="fn_endosos.detalle.firstPage();" title="First page" type="button"><span></span></button>
                                        <button class="pgn-anterior"  onclick="fn_endosos.detalle.previousPage();" title="Previous" type="button"><span></span></button>
                                        <button class="pgn-siguiente" onclick="fn_endosos.detalle.nextPage();" title="Next" type="button"><span></span></button>
                                        <button class="pgn-final"     onclick="fn_endosos.detalle.lastPage();" title="Last Page" type="button"><span></span></button>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    </td>
                </tr>
                </table> 
                <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('frm_edit_estatus');fn_endosos.fillgrid();" style="margin-right:10px;background:#e8051b;" title="Close">CLOSE</button>
                <!--<button type="button" class="btn-1 btns_only_edit" onclick="fn_endosos.download_excel($('#frm_edit_estatus input[name=iConsecutivo]').val());" style="margin-right:10px;background: #87c540;width: 180px;" title="Download Excel file without send">DOWNLOAD EXCEL FILE</button>-->
                <button type="button" class="btn-1" onclick="fn_endosos.estatus_save();" style="margin-right:10px;" title="Save status">SAVE</button> 
            </fieldset>
        </form>
    </div>
    </div>
</div>
<!-- DIALOGUES -->
<div id="dialog_delete" title="Delete" style="display:none;">
  <p><span class="ui-icon ui-icon-alert" ></span> Are you sure you want to delete the report for company? <br><span class="name" style="color:#0a87c1;font-weight:600;padding-left:20px;"></span></p>
  <form><div><input type="hidden" name="iConsecutivo" /></div></form>  
</div>
<!-- FOOTER -->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>