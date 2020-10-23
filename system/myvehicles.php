<?php session_start();    
if ( !($_SESSION["acceso"] == '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<!---- HEADER ----->
<?php include("header.php"); ?>  
<script type="text/javascript"> 
$(document).ready(inicio);
function inicio(){  
        $.blockUI();
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);
        //if(tipo_usuario != "1"){validarLoginCliente(usuario_actual);}
        fn_vehicles.init();
        fn_vehicles.fillgrid();
        $.unblockUI();
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}  }                   
var fn_vehicles = {
        domroot:"#ct_mypolicies",
        data_grid: "#data_grid_mypolicies",
        filtro : "",
        pagina_actual : "",
        sort : "DESC",
        orden : "iYear",
        init : function(){
            $('.num').keydown(fn_solotrucking.inputnumero); 
            $('.decimals').keydown(fn_solotrucking.inputdecimals);
            //Filtrado con la tecla enter
            $(fn_vehicles.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_vehicles.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_vehicles.filtraInformacion();
                }
            });    
            
            $(".fecha,.flt_fecha").mask("99-99-9999"); 
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_myvehicles.php", 
                data:{
                    accion:"get_data",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_vehicles.pagina_actual, 
                    filtroInformacion : fn_vehicles.filtro,  
                    ordenInformacion : fn_vehicles.orden,
                    sortInformacion : fn_vehicles.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_vehicles.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_vehicles.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_vehicles.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_vehicles.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_vehicles.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_vehicles.pagina_actual = data.pagina;
                    //fn_vehicles.view();
                }
            }); 
        },
        firstPage : function(){
            if($(fn_vehicles.data_grid+" #pagina_actual").val() != "1"){
                fn_vehicles.pagina_actual = "";
                fn_vehicles.fillgrid();
            }
        },
        previousPage : function(){
                if($(fn_vehicles.data_grid+" #pagina_actual").val() != "1"){
                    fn_vehicles.pagina_actual = (parseInt($(fn_vehicles.data_grid+" #pagina_actual").val()) - 1) + "";
                    fn_vehicles.fillgrid();
                }
        },
        nextPage : function(){
                if($(fn_vehicles.data_grid+" #pagina_actual").val() != $(fn_vehicles.data_grid+" #paginas_total").val()){
                    fn_vehicles.pagina_actual = (parseInt($(fn_vehicles.data_grid+" #pagina_actual").val()) + 1) + "";
                    fn_vehicles.fillgrid();
                }
        },
        lastPage : function(){
                if($(fn_vehicles.data_grid+" #pagina_actual").val() != $(fn_vehicles.data_grid+" #paginas_total").val()){
                    fn_vehicles.pagina_actual = $(fn_vehicles.data_grid+" #paginas_total").val();
                    fn_vehicles.fillgrid();
                }
        }, 
        ordenamiento : function(campo,objeto){
                $(fn_vehicles.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

                if(campo == fn_vehicles.orden){
                    if(fn_vehicles.sort == "ASC"){
                        fn_vehicles.sort = "DESC";
                        $(fn_vehicles.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                    }else{
                        fn_vehicles.sort = "ASC";
                        $(fn_vehicles.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                    }
                }else{
                    fn_vehicles.sort = "ASC";
                    fn_vehicles.orden = campo;
                    $(fn_vehicles.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
                fn_vehicles.fillgrid();

                return false;
        }, 
        filtraInformacion : function(){
                fn_vehicles.pagina_actual = 0;
                fn_vehicles.filtro = "";
                if($(fn_vehicles.data_grid+" .flt_vin").val() != ""){ fn_vehicles.filtro += "sVIN|"+$(fn_vehicles.data_grid+" .flt_vin").val()+","}
                if($(fn_vehicles.data_grid+" .flt_year").val() != ""){ fn_vehicles.filtro += "iYear|"+$(fn_vehicles.data_grid+" .flt_year").val()+","} 
                if($(fn_vehicles.data_grid+" .flt_type").val() != ""){ fn_vehicles.filtro += "sTipo|"+$(fn_vehicles.data_grid+" .flt_type").val()+","} 
                if($(fn_vehicles.data_grid+" .flt_radio").val() != ""){ fn_vehicles.filtro += "iConsecutivoRadio|"+$(fn_vehicles.data_grid+" .flt_radio").val()+","} 
                if($(fn_vehicles.data_grid+" .flt_marca").val() != ""){ fn_vehicles.filtro += "iModelo|"+$(fn_vehicles.data_grid+" .flt_marca").val()+","}    
                if($(fn_vehicles.data_grid+" .flt_peso").val() != ""){ fn_vehicles.filtro += "sPeso|"+$(fn_vehicles.data_grid+" .flt_peso").val()+","} 
                if($(fn_vehicles.data_grid+" .flt_pd").val() != ""){ fn_vehicles.filtro += "iTotalPremiumPD|"+$(fn_vehicles.data_grid+" .flt_pd").val()+","} 
                fn_vehicles.fillgrid();
       },
       get_list_description : function(policy,type){
            if(type == 'D'){
                fn_vehicles.list_drivers.id_policy = policy; 
                fn_vehicles.list_drivers.fill_actives();
                fn_popups.resaltar_ventana('driver_list_form'); 
            }else if(type == 'U'){
                fn_vehicles.list_units.id_policy = policy; 
                fn_vehicles.list_units.fill_actives();
                fn_popups.resaltar_ventana('units_list_form'); 
            }
        },      
}    

 
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_mypolicies" class="container">
        <div class="page-title">
            <h1>POLICIES</h1>
            <h2 style="margin-bottom: 5px;">MY VEHICLES' LIST</h2>
        </div>
        <table id="data_grid_mypolicies" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style="width:300px"><input class="flt_vin" type="text" placeholder="VIN #:"></td> 
                <td style='width:80px;'><input class="flt_year num" type="text" placeholder="####" maxlength="4"></td> 
                <td style='width:100px;'>
                    <select class="flt_type">
                        <option value="">Select an option...</option>
                        <option value="UNIT">UNIT</option> 
                        <option value="TRAILER">TRAILER</option>
                    </select>
                </td> 
                <td style='width:100px;'>
                    <select class="flt_radio">
                        <option value="">Select an option...</option>
                        <option value="4">0-50</option> 
                        <option value="1">0-250</option>
                        <option value="2">0-500</option>
                        <option value="3">500+</option> 
                        <option value="5">0-1500</option>
                        <option value="5">0-15000</option> 
                        <option value="7">UNLIMITED MILES</option> 
                    </select>
                </td> 
                <td><input class="flt_marca" type="text" placeholder="Make:"></td>  
                <td style='width:80px;'><input class="flt_peso" type="text" placeholder="Weight:"></td>
                <td style='width:100px;'><input class="flt_pd" type="text" placeholder="Premium PD:"></td>
                <td style='width:300px;'></td>
                <td style='width:35px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_vehicles.filtraInformacion();"><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_vehicles.ordenamiento('sVIN',this.cellIndex);">VIN</td> 
                <td class="etiqueta_grid"      onclick="fn_vehicles.ordenamiento('iYear',this.cellIndex);">YEAR</td>
                <td class="etiqueta_grid"      onclick="fn_vehicles.ordenamiento('sTipo',this.cellIndex);">TYPE</td>
                <td class="etiqueta_grid"      onclick="fn_vehicles.ordenamiento('iConsecutivoRadio',this.cellIndex);">RADIUS</td> 
                <td class="etiqueta_grid"      onclick="fn_vehicles.ordenamiento('iModelo',this.cellIndex);">MAKE</td> 
                <td class="etiqueta_grid"      onclick="fn_vehicles.ordenamiento('sPeso',this.cellIndex);">WEIGHT</td> 
                <td class="etiqueta_grid"      onclick="fn_vehicles.ordenamiento('iTotalPremiumPD',this.cellIndex);">PREMIUM PD</td> 
                <td class="etiqueta_grid" style="padding: 3px 0px 0px!important;width:450px;">
                    <span style="display: block;padding: 0px 3px;text-align: center;">POLICIES</span>
                    <table style="width: 100%;text-transform: uppercase;">
                    <thead><tr>
                        <td style="width:40%;">No.</td>
                        <td style="width:10%">Type</td>
                        <td style="width:50%">Application Date</td>
                    </tr></thead></table>
                </td> 
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
                        <button id="pgn-inicio"    onclick="fn_vehicles.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_vehicles.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_vehicles.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_vehicles.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>    
    </div>
</div>
<!---- FORMULARIOS
<div id="mypolicies_edit_form" class="popup-form">
    <div class="p-header">
        <h2>MY POLICY UNITS AND DRIVERS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_vehicles.valida_archivos();"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <p class="msg-error error" style="padding:10px;margin-bottom:10px;display:none;"></p>
    <div>
        <form>
            <fieldset>
                <legend></legend>
            </fieldset>
            <br>  
            <button type="button" class="btn-1" onclick="fn_vehicles.save();">SAVE</button>
        </form> 
    </div>
    </div>
</div>
 ------> 
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>