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
        fn_bactividades.init();
        //fn_bactividades.fillgrid();
        $.unblockUI();  
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}                   
var fn_bactividades = {
        domroot:"#ct_actividades",
        data_grid: "#data_grid_actividades",
        form : "",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "eModulo",
        init : function(){
            fn_bactividades.fillgrid();
            $('.num').keydown(fn_solotrucking.inputnumero());  
            //Filtrado con la tecla enter
            $(fn_bactividades.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_bactividades.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_bactividades.filtraInformacion();
                }
            }); 
        },
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones_bitacora_actividades.php", 
                data:{
                    accion:"get_bitacora",
                    registros_por_pagina : "15", 
                    pagina_actual : fn_bactividades.pagina_actual, 
                    filtroInformacion : fn_bactividades.filtro,  
                    ordenInformacion : fn_bactividades.orden,
                    sortInformacion : fn_bactividades.sort,
                },
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_bactividades.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_bactividades.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_bactividades.data_grid+" tbody tr:odd").addClass('white');
                    $(fn_bactividades.data_grid + " tfoot #paginas_total").val(data.total);
                    $(fn_bactividades.data_grid + " tfoot #pagina_actual").val(data.pagina);
                    fn_bactividades.pagina_actual = data.pagina; 
                }
            }); 
        },
        firstPage : function(){
            if($(fn_bactividades.data_grid+" #pagina_actual").val() != "1"){
                fn_bactividades.pagina_actual = "";
                fn_bactividades.fillgrid();
            }
        },
        previousPage : function(){
            if($(fn_bactividades.data_grid+" #pagina_actual").val() != "1"){
                fn_bactividades.pagina_actual = (parseInt($(fn_bactividades.data_grid+" #pagina_actual").val()) - 1) + "";
                fn_bactividades.fillgrid();
            }
        },
        nextPage : function(){
            if($(fn_bactividades.data_grid+" #pagina_actual").val() != $(fn_bactividades.data_grid+" #paginas_total").val()){
                fn_bactividades.pagina_actual = (parseInt($(fn_bactividades.data_grid+" #pagina_actual").val()) + 1) + "";
                fn_bactividades.fillgrid();
            }
        },
        lastPage : function(){
            if($(fn_bactividades.data_grid+" #pagina_actual").val() != $(fn_bactividades.data_grid+" #paginas_total").val()){
                fn_bactividades.pagina_actual = $(fn_bactividades.data_grid+" #paginas_total").val();
                fn_bactividades.fillgrid();
            }
        }, 
        ordenamiento : function(campo,objeto){
            $(fn_bactividades.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_bactividades.orden){
                if(fn_bactividades.sort == "ASC"){
                    fn_bactividades.sort = "DESC";
                    $(fn_bactividades.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_bactividades.sort = "ASC";
                    $(fn_bactividades.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_bactividades.sort = "ASC";
                fn_bactividades.orden = campo;
                $(fn_bactividades.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
            }
            fn_bactividades.fillgrid();

            return false;
        }, 
        filtraInformacion : function(){
            fn_bactividades.pagina_actual = 0;
            fn_bactividades.filtro = "";
            if($(fn_bactividades.data_grid+" .flt_id").val() != ""){ fn_bactividades.filtro += "iConsecutivo|"+$(fn_bactividades.data_grid+" .flt_id").val()+","}
            if($(fn_bactividades.data_grid+" .flt_titulo").val() != ""){ fn_bactividades.filtro += "sTitulo|"+$(fn_bactividades.data_grid+" .flt_titulo").val()+","} 
            if($(fn_bactividades.data_grid+" .flt_desc").val() != ""){ fn_bactividades.filtro += "sDescripcion|"+$(fn_bactividades.data_grid+" .flt_desc").val()+","} 
            if($(fn_bactividades.data_grid+" .flt_comentarios").val() != ""){ fn_bactividades.filtro += "sComentarios|"+$(fn_bactividades.data_grid+" .flt_comentarios").val()+","}  
            if($(fn_bactividades.data_grid+" .flt_modulo").val() != ""){ fn_bactividades.filtro += "eModulo|"+$(fn_bactividades.data_grid+" .flt_modulo").val()+","} 
            if($(fn_bactividades.data_grid+" .flt_fechasol").val() != ""){ fn_bactividades.filtro += "dFechaSolicitud|"+$(fn_bactividades.data_grid+" .flt_fechasol").val()+","} 
            if($(fn_bactividades.data_grid+" .flt_status").val() != ""){ fn_bactividades.filtro += "eEstatus|"+$(fn_bactividades.data_grid+" .flt_status").val()+","} 
            if($(fn_bactividades.data_grid+" .flt_fechaent").val() != ""){ fn_bactividades.filtro += "dFechaEntrega|"+$(fn_bactividades.data_grid+" .flt_fechaent").val()+","} 
   
            fn_bactividades.fillgrid();
        },  
               
}    

 
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_actividades" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
            <h2>BROKERS OF SOLO-TRUCKING INSURANCE</h2>
        </div>
        <table id="data_grid_actividades" class="data_grid">
        <thead id="grid-head2">
            <tr id="grid-head1">
                <td style="width:50px!important;"><input class="flt_id" type="text" placeholder="ID:"></td> 
                <td><input class="flt_modulo" type="text" placeholder="Module:"></td> 
                <td><input class="flt_titulo" type="text" placeholder="Title:"></td>
                <td><input class="flt_desc" type="text" placeholder="Description:"></td>
                <td><input class="flt_comentarios" type="text" placeholder="Comments:"></td> 
                <td><input class="flt_fechasol" type="text" placeholder="MM/DD/YY:"></td> 
                <td>
                    <select class="flt_status">
                        <option value="ANALISIS">ANALISIS</option>
                        <option value="DESARROLLO">DESARROLLO</option> 
                        <option value="PRUEBAS">PRUEBAS</option>  
                        <option value="TERMINADO">TERMINADO</option> 
                    </select>
                </td> 
                <td><input class="flt_fechaent" type="text" placeholder="MM/DD/YY:"></td>   
                <td style='width:80px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_bactividades.filtraInformacion();"><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid" onclick="fn_bactividades.ordenamiento('iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_bactividades.ordenamiento('eModulo',this.cellIndex);">MODULE</td> 
                <td class="etiqueta_grid"      onclick="fn_bactividades.ordenamiento('sTitulo',this.cellIndex);">TITLE</td> 
                <td class="etiqueta_grid"      onclick="fn_bactividades.ordenamiento('sDescripcion',this.cellIndex);">DESCRIPTION</td>
                <td class="etiqueta_grid"      onclick="fn_bactividades.ordenamiento('sComentarios',this.cellIndex);">COMMENTS</td> 
                <td class="etiqueta_grid"      onclick="fn_bactividades.ordenamiento('dFechaSolicitud',this.cellIndex);">APPLICATION</td>
                <td class="etiqueta_grid"      onclick="fn_bactividades.ordenamiento('eEstatus',this.cellIndex);">STATUS</td> 
                <td class="etiqueta_grid"      onclick="fn_bactividades.ordenamiento('dFechaEntrega',this.cellIndex);">FINISHED</td> 
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
                        <button id="pgn-inicio"    onclick="fn_bactividades.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_bactividades.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_bactividades.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_bactividades.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>   
    </div>
</div>

<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>