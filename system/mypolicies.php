<?php session_start();    
if ( !($_SESSION["acceso"] == '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
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
        //if(tipo_usuario != "1"){validarLoginCliente(usuario_actual);}
        fn_mypolicies.init();
        fn_mypolicies.fillgrid();
        $.unblockUI();
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}  }                   
var fn_mypolicies = {
        domroot:"#ct_mypolicies",
        data_grid: "#data_grid_mypolicies",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "A.iConsecutivo",
        init : function(){
            $('.num').keydown(fn_solotrucking.inputnumero); 
            $('.decimals').keydown(fn_solotrucking.inputdecimals);
            //Filtrado con la tecla enter
            $(fn_mypolicies.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_mypolicies.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_mypolicies.filtraInformacion();
                }
            });    
            
            $(".flt_policyexpdate,.flt_policystartdate, .fecha,.flt_fecha").mask("99-99-9999"); 
            
            //Filtrar listas grid:
            $('#drivers_active_table #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {event.preventDefault();fn_mypolicies.list_drivers.filtraInformacion();}
                if(event.keyCode == '27'){event.preventDefault();$(this).val('');fn_mypolicies.list_drivers.filtraInformacion();}
            });
            $('#unit_active_table #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {event.preventDefault();fn_mypolicies.list_units.filtraInformacion();}
                if(event.keyCode == '27'){event.preventDefault();$(this).val('');fn_mypolicies.list_units.filtraInformacion();}
            });
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_mypolicies.php", 
                data:{
                    accion:"get_policies",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_mypolicies.pagina_actual, 
                    filtroInformacion : fn_mypolicies.filtro,  
                    ordenInformacion : fn_mypolicies.orden,
                    sortInformacion : fn_mypolicies.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_mypolicies.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_mypolicies.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_mypolicies.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_mypolicies.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_mypolicies.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_mypolicies.pagina_actual = data.pagina;
                    //fn_mypolicies.view();
                }
            }); 
        },
        firstPage : function(){
            if($(fn_mypolicies.data_grid+" #pagina_actual").val() != "1"){
                fn_mypolicies.pagina_actual = "";
                fn_mypolicies.fillgrid();
            }
        },
        previousPage : function(){
                if($(fn_mypolicies.data_grid+" #pagina_actual").val() != "1"){
                    fn_mypolicies.pagina_actual = (parseInt($(fn_mypolicies.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_mypolicies.fillgrid();
                }
        },
        nextPage : function(){
                if($(fn_mypolicies.data_grid+" #pagina_actual").val() != $(fn_mypolicies.data_grid+" #paginas_total").val()){
                    fn_mypolicies.pagina_actual = (parseInt($(fn_mypolicies.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_mypolicies.fillgrid();
                }
        },
        lastPage : function(){
                if($(fn_mypolicies.data_grid+" #pagina_actual").val() != $(fn_mypolicies.data_grid+" #paginas_total").val()){
                    fn_mypolicies.pagina_actual = $(fn_mypolicies.data_grid+" #paginas_total").val();
                    fn_mypolicies.fillgrid();
                }
        }, 
        ordenamiento : function(campo,objeto){
                $(fn_mypolicies.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_mypolicies.orden){
                    if(fn_mypolicies.sort == "ASC"){
                        fn_mypolicies.sort = "DESC";
                        $(fn_mypolicies.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_mypolicies.sort = "ASC";
                        $(fn_mypolicies.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_mypolicies.sort = "ASC";
                    fn_mypolicies.orden = campo;
                    $(fn_mypolicies.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_mypolicies.fillgrid();

                return false;
        }, 
        filtraInformacion : function(){
                fn_mypolicies.pagina_actual = 0;
                fn_mypolicies.filtro = "";
                if($(fn_mypolicies.data_grid+" .flt_pid").val() != ""){ fn_mypolicies.filtro += "A.iConsecutivo|"+$(fn_mypolicies.data_grid+" .flt_pid").val()+","}
                if($(fn_mypolicies.data_grid+" .flt_policynumber").val() != ""){ fn_mypolicies.filtro += "sNumeroPoliza|"+$(fn_mypolicies.data_grid+" .flt_policynumber").val()+","} 
                //if($(fn_mypolicies.data_grid+" .flt_pbroker").val() != ""){ fn_mypolicies.filtro += "sName|"+$(fn_mypolicies.data_grid+" .flt_pbroker").val()+","}  
                if($(fn_mypolicies.data_grid+" .flt_policytype").val() != ""){ fn_mypolicies.filtro += "sDescripcion|"+$(fn_mypolicies.data_grid+" .flt_policytype").val()+","} 
                if($(fn_mypolicies.data_grid+" .flt_policystartdate").val() != ""){ fn_mypolicies.filtro += "dFechaInicio|"+$(fn_mypolicies.data_grid+" .flt_policystartdate").val()+","} 
                if($(fn_mypolicies.data_grid+" .flt_policyexpdate").val() != ""){ fn_mypolicies.filtro += "dFechaCaducidad|"+$(fn_mypolicies.data_grid+" .flt_policyexpdate").val()+","}    
            
                fn_mypolicies.fillgrid();
       },
       get_list_description : function(policy,type){
            if(type == 'D'){
                fn_mypolicies.list_drivers.id_policy = policy; 
                fn_mypolicies.list_drivers.fill_actives();
                fn_popups.resaltar_ventana('driver_list_form'); 
            }else if(type == 'U'){
                fn_mypolicies.list_units.id_policy = policy; 
                fn_mypolicies.list_units.fill_actives();
                fn_popups.resaltar_ventana('units_list_form'); 
            }
        },
        list_drivers : {
            domroot_nav : "#driver_list_form",
            filtro : "",
            pagina_actual : "",
            sort : "ASC",
            orden : "sNombre",
            id_policy : "",
            fill_actives : function(){
                 $.ajax({             
                    type:"POST", 
                    url:"funciones_mypolicies.php", 
                    data:{
                        accion:"get_drivers_active",
                        registros_por_pagina : "30", 
                        iConsecutivoPoliza :  fn_mypolicies.list_drivers.id_policy,
                        pagina_actual : fn_mypolicies.list_drivers.pagina_actual, 
                        filtroInformacion : fn_mypolicies.list_drivers.filtro,  
                        ordenInformacion : fn_mypolicies.list_drivers.orden,
                        sortInformacion : fn_mypolicies.list_drivers.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_mypolicies.list_drivers.domroot_nav+" #drivers_active_table tbody").empty().append(data.tabla);
                        $(fn_mypolicies.list_drivers.domroot_nav+" #drivers_active_table tbody tr:even").addClass('gray');
                        $(fn_mypolicies.list_drivers.domroot_nav+" #drivers_active_table tbody tr:odd").addClass('white');
                        $(fn_mypolicies.list_drivers.domroot_nav+" #drivers_active_table tfoot .paginas_total").val(data.total);
                        $(fn_mypolicies.list_drivers.domroot_nav+" #drivers_active_table tfoot .pagina_actual").val(data.pagina);
                        fn_mypolicies.list_drivers.pagina_actual = data.pagina; 
                    }
                }); 
            },
            filtraInformacion : function(){
                fn_mypolicies.list_drivers.pagina_actual = 0;
                fn_mypolicies.list_drivers.filtro = "";
                if($(fn_mypolicies.list_drivers.domroot_nav+" .flt_dName").val() != ""){ fn_mypolicies.list_drivers.filtro += "sNombre|"+$(fn_mypolicies.list_drivers.domroot_nav+" .flt_dName").val()+","}
                if($(fn_mypolicies.list_drivers.domroot_nav+" .flt_dDob").val() != ""){ fn_mypolicies.list_drivers.filtro += "dFechaNacimiento|"+$(fn_mypolicies.list_drivers.domroot_nav+" .flt_dDob").val()+","} 
                if($(fn_mypolicies.list_drivers.domroot_nav+" .flt_dLicense").val() != ""){ fn_mypolicies.list_drivers.filtro += "iNumLicencia|"+$(fn_mypolicies.list_drivers.domroot_nav+" .flt_dLicense").val()+","} 
                if($(fn_mypolicies.list_drivers.domroot_nav+" .flt_dtype").val() != ""){ fn_mypolicies.list_drivers.filtro += "eTipoLicencia|"+$(fn_mypolicies.list_drivers.domroot_nav+" .flt_dtype").val()+","}  
                if($(fn_mypolicies.list_drivers.domroot_nav+" .flt_dExpire").val() != ""){ fn_mypolicies.list_drivers.filtro += "dFechaExpiracionLicencia|"+$(fn_mypolicies.list_drivers.domroot_nav+" .flt_dExpire").val()+","} 
                if($(fn_mypolicies.list_drivers.domroot_nav+" .flt_dYears").val() != ""){ fn_mypolicies.list_drivers.filtro += "iExperienciaYear|"+$(fn_mypolicies.list_drivers.domroot_nav+" .flt_dYears").val()+","} 
                fn_mypolicies.list_drivers.fill_actives();
            },
            firstPage : function(){
                if($("#drivers_active_table .pagina_actual").val() != "1"){
                    fn_mypolicies.list_drivers.pagina_actual = "";
                    fn_mypolicies.list_drivers.fill_actives();
                }
            },
            previousPage : function(){
                if($("#drivers_active_table .pagina_actual").val() != "1"){
                    fn_mypolicies.list_drivers.pagina_actual = (parseInt($("#drivers_active_table .pagina_actual").val()) - 1) + "";
                    fn_mypolicies.list_drivers.fill_actives();
                }
            },
            nextPage : function(){
                if($("#drivers_active_table .pagina_actual").val() != $("#drivers_active_table .paginas_total").val()){
                    fn_mypolicies.list_drivers.pagina_actual = (parseInt($("#drivers_active_table .pagina_actual").val()) + 1) + "";
                    fn_mypolicies.list_drivers.fill_actives();
                }
            },
            lastPage : function(){
                if($("#drivers_active_table .pagina_actual").val() != $("#drivers_active_table .paginas_total").val()){
                    fn_mypolicies.list_drivers.pagina_actual = $("#drivers_active_table .paginas_total").val();
                    fn_mypolicies.list_drivers.fill_actives();
                }
            },     
        },
        list_units : {
            domroot_nav : "#units_list_form",
            filtro : "",
            pagina_actual : "",
            sort : "ASC",
            orden : "sVIN",
            id_policy : "",
            fill_actives : function(){
                 $.ajax({             
                    type:"POST", 
                    url:"funciones_mypolicies.php", 
                    data:{
                        accion:"get_units_active",
                        registros_por_pagina : "30", 
                        iConsecutivoPoliza :  fn_mypolicies.list_units.id_policy,
                        pagina_actual : fn_mypolicies.list_units.pagina_actual, 
                        filtroInformacion : fn_mypolicies.list_units.filtro,  
                        ordenInformacion : fn_mypolicies.list_units.orden,
                        sortInformacion : fn_mypolicies.list_units.sort,
                    },
                    async : true,
                    dataType : "json",
                    success : function(data){                               
                        $(fn_mypolicies.list_units.domroot_nav+" #unit_active_table tbody").empty().append(data.tabla);
                        $(fn_mypolicies.list_units.domroot_nav+" #unit_active_table tbody tr:even").addClass('gray');
                        $(fn_mypolicies.list_units.domroot_nav+" #unit_active_table tbody tr:odd").addClass('white');
                        $(fn_mypolicies.list_units.domroot_nav+" #unit_active_table tfoot .paginas_total").val(data.total);
                        $(fn_mypolicies.list_units.domroot_nav+" #unit_active_table tfoot .pagina_actual").val(data.pagina);
                        fn_mypolicies.list_units.pagina_actual = data.pagina; 
                    }
                }); 
            },
            filtraInformacion : function(){
                fn_mypolicies.list_units.pagina_actual = 0;
                fn_mypolicies.list_units.filtro = "";
                if($(fn_mypolicies.list_units.domroot_nav+" .flt_uVIN").val() != ""){ fn_mypolicies.list_units.filtro += "sVIN|"+$(fn_mypolicies.list_units.domroot_nav+" .flt_uVIN").val()+","}
                if($(fn_mypolicies.list_units.domroot_nav+" .flt_uRadio").val() != ""){ fn_mypolicies.list_units.filtro += "iConsecutivoRadio|"+$(fn_mypolicies.list_units.domroot_nav+" .flt_uRadio").val()+","} 
                if($(fn_mypolicies.list_units.domroot_nav+" .flt_uYear").val() != ""){ fn_mypolicies.list_units.filtro += "iYear|"+$(fn_mypolicies.list_units.domroot_nav+" .flt_uYear").val()+","} 
                if($(fn_mypolicies.list_units.domroot_nav+" .flt_uMake").val() != ""){ fn_mypolicies.list_units.filtro += "C.sDescription|"+$(fn_mypolicies.list_units.domroot_nav+" .flt_uMake").val()+","}  
                if($(fn_mypolicies.list_units.domroot_nav+" .flt_uType").val() != ""){ fn_mypolicies.list_units.filtro += "sTipo|"+$(fn_mypolicies.list_units.domroot_nav+" .flt_uType").val()+","} 
                if($(fn_mypolicies.list_units.domroot_nav+" .flt_uWeight").val() != ""){ fn_mypolicies.list_units.filtro += "sPeso|"+$(fn_mypolicies.list_units.domroot_nav+" .flt_uWeight").val()+","} 
                fn_mypolicies.list_units.fill_actives();
            }, 
            firstPage : function(){
                if($("#unit_active_table .pagina_actual").val() != "1"){
                    fn_mypolicies.list_units.pagina_actual = "";
                    fn_mypolicies.list_units.fill_actives();
                }
            },
            previousPage : function(){
                if($("#unit_active_table .pagina_actual").val() != "1"){
                    fn_mypolicies.list_units.pagina_actual = (parseInt($("#unit_active_table .pagina_actual").val()) - 1) + "";
                    fn_mypolicies.list_units.fill_actives();
                }
            },
            nextPage : function(){
                if($("#unit_active_table .pagina_actual").val() != $("#unit_active_table .paginas_total").val()){
                    fn_mypolicies.list_units.pagina_actual = (parseInt($("#unit_active_table .pagina_actual").val()) + 1) + "";
                    fn_mypolicies.list_units.fill_actives();
                }
            },
            lastPage : function(){
                if($("#unit_active_table .pagina_actual").val() != $("#unit_active_table .paginas_total").val()){
                    fn_mypolicies.list_units.pagina_actual = $("#unit_active_table .paginas_total").val();
                    fn_mypolicies.list_units.fill_actives();
                }
            },   
        }
        
            
}    

 
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_mypolicies" class="container">
        <div class="page-title">
            <h1>POLICIES</h1>
            <h2 style="margin-bottom: 5px;">MY POLICIES INFORMATION</h2>
        </div>
        <table id="data_grid_mypolicies" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style="width:50px!important;"><input class="flt_pid" type="text" placeholder="ID:"></td> 
                <td><input class="flt_policynumber" type="text" placeholder="Policy Numer:"></td>
                <td><input class="flt_policytype" type="text" placeholder="Policy Type:"></td> 
                <td><input class="flt_policystartdate" type="text" placeholder="MM-DD-YY"></td> 
                <td><input class="flt_policyexpdate" type="text" placeholder="MM-DD-YY"></td>  
                <td style='width:120px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_mypolicies.filtraInformacion();"><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_mypolicies.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_mypolicies.ordenamiento('sNumeroPoliza',this.cellIndex);">Policy Number</td>
                <td class="etiqueta_grid"      onclick="fn_mypolicies.ordenamiento('sDescripcion',this.cellIndex);">Type</td>
                <td class="etiqueta_grid"      onclick="fn_mypolicies.ordenamiento('dFechaInicio',this.cellIndex);">EFFECTIVE DATE </td> 
                <td class="etiqueta_grid"      onclick="fn_mypolicies.ordenamiento('dFechaCaducidad',this.cellIndex);">Expiration Date</td> 
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
                        <button id="pgn-inicio"    onclick="fn_mypolicies.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_mypolicies.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_mypolicies.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_mypolicies.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table> 
        <p style="margin:5px auto; text-align:center;"><span style="font-weight:bold;color:#00518a;">System Message:</span> We are working on this section to show all the information of your policies, please be patient.</p>   
    </div>
</div>
<!-- FORMULARIOS -->
<div id="mypolicies_edit_form" class="popup-form">
    <div class="p-header">
        <h2>MY POLICY UNITS AND DRIVERS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_mypolicies.valida_archivos();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <p class="msg-error error" style="padding:10px;margin-bottom:10px;display:none;"></p>
    <div>
        <form>
            <fieldset>
                <legend></legend>
            </fieldset>
            <br>  
            <button type="button" class="btn-1" onclick="fn_mypolicies.save();">SAVE</button>
        </form> 
    </div>
    </div>
</div>
<!-- upload file form -->
<div id="driver_list_form" class="popup-form" style="width:90%!important;">
    <div class="p-header">
        <h2>LIST OF DRIVERS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('driver_list_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
        <table id="drivers_active_table" class="popup-datagrid">
                <thead>
                    <tr id="grid-head1">
                        <td style="width:500px;"><input class="flt_dName" type="text" placeholder="Name:"></td> 
                        <td><input class="flt_dDob flt_fecha"  type="text" placeholder="MM-DD-YY"></td>
                        <td><input class="flt_dLicense" type="text" placeholder="License #:"></td> 
                        <td>
                            <select class="flt_dtype" type="text" onblur="fn_mypolicies.list_drivers.filtraInformacion();">
                                <option value="">Select an option...</option>
                                <option value="1">Federal / B1</option> 
                                <option value="2">Commercial / CDL - A</option> 
                            </select>
                        </td> 
                        <td><input class="flt_dExpire flt_fecha" type="text" placeholder="MM-DD-YY"></td> 
                        <td style="width:80px;"><input class="flt_dYears num" type="text" placeholder="Years:"></td>  
                        <td style='width:120px;'>
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_mypolicies.list_drivers.filtraInformacion();"><i class="fa fa-search"></i></div>
                        </td> 
                    </tr>
                    <tr id="grid-head2">
                        <td class="etiqueta_grid">Name</td>
                        <td class="etiqueta_grid">DOB</td>
                        <td class="etiqueta_grid">LICENSE NUMBER</td>
                        <td class="etiqueta_grid">LICENSE TYPE</td> 
                        <td class="etiqueta_grid">EXPIRE DATE</td> 
                        <td class="etiqueta_grid">EXPERIENCE YEARS</td>
                        <td class="etiqueta_grid">APPLICATION DATE</td>
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
                                <button class="pgn-inicio"    onclick="fn_mypolicies.list_drivers.firstPage();" title="First page"><span></span></button>
                                <button class="pgn-anterior"  onclick="fn_mypolicies.list_drivers.previousPage();" title="Previous"><span></span></button>
                                <button class="pgn-siguiente" onclick="fn_mypolicies.list_drivers.nextPage();" title="Next"><span></span></button>
                                <button class="pgn-final"     onclick="fn_mypolicies.list_drivers.lastPage();" title="Last Page"><span></span></button>
                            </div>
                        </td>
                    </tr>
                </tfoot>
        </table>
    </div>
</div>
<div id="units_list_form" class="popup-form" style="width:90%!important;">
    <div class="p-header">
        <h2>LIST OF UNITS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('units_list_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
        <table id="unit_active_table" class="popup-datagrid">
                <thead>
                    <tr id="grid-head1">
                        <td style="width:500px;"><input class="flt_uVIN" type="text" placeholder="Name:"></td> 
                        <td>
                            <select class="flt_uRadio" type="text" onblur="fn_mypolicies.list_units.filtraInformacion();">
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
                            <div class="btn-icon-2 btn-left" title="Search" onclick="fn_mypolicies.list_units.filtraInformacion();"><i class="fa fa-search"></i></div>
                        </td> 
                    </tr>
                    <tr id="grid-head2">
                        <td class="etiqueta_grid">VIN</td>
                        <td class="etiqueta_grid">RADIO</td>
                        <td class="etiqueta_grid">YEAR</td>
                        <td class="etiqueta_grid">MAKE</td> 
                        <td class="etiqueta_grid">TYPE</td> 
                        <td class="etiqueta_grid">WEIGHT</td> 
                        <td class="etiqueta_grid">APPLICATION DATE</td>
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
                                <button class="pgn-inicio"    onclick="fn_mypolicies.list_units.firstPage();" title="First page"><span></span></button>
                                <button class="pgn-anterior"  onclick="fn_mypolicies.list_units.previousPage();" title="Previous"><span></span></button>
                                <button class="pgn-siguiente" onclick="fn_mypolicies.list_units.nextPage();" title="Next"><span></span></button>
                                <button class="pgn-final"     onclick="fn_mypolicies.list_units.lastPage();" title="Last Page"><span></span></button>
                            </div>
                        </td>
                    </tr>
                </tfoot>
        </table>
    </div>
</div>

<!-- FOOTER -->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>