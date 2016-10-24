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
        fn_claims.init();
        fn_claims.fillgrid();
        $.unblockUI();
    
}  
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}  }                   
var fn_claims = {
        domroot:"#ct_claims",
        data_grid: "#data_grid",
        filtro : "",
        pagina_actual : "",
        sort : "ASC",
        orden : "A.iConsecutivo",
        init : function(){
            /*---- plugins y de mas ...*/
            $('.num').keydown(fn_solotrucking.inputnumero); 
            $('.decimals').keydown(fn_solotrucking.inputdecimals);
            $('.hora').mask('00:00:00');
            
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
                    //fn_claims.view();
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
                if($(fn_claims.data_grid+" .flt_id").val() != ""){ fn_claims.filtro += "A.iConsecutivo|"+$(fn_claims.data_grid+" .flt_id").val()+","}
                if($(fn_claims.data_grid+" .flt_policynumber").val() != ""){ fn_claims.filtro += "B.sNumeroPoliza|"+$(fn_claims.data_grid+" .flt_policynumber").val()+","} 
                if($(fn_claims.data_grid+" .flt_type").val() != ""){ fn_claims.filtro += "C.sNombre|"+$(fn_claims.data_grid+" .flt_type").val()+","}  
                if($(fn_claims.data_grid+" .flt_incident").val() != ""){ fn_claims.filtro += "D.sNombre|"+$(fn_claims.data_grid+" .flt_incident").val()+","} 
                if($(fn_claims.data_grid+" .flt_dateIncident").val() != ""){ fn_claims.filtro += "dFechaHoraIncidente|"+$(fn_claims.data_grid+" .flt_dateIncident").val()+","} 
                if($(fn_claims.data_grid+" .flt_dateAplication").val() != ""){ fn_claims.filtro += "dFechaAplicacion|"+$(fn_claims.data_grid+" .flt_dateAplication").val()+","}    
            
                fn_claims.fillgrid();
       },
       add : function(){
          $('#edit_form :text, #edit_form select').val('').removeClass('error');
          $('#edit_form .mensaje_valido').empty().append('The fields containing an (<span style="color:#ff0000;">*</span>) are required.');
          $('#edit_form .p-header h2').empty().append('CLAIMS - NEW APPLICATION');
          fn_solotrucking.get_date(".fecha"); 
          fn_popups.resaltar_ventana('edit_form'); 
       }
              
}    

 
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_claims" class="container">
        <div class="page-title">
            <h1>CLAIMS</h1>
            <h2 style="margin-bottom: 5px;">CLAIMS APPLICATIONS</h2>
        </div>
        <table id="data_grid" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style="width:50px!important;"><input class="flt_id" type="text" placeholder="ID:"></td> 
                <td><input class="flt_policynumber" type="text" placeholder="Policy Numer:"></td>
                <td><input class="flt_type" type="text" placeholder="Type:"></td> 
                <td><input class="flt_incident" type="text" placeholder="Incident:"></td> 
                <td><input class="flt_dateIncident" type="text" placeholder="MM-DD-YY"></td> 
                <td><input class="flt_dateAplication" type="text" placeholder="MM-DD-YY"></td>  
                <td style='width:120px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick="fn_claims.filtraInformacion();"><i class="fa fa-search"></i></div>
                    <div class="btn-icon-2 btn-left" title="Add +"  onclick="fn_claims.add();"><i class="fa fa-plus"></i></div>  
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_claims.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td> 
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('B.sNumeroPoliza',this.cellIndex);">Policy Number</td>
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('C.sNombre',this.cellIndex);">TYPE</td>
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('D.sNombre',this.cellIndex);">INCIDENT</td>
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('dFechaHoraIncidente',this.cellIndex);">DATE-HOUR OF INCIDENT </td> 
                <td class="etiqueta_grid"      onclick="fn_claims.ordenamiento('dFechaAplicacion',this.cellIndex);">DATE-HOUR OF APLICATION</td> 
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
<!---- FORMULARIOS ------>
<div id="edit_form" class="popup-form">
    <div class="p-header">
        <h2>CLAIMS</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <p class="mensaje_valido">&nbsp;The fields containing an (<span style="color:#ff0000;">*</span>) are required.</p> 
    <div>
        <form>
            <table>
             <tr>
             <td>
                <div class="field_item"> 
                    <label style="margin-left:15px;">select the policies in which you want to apply your claim <span style="color:#ff0000;">*</span>:</label> 
                    <div class="company_policies" style="padding: 10px 10px 10px 23px;"></div>
                </div>
             </td>
             </tr>
            </table>
            <fieldset>
                <legend>INFORMATION FROM INCIDENT</legend>
                <table style="width: 100%;">
                <tr>
                    <td>
                    <div class="field_item"> 
                        <label style="margin-left:15px;">Date: <span style="color:#ff0000;">*</span>:</label> 
                        <input tabindex="1" id="dFechaIncidente" type="text" class="fecha" style="width: 80%;position: relative;margin-left:15px;">
                    </div>
                    </td>
                    <td>
                    <div class="field_item"> 
                        <label style="margin-left:15px;">Hour: <span style="color:#ff0000;">*</span>:</label> 
                        <input tabindex="1" id="dHoraIncidente" type="text" class="hora" style="width: 80%;position: relative;margin-left:15px;" title="Please capture the hour in 24/h format">
                    </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <div class="field_item"> 
                        <label style="margin-left:15px;">What was involved in the incident? <span style="color:#ff0000;">*</span>:</label> 
                        <select tabindex="1" id="">
                            <option value="">Select an opction...</option>
                            <option value="D">A Driver</option>
                            <option value="U">A Trailer/Unit</option> 
                            <option value="DU">Both</option>
                        </select>
                    </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <table class="popup-datagrid">
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%"></td></tr></tbody>
                    </table>
                    </td>
                </tr>
                <tr>
                    <td>
                    <div class="field_item"> 
                        <label style="margin-left:15px;">Type of Claim: <span style="color:#ff0000;">*</span>:</label> 
                        <select tabindex="1" id="iConsecutivoTipoClaim"><option value="">Select an opction...</option></select>
                    </div>
                    </td>
                    <td>
                    <div class="field_item"> 
                        <label style="margin-left:15px;">What happend? <span style="color:#ff0000;">*</span>:</label> 
                        <select tabindex="1" id="iConsecutivoIncidente"><option value="">Select an opction...</option></select>
                    </div>
                    </td>
                </tr>
                <tr>
                    <td>
                    <div class="field_item"> 
                        <label style="margin-left:15px;">Where State?: <span style="color:#ff0000;">*</span>:</label> 
                        <select tabindex="1" id="sEstado"><option value="">Select an opction...</option></select>
                    </div>
                    </td>
                    <td>
                    <div class="field_item"> 
                        <label style="margin-left:15px;">What City?:</label> 
                        <input tabindex="1" id="sCiudad" type="text">
                    </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <div class="file_certificate files"> 
                        <label><span style="color:#9e2e2e;">Please upload the pictures in JPG format.</span></label> 
                        <input  id="txtFile" type="text" readonly="readonly" value="" size="40" style="width:85%;" />
                        <button id="btnFile" type="button">Upload File</button>
                        <input  id="iTokenTmp" type="hidden">
                    </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <table class="popup-datagrid">
                        <thead>
                            <tr id="grid-head2">
                                <td class="etiqueta_grid">File Name</td>
                                <td class="etiqueta_grid">Size</td>
                                <td class="etiqueta_grid"></td>
                            </tr>
                        </thead>
                        <tbody><tr><td style="text-align:center; font-weight: bold;" colspan="100%">No data available.</td></tr></tbody>
                    </table>
                    </td>
                </tr>
                </table>
            </fieldset>
            <br>  
            <button type="button" class="btn-1" onclick="fn_claims.save();">SAVE</button>
            <button type="button" class="btn-1" onclick="fn_popups.cerrar_ventana('edit_form');" style="margin-right:10px;background:#e8051b;">CLOSE</button>
        </form> 
    </div>
    </div>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>