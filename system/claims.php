<?php 
session_start();    
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
        var tipo_usuario   = <?php echo json_encode($_SESSION['acceso']);?> 
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
                            url:"funciones_claims.php", 
                            data:{accion:"definir_compania",'iConsecutivoCompania':clave},
                            async : true,
                            dataType : "json",
                            success : function(data){
                                switch(data.error){
                                    case '0':
                                        $("#ct_claims h2").empty().append('APPLICATIONS TO THE COMPANY: ' + name); 
                                        fn_claims.init();
                                        fn_claims.fillgrid();
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
        }
        else{
            fn_claims.init();
            fn_claims.fillgrid();
        }
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}  }                   
var fn_claims = {
        domroot:"#ct_claims",
        data_grid: "#data_grid",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "iConsecutivo",
        init : function(){
            /*---- plugins y de mas ...*/
            $('.num').keydown(fn_solotrucking.inputnumero); 
            $('.decimals').keydown(fn_solotrucking.inputdecimals);
            $('.hora').mask('00:00');
            
            //INICIALIZA DATEPICKER PARA CAMPOS FECHA
            $(".fecha").datepicker({
                showOn: 'button',
                buttonImage: 'images/layout.png',
                dateFormat : 'mm/dd/yy',
                buttonImageOnly: true
            });
            $(".fecha,.flt_fecha").mask("99/99/9999"); 
            
            //Filtrado con la tecla enter
            $(fn_claims.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_claims.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_claims.filtraInformacion();
                }
            }); 
            
            //GET POLICIES:
            $.ajax({             
                type:"POST", 
                url:"funciones_claims.php", 
                data:{accion:"get_company_policies"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    if(data.error == '0'){
                        $("#edit_form .company_policies").empty().append(data.checkboxes); 
                        $("#info_policies table tbody").empty().append(data.policies_information);  
                    }
                }
            }); 
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_claims_types"},
                async : true,
                dataType : "json",
                success : function(data){$("#iConsecutivoTipoClaim").empty().append(data.select);}
            });  
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_claims_incident"},
                async : true,
                dataType : "json",
                success : function(data){$("#iConsecutivoIncidente").empty().append(data.select);}
            }); 
            $.ajax({             
                type:"POST", 
                url:"catalogos_generales.php", 
                data:{accion:"get_country", country : 'USA'},
                async : true,
                dataType : "json",
                success : function(data){$("#sEstado").empty().append(data.tabla);}
            });  
            /*----------- CARGANDO DRIVERS Y UNITS ----------*/
            $.ajax({
                type: "POST",
                url : "funciones_claims.php",
                data: {
                    "accion" : "get_drivers"
                },
                async : true,
                dataType : "text",
                success : function(data){
                    var datos = eval(data); 
                    $("#sDriver").autocomplete();
                    $("#sDriver").autocomplete({source:datos});
                }
            });
            $.ajax({
                type: "POST",
                url : "funciones_claims.php",
                data: {
                    "accion" : "get_units"
                },
                async : true,
                dataType : "text",
                success : function(data){
                    var datos = eval(data); 
                    $("#sUnitTrailer").autocomplete();
                    $("#sUnitTrailer").autocomplete({source:datos});
                }
            });
            
            
            // UPLOAD FILES SCRIPT
            new AjaxUpload('#btnFile',{
                    action: 'funciones_claims.php',
                    onSubmit : function(file , ext){   
                        if (!(ext && (/^(pdf)$/i.test(ext) || /^(jpg)$/i.test(ext) || /^(png)$/i.test(ext) || /^(docx)$/i.test(ext) || /^(xls)$/i.test(ext) || /^(xlsx)$/i.test(ext)))){
                            var mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: Invalid file format, please upload JPG or PNG.</p>';
                            fn_solotrucking.mensaje(mensaje);
                            return false;
                        }else{
                           if($('#edit_form #iConsecutivo').val() != ''){
                              this.setData({'accion':'upload_files','iConsecutivoClaim':$('#edit_form #iConsecutivo').val(),});
                              this.disable();  
                           }else{
                               fn_solotrucking.mensaje('To upload the pictures of incident, please save first the general data of claim making click in save button.'); 
                               return false;
                           }
                        }
                    },
                    onComplete : function(file,response){  
                        var respuesta = JSON.parse(response);
                        switch(respuesta.error){
                            case '0':
                                this.enable();
                                fn_solotrucking.mensaje(respuesta.mensaje);
                                fn_claims.files.iConsecutivoClaim = $('#edit_form #iConsecutivo').val();
                                fn_claims.files.fillgrid(); 
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
                url:"funciones_claims.php", 
                data:{
                    accion:"get_data_grid",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_claims.pagina_actual, 
                    filtroInformacion : fn_claims.filtro,  
                    ordenInformacion : fn_claims.orden,
                    sortInformacion : fn_claims.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_claims.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_claims.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_claims.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_claims.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_claims.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_claims.pagina_actual = data.pagina;
                    fn_claims.edit();
                    fn_claims.send();
                    fn_claims.unsent();
                }
            }); 
        },
        firstPage : function(){
            if($(fn_claims.data_grid+" #pagina_actual").val() != "1"){
                fn_claims.pagina_actual = "";
                fn_claims.fillgrid();
            }
        },
        previousPage : function(){
                if($(fn_claims.data_grid+" #pagina_actual").val() != "1"){
                    fn_claims.pagina_actual = (parseInt($(fn_claims.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_claims.fillgrid();
                }
        },
        nextPage : function(){
                if($(fn_claims.data_grid+" #pagina_actual").val() != $(fn_claims.data_grid+" #paginas_total").val()){
                    fn_claims.pagina_actual = (parseInt($(fn_claims.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_claims.fillgrid();
                }
        },
        lastPage : function(){
                if($(fn_claims.data_grid+" #pagina_actual").val() != $(fn_claims.data_grid+" #paginas_total").val()){
                    fn_claims.pagina_actual = $(fn_claims.data_grid+" #paginas_total").val();
                    fn_claims.fillgrid();
                }
        }, 
        ordenamiento : function(campo,objeto){
                $(fn_claims.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_claims.orden){
                    if(fn_claims.sort == "ASC"){
                        fn_claims.sort = "DESC";
                        $(fn_claims.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_claims.sort = "ASC";
                        $(fn_claims.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_claims.sort = "ASC";
                    fn_claims.orden = campo;
                    $(fn_claims.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_claims.fillgrid();

                return false;
        }, 
        filtraInformacion : function(){
                fn_claims.pagina_actual = 0;
                fn_claims.filtro = "";
                if($(fn_claims.data_grid+" .flt_id").val() != ""){ fn_claims.filtro += "iConsecutivo|"+$(fn_claims.data_grid+" .flt_id").val()+","}
                if($(fn_claims.data_grid+" .flt_type").val() != ""){ fn_claims.filtro += "eCategoria|"+$(fn_claims.data_grid+" .flt_type").val()+","}
                if($(fn_claims.data_grid+" .flt_dateIncident").val() != ""){ fn_claims.filtro += "dFechaIncidente|"+$(fn_claims.data_grid+" .flt_dateIncident").val()+","} 
                if($(fn_claims.data_grid+" .flt_hourIncident").val() != ""){ fn_claims.filtro += "dHoraIncidente|"+$(fn_claims.data_grid+" .flt_hourIncident").val()+","}  
                if($(fn_claims.data_grid+" .flt_cityIncident").val() != ""){ fn_claims.filtro += "sCiudad|"+$(fn_claims.data_grid+" .flt_cityIncident").val()+","} 
                if($(fn_claims.data_grid+" .flt_stateIncident").val() != ""){ fn_claims.filtro += "sEstado|"+$(fn_claims.data_grid+" .flt_stateIncident").val()+","} 
                if($(fn_claims.data_grid+" .flt_statusclaim").val() != ""){ fn_claims.filtro += "eStatus|"+$(fn_claims.data_grid+" .flt_statusclaim").val()+","}
                if($(fn_claims.data_grid+" .flt_dateAplication").val() != ""){ fn_claims.filtro += "dFechaAplicacion|"+$(fn_claims.data_grid+" .flt_dateAplication").val()+","}    
            
                fn_claims.fillgrid();
       },
        add : function(){
          $('#general_information input:text, #general_information select, #general_information textarea,#general_information input:hidden').val('').removeClass('error');
          $('#edit_form input[type=checkbox]').prop("checked",false); 
          $('#edit_form .mensaje_valido').empty().append('The fields containing an (<span style="color:#ff0000;">*</span>) are required.');
          $('#edit_form .p-header h2').empty().append('CLAIMS - NEW APPLICATION');
          $("#edit_form .policy_apply select").val("NOAPPLY").prop("disabled","disabled").addClass("readonly");
          $("#edit_form #files_datagrid").hide();
         
          fn_solotrucking.get_date(".fecha"); 
          fn_claims.revisar_tipos_polizas();
          fn_popups.resaltar_ventana('edit_form'); 
        },
        save : function(){
            
           var valid      = true;
           todosloscampos = $('#edit_form :text, #edit_form select');
           todosloscampos.removeClass("error");

           //validando campos fecha:
           $("#edit_form .fecha" ).each(function( index ){
                 valid = valid && fn_solotrucking.checkRegexp($(this), /^(0[1-9]|1[012])[- \/.](0[1-9]|[12][0-9]|3[01])[- \/.](19|20)\d\d$/);
                 return valid; 
                  if(!valid){$(this).addClass('error');}  
           });
           if(!valid){fn_solotrucking.mensaje("The date format is not valid, please check it..");return false;}
           
           //Validando campo de hora:
           valid = valid && fn_solotrucking.checkLength($('#edit_form #dHoraIncidente'),'Hour',1,6);
           
           //Validando que por lo menos sea un driver o una unidad...
           if($('#edit_form #sDriver').val() == '' && $('#edit_form #sUnitTrailer').val() == ''){
               valid = false; $('#edit_form #sUnitTrailer, #edit_form #sDriver').addClass('error');
           }
           if(!valid){fn_solotrucking.mensaje("Please select at least one driver or one unit from your list.");} 
           
           //Validando mensaje:
           valid = valid && fn_solotrucking.checkLength($('#edit_form #sDescripcionSuceso'),'Message',5,1000);
           if(!valid){fn_solotrucking.mensaje("Please write what happened.");} 
           
           if($('#edit_form #sEstado').val() == ''){valid = false; $(this).addClass('error');}
           if(!valid){fn_solotrucking.mensaje("Please select the state where the incident occurred.");} 
           if($('#edit_form #sCiudad').val() == ''){valid = false; $(this).addClass('error');}
           if(!valid){fn_solotrucking.mensaje("Please select the city where the incident occurred.");} 
           
           //Revisando polizas en las que aplica:
           var polizas = "";
           $("#edit_form .company_policies input[type=checkbox]").each(function(){
               if($(this).is(':checked')){
                  if(polizas != ""){polizas +="|"+$(this).val();}else{polizas = $(this).val();} 
               }
           });
           
           if(polizas != ""){$("#general_information #iConsecutivoPolizas").val(polizas);}
           
           if(valid){
               if($('#edit_form #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}         
               struct_data_post.action  = "save_claim";
               struct_data_post.domroot = "#edit_form #general_information"; 
               $.post("funciones_claims.php",struct_data_post.parse(),
               function(data){
                    switch(data.error){
                     case '0':
                        fn_solotrucking.mensaje(data.msj);
                        if(data.iConsecutivoClaim != ''){
                            $('#edit_form #iConsecutivo').val(data.iConsecutivoClaim);
                            $("#edit_form #files_datagrid").show(); 
                        }
                     break;
                     case '1': fn_solotrucking.mensaje(data.msj); break;
                    }
               },"json"); 
           }
           
           
        },
        edit: function(){
            $(fn_claims.data_grid + " tbody td .edit").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").html();
                    $.post("funciones_claims.php",
                    {
                        accion:"edit_claim", 
                        clave: clave, 
                        domroot : "edit_form"
                    },
                    function(data){
                        if(data.error == '0'){
                           $('#general_information input:text, #general_information select, #general_information textarea,#general_information input:hidden').val('').removeClass('error'); 
                           $('#edit_form input:checkbox').prop("checked",false); 
                           $("#edit_form .policy_apply select").val("NOAPPLY").prop("disabled","disabled").addClass("readonly");    
                           eval(data.fields); 
                           fn_claims.revisar_tipos_polizas();
                           
                           //Calcular nuevamente a  que polizas aplicaria el claim:
                           $("#edit_form .policy_apply select").each(function(){
                             if($(this).val() == "YES"){fn_claims.valida_tipo_dano($(this));}
                           });
                           
                           $('#edit_form #sDescripcionSuceso').val(data.descripcion);
                           //Llenar grid de archivos:
                           fn_claims.files.iConsecutivoClaim = clave;
                           fn_claims.files.fillgrid(); 
                           $("#edit_form #files_datagrid").show(); 
                           
                           fn_popups.resaltar_ventana('edit_form');   
                        }else{
                           fn_solotrucking.mensaje(data.msj);  
                        }       
                    },"json");
            });
       },
        send : function(){
         $(fn_claims.data_grid + " tbody td .btn_send").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").html();
                    $.post("funciones_claims.php",{accion:"send_claim", clave: clave},
                    function(data){
                        fn_solotrucking.mensaje(data.msj);
                        if(data.error == '0'){fn_claims.fillgrid();}     
            },"json");
         });  
       },
        unsent : function(){
          $(fn_claims.data_grid + " tbody td .btn_unsent").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").html();
                    $.post("funciones_claims.php",{accion:"unsent_claim", clave: clave},
                    function(data){
                        fn_solotrucking.mensaje(data.msj);
                        if(data.error == '0'){fn_claims.fillgrid();}     
            },"json");
         });   
       },
        //VALIDACIONES
        revisar_tipos_polizas : function(){  
            if($("#edit_form .company_policies input[type=checkbox]").html() != undefined){
                $("#edit_form .company_policies input[type=checkbox]").each(function(){
                    if($(this).hasClass("PD")){$("#edit_form #eDanoFisico").removeProp("disabled").removeClass("readonly");}
                    if($(this).hasClass("MTC")){$("#edit_form #eDanoMercancia").removeProp("disabled").removeClass("readonly");}  
                    if($(this).hasClass("AL")){$("#edit_form #eDanoTerceros").removeProp("disabled").removeClass("readonly");}  
                });
            }else{
                $("#edit_form .policy_apply select").val("NOAPPLY").prop("disabled","disabled").addClass("readonly");
            }    
        },
        valida_tipo_dano : function(input){
            if(input){
                var value = input.val();
                var tipo  = input.prop("id");
                
                switch(tipo){
                    case 'eDanoTerceros' : var clase = "AL"; break;
                    case 'eDanoMercancia': var clase = "MTC"; break;
                    case 'eDanoFisico'   : var clase = "PD"; break;
                }
                
                if(value == "YES"){ 
                    $("#edit_form .company_policies input[type=checkbox]."+clase).prop("checked",true);
                    $("#edit_form .company_policies label."+clase).show();
                }else{
                    $("#edit_form .company_policies input[type=checkbox]."+clase).prop("checked",false);
                    $("#edit_form .company_policies label."+clase).hide();
                } 
                
            }
        },
        files : {
           pagina_actual : "",
           sort : "ASC",
           orden : "iConsecutivo",
           iConsecutivoClaim : "",
           fillgrid: function(){
                $.ajax({             
                    type:"POST", 
                    url:"funciones_claims.php", 
                    data:{
                        accion:"get_files",
                        iConsecutivoClaim : fn_claims.files.iConsecutivoClaim,
                        registros_por_pagina : "10", 
                        pagina_actual : fn_claims.files.pagina_actual,   
                        ordenInformacion : fn_claims.files.orden,
                        sortInformacion : fn_claims.files.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $("#files_datagrid tbody").empty().append(data.tabla);
                        $("#files_datagrid tbody tr:even").addClass('gray');
                        $("#files_datagrid tbody tr:odd").addClass('white');
                        $("#files_datagrid tfoot .paginas_total").val(data.total);
                        $("#files_datagrid tfoot .pagina_actual").val(data.pagina);
                        fn_claims.files.pagina_actual = data.pagina;
                        fn_claims.files.delete_file();
                    }
                }); 
            },
            delete_file : function(){
              $("#files_datagrid tbody td .trash").bind("click",function(){
                    var clave = $(this).parent().parent().find("td:eq(0)").attr('id');
                    $.post("funciones_claims.php",{accion:"delete_file", clave: clave},
                    function(data){
                        fn_solotrucking.mensaje(data.msj);
                        if(data.error == '0'){fn_claims.files.fillgrid();}      
              },"json");
              });  
            },
            firstPage : function(){
                if($("#files_datagrid .pagina_actual").val() != "1"){
                    fn_claims.files.pagina_actual = "";
                    fn_claims.files.fillgrid();
                }
            },
            previousPage : function(){
                if($("#files_datagrid .pagina_actual").val() != "1"){
                    fn_claims.files.pagina_actual = (parseInt($("#files_datagrid .pagina_actual").val()) - 1) + "";
                    fn_claims.files.fillgrid();
                }
            },
            nextPage : function(){
                if($("#files_datagrid .pagina_actual").val() != $("#files_datagrid .paginas_total").val()){
                    fn_claims.files.pagina_actual = (parseInt($("#files_datagrid .pagina_actual").val()) + 1) + "";
                    fn_claims.files.fillgrid();
                }
            },
            lastPage : function(){
                if($("#files_datagrid .pagina_actual").val() != $("#files_datagrid .paginas_total").val()){
                    fn_claims.files.pagina_actual = $("#files_datagrid .paginas_total").val();
                    fn_claims.files.fillgrid();
                }
            }, 
            ordenamiento : function(campo,objeto){
                $("#files_datagrid #grid-head2 td").removeClass('down').removeClass('up');
                if(campo == fn_claims.files.orden){
                    if(fn_claims.files.sort == "ASC"){
                        fn_claims.files.sort = "DESC";
                        $("#files_datagrid #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_claims.files.sort = "ASC";
                        $("#files_datagrid #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_claims.files.sort = "ASC";
                    fn_claims.files.orden = campo;
                    $("#files_datagrid #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_claims.files.fillgrid();
                return false;
            }, 
           
       }
       /*cargar_tabla_UD : function(parametros){
           if(parametros != ''){
               var policies_selected = "";
               $("#edit_form .company_policies .num_policies" ).each(function( index ){
                       if(this.checked){
                          if(policies_selected != ''){policies_selected += "," + this.value; }else{policies_selected += this.value;} 
                       }
                         
                });
                if(policies_selected != ''){
                    $.ajax({             
                        type:"POST", 
                        url:"funciones_claims.php", 
                        data:{
                            'accion' :"cargar_tablaUD",
                            'iConsecutivosPolizas' : policies_selected,
                            'sFiltroQuery' : parametros,
                        },
                        async : true,
                        dataType : "json",
                        success : function(data){  
                            if(data.error == '0'){
                                $('#edit_form #TablaUD tbody').empty().append(data.tabla);
                            }else{
                                fn_solotrucking.mensaje(data.mensaje);
                            }                             
                            
                        }
                   });    
                }else{
                    fn_solotrucking.mensaje('Please first select the policies that you want to make the claim.');
                    $('#edit_form #FiltroTablaUD').val('');
                    $('#edit_form .p-container').animate({ scrollTop: 0 }, 1000); 
                }
           }
       }*/        
}    

 
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_claims" class="container">
        <div class="page-title">
            <h1>CLAIMS</h1>
            <h2 style="margin-bottom: 5px;">CLAIMS APPLICATIONS</h2>
        </div>
        <div id="info_policies">
        <h3 class="popup-gridtit clear"></h3>
        <p style="width: 98%;margin: 10px auto 5px;">Your claims will apply in following policies:</p>
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
        <table id="data_grid" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style="width:50px!important;"><input class="flt_id" type="text" placeholder="ID:"></td> 
                <td>
                    <select class="flt_type" onblur="fn_claims.filtraInformacion();">
                        <option value="">All</option>
                        <option value="DRIVER">Driver</option>
                        <option value="UNIT/TRAILER">Unit / Trailer</option>
                        <option value="BOTH">Both</option>
                    </select>
                </td> 
                <td><input class="flt_dateIncident flt_fecha"   type="text" placeholder="MM-DD-YY"></td> 
                <td><input class="flt_hourIncident hora"   type="text" placeholder="00:00"></td> 
                <td><input class="flt_cityIncident" type="text" placeholder="City: "></td>
                <td><input class="flt_stateIncident" type="text" placeholder="State: "></td>
                <td>
                    <select class="flt_statusclaim" onblur="fn_claims.filtraInformacion();">
                        <option value="">All</option>
                        <option value="EDITABLE">Without Sending</option>
                        <option value="SENT">Sent To Solo-Trucking</option>
                        <option value="INPROCESS">In Process</option> 
                        <option value="APPROVED">Approved</option> 
                        <option value="CANCELED">Candeled</option>
                    </select>
                </td> 
                <td><input class="flt_dateAplication flt_fecha" type="text" placeholder="MM-DD-YY"></td> 
                <td style='width:120px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_claims.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_claims.add();"><i class="fa fa-plus"></i></div>  
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_claims.ordenamiento('iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('eCategoria',this.cellIndex);">Type</td>
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('dFechaIncidente',this.cellIndex);">Incident Date</td>
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('dHoraIncidente',this.cellIndex);">INCIDENT Hour</td>
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('sCiudad',this.cellIndex);">City </td> 
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('sEstado',this.cellIndex);">State</td> 
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('eStatus',this.cellIndex);">Status of Claim</td> 
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('dFechaAplicacion',this.cellIndex);">Application Date</td> 
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
                        <button id="pgn-inicio"    onclick="fn_claims.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_claims.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_claims.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_claims.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table> 
    </div>
</div>
<!-- FORMULARIOS -->
<div id="edit_form" class="popup-form">
    <div class="p-header">
        <h2>CLAIMS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('edit_form');fn_claims.filtraInformacion();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container"> 
    <div>
        <form>
            <table>
             <tr>
             <td>
                <div class="field_item"> 
                    <label style="margin-left:15px;">Your claims will apply in following policies:</label> 
                    <div class="company_policies" style="padding: 10px 10px 10px 23px;"></div>
                </div>
             </td>
             </tr>
            </table> 
            <fieldset>
                <legend>INFORMATION FROM INCIDENT</legend>
                <table id="general_information" style="width: 100%;">
                <tr><td colspan="100%"><p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p></td></tr>
                <tr>
                    <td style="width: 50%;">
                    <div class="field_item">
                        <input id="iConsecutivo" type="hidden" value=""> 
                        <input id="iConsecutivoPolizas" type="hidden" value=""> 
                        <label>Date: <span style="color:#ff0000;">*</span>:</label><br> 
                        <input tabindex="1" id="dFechaIncidente" type="text" class="fecha" style="width: 85%;" placeholder="MM/DD/YYYY">
                    </div>
                    </td>
                    <td style="width: 50%;">
                    <div class="field_item"> 
                        <label>Hour: <span style="color:#ff0000;">*</span>:</label><br>
                        <input tabindex="1" id="dHoraIncidente" type="text" class="hora" title="Please capture the hour in 24/h format" style="width: 98%;" placeholder="HH:MM">
                    </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="100%">
                    <div class="field_item policy_apply"> 
                        <label>There was damage to third parties? (Persons and/or properties) <span style="color:#ff0000;">*</span>:</label> 
                        <select id="eDanoTerceros" onblur="fn_claims.valida_tipo_dano($(this));" title="Auto-Liability Policy">
                            <option value="NOAPPLY">NO APPLY</option>
                            <option value="YES">YES</option> 
                            <option value="NO">NO</option> 
                        </select>
                    </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="100%">
                    <div class="field_item policy_apply"> 
                        <label>There was damage to your Unit/Trailer?<span style="color:#ff0000;">*</span>:</label> 
                        <select id="eDanoFisico" onblur="fn_claims.valida_tipo_dano($(this));" title="Physical Damage Policy">
                            <option value="NOAPPLY">NO APPLY</option>
                            <option value="YES">YES</option> 
                            <option value="NO">NO</option> 
                        </select>
                    </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="100%">
                    <div class="field_item policy_apply"> 
                        <label>There was damage on your Cargo?<span style="color:#ff0000;">*</span>:</label> 
                        <select id="eDanoMercancia" onblur="fn_claims.valida_tipo_dano($(this));" title="Motor Truck Cargo Policy">
                            <option value="NOAPPLY">NO APPLY</option>
                            <option value="YES">YES</option> 
                            <option value="NO">NO</option> 
                        </select>
                    </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="100%">
                    <div class="field_item"> 
                        <label>What happend? <span style="color:#ff0000;">*</span>:</label> 
                        <textarea tabindex="1" id="sDescripcionSuceso" maxlenght="1000" style="resize: none;" title="please write what happend in the incident."></textarea>
                    </div>
                    </td>
                </tr>
                <tr>
                    <td>
                    <div class="field_item"> 
                        <label>Where State?: <span style="color:#ff0000;">*</span>:</label> 
                        <select tabindex="1" id="sEstado"><option value="">Select an opction...</option></select>
                    </div>
                    </td>
                    <td>
                    <div class="field_item"> 
                        <label>What City?:</label> 
                        <input tabindex="1" id="sCiudad" type="text" class="txt-uppercase">
                    </div>
                    </td>
                </tr>
                <tr>
                    <td>
                    <div class="field_item"> 
                        <label>Driver <span style="color:#ff0000;">*</span>:</label> 
                        <input tabindex="1" id="sDriver" type="text" placeholder="Write the name or system id of your driver" style="width: 98%;" title="Please check before that the driver is in the selected policy.">
                    </div>
                    </td>
                    <td>
                    <div class="field_item"> 
                        <label>Unit/Trailer <span style="color:#ff0000;">*</span>:</label> 
                        <input tabindex="1" id="sUnitTrailer" type="text" placeholder="Write the VIN or system id of your Unit or Trailer" style="width: 98%;" title="Please check before that the unit/trailer is in the selected policy.">
                    </div>
                    </td>
                </tr>
                </table>
                <table id="files_datagrid" class="popup-datagrid">
                    <thead>
                        <tr id="grid-head2">
                            <td class="etiqueta_grid">File Name</td>
                            <td class="etiqueta_grid">Type</td>
                            <td class="etiqueta_grid">Size</td>
                            <td class="etiqueta_grid" style="width: 100px;text-align: center;"><button id="btnFile" type="button" title="Please upload the pictures in JPG format">Upload file</button></td>
                        </tr>
                    </thead>
                    <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No uploaded files.</td></tr></tbody>
                    <tfoot>
                    <tr><td colspan="100%">
                            <div class="datagrid-pages">
                                <input class="pagina_actual" type="text" readonly="readonly" size="3">
                                <label> / </label>
                                <input class="paginas_total" type="text" readonly="readonly" size="3">
                            </div>
                    </td></tr>
                    <tr><td colspan="100%">
                        <div class="datagrid-menu-pages">
                            <button class="pgn-inicio"    onclick="fn_claims.files.firstPage();" title="First page"><span></span></button>
                            <button class="pgn-anterior"  onclick="fn_claims.files.previousPage();" title="Previous"><span></span></button>
                            <button class="pgn-siguiente" onclick="fn_claims.files.nextPage();" title="Next"><span></span></button>
                            <button class="pgn-final"     onclick="fn_claims.files.lastPage();" title="Last Page"><span></span></button>
                        </div>
                    </td></tr>
                    </tfoot>
                </table>
            </fieldset>
            <button type="button" class="btn-1" onclick="fn_claims.save();">SAVE</button>
            <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('edit_form');fn_claims.filtraInformacion();" style="margin-right:10px;background:#e8051b;">CLOSE</button>
        </form> 
    </div>
    </div>
</div>
<!-- DIALOGOS -->
<div id="dialog_select_company" title="SYSTEM MESSAGE" style="display:none;">
    <p>Please select a company first:</p>
    <form><div> <Select id="iConsecutivoCompania"><option value="">Select an option...</option></select></div></form>   
</div> 
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>