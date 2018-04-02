<?php session_start();    
if ( !($_SESSION["acceso"] == '2'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ 
    //No ha iniciado session, redirecciona a la pagina de login
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
    fn_certificate.init();
    fn_certificate.fillgrid();  
    $.unblockUI(); 
    
    $('#dialog_delete_certificate_co').dialog({
            modal: true,
            autoOpen: false,
            width : 300,
            height : 200,
            resizable : false,
            buttons : {
                'YES' : function() {
                    clave = $('#id_certificate_co').val();
                    $(this).dialog('close');
                    
                    fn_certificate.delete_certificate(clave);             
                },
                 'NO' : function(){
                    $(this).dialog('close');
                }
            }
    });  
} 
function validapantalla(usuario){if(usuario == ""  || usuario == null){location.href= "login.php";}}              
var fn_certificate = {
    domroot:"#ct_certificate",
    data_grid: "#data_grid_certificates",
    filtro : "",
    pagina_actual : "",
    sort : "ASC",
    orden : "A.iConsecutivo",
    init : function(){
            $('.num').keydown(fn_solotrucking.inputnumero); 
            $('.decimals').keydown(fn_solotrucking.inputdecimals);
            //Filtrado con la tecla enter
            $(fn_certificate.data_grid + ' #grid-head1 input').keyup(function(event){
                if (event.keyCode == '13') {
                    event.preventDefault();
                    fn_certificate.filtraInformacion();
                }
                if(event.keyCode == '27'){
                   event.preventDefault();
                   $(this).val(''); 
                   fn_certificate.filtraInformacion();
                }
            });   
    },
    fillgrid: function(){
           $.ajax({             
            type:"POST", 
            url:"funciones_certificates.php", 
            data:{
                accion:"get_company_certificate",
                registros_por_pagina : "15", 
                pagina_actual : fn_certificate.pagina_actual, 
                filtroInformacion : fn_certificate.filtro,  
                ordenInformacion : fn_certificate.orden,
                sortInformacion : fn_certificate.sort,
            },
            async : true,
            dataType : "json",
            success : function(data){                               
                $(fn_certificate.data_grid+" tbody").empty().append(data.tabla);
                $(fn_certificate.data_grid+" tbody tr:even").addClass('gray');
                $(fn_certificate.data_grid+" tbody tr:odd").addClass('white');
                $(fn_certificate.data_grid + " tfoot #paginas_total").val(data.total);
                $(fn_certificate.data_grid + " tfoot #pagina_actual").val(data.pagina);
                fn_certificate.pagina_actual = data.pagina;
                fn_certificate.edit(); 
                fn_certificate.delete_confirm();
                fn_certificate.send_email();
                
                if(data.certificate_info != ""){
                    $(fn_certificate.domroot+" .page-title").append(data.certificate_info);
                }
            }
        }); 
    },
    add: function(){
       $.ajax({             
                type:"POST", 
                url:"funciones_certificates.php", 
                data:{accion:"find_certificate"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    if(data.error == '0'){
                        $("#certificate_edit_form input, #certificate_edit_form textarea ").val('');
                        $("#certificate_edit_form #iConsecutivoCertificate").val(data.certificate_id);
                        $('#certificate_edit_form #email').removeClass('readonly').removeAttr('readonly');
                        fn_popups.resaltar_ventana('certificate_edit_form'); 
                    }else{
                      fn_solotrucking.mensaje(data.msj);  
                    }
                }
       }); 
    }, 
    save: function(){
      //Validate Fields:
      var emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
      var iConsecutivoCert = $('#certificate_edit_form #iConsecutivoCertificate');
      todosloscampos = $('#certificate_edit_form input, #certificate_edit_form textarea');
      todosloscampos.removeClass( "error" );
      var valid = true; 
      
      //Validate the email data:
      var email = $('#certificate_edit_form #email');
      valid = valid && fn_solotrucking.checkRegexp(email,emailRegex, "eg. ui@solotrucking.com");
      if(!valid){return false;}
      
      //Validate the Certificate Cholder:
      if($('#certificate_edit_form .cholder input').val() == ''){
          fn_solotrucking.actualizarMensajeAlerta("Please check that all information is complete to generate de certificate.");
          return false;
      }
      
      if(valid){
         if($('#certificate_edit_form #iConsecutivo').val() != ''){struct_data_post.edit_mode = "true";}else{struct_data_post.edit_mode = "false";}         
             struct_data_post.action="save_certificate";
             struct_data_post.domroot= "#certificate_edit_form"; 
                $.post("funciones_certificates.php",struct_data_post.parse(),
                function(data){
                    switch(data.error){
                     case '0':
                        fn_solotrucking.mensaje(data.msj);
                        fn_certificate.fillgrid();
                        fn_popups.cerrar_ventana('certificate_edit_form');
                     break;
                     case '1': fn_solotrucking.mensaje(data.msj); break;
                    }
         },"json"); 
      } 
      
       
    }, 
    firstPage : function(){
            if($(fn_certificate.data_grid+" #pagina_actual").val() != "1"){
                fn_certificate.pagina_actual = "";
                fn_certificate.fillgrid();
            }
        },
    previousPage : function(){
            if($(fn_certificate.data_grid+" #pagina_actual").val() != "1"){
                fn_certificate.pagina_actual = (parseInt($(fn_certificate.data_grid+" #pagina_actual").val()) - 1) + "";
                fn_certificate.fillgrid();
            }
        },
    nextPage : function(){
            if($(fn_certificate.data_grid+" #pagina_actual").val() != $(fn_certificate.data_grid+" #paginas_total").val()){
                fn_certificate.pagina_actual = (parseInt($(fn_certificate.data_grid+" #pagina_actual").val()) + 1) + "";
                fn_certificate.fillgrid();
            }
        },
    lastPage : function(){
            if($(fn_certificate.data_grid+" #pagina_actual").val() != $(fn_certificate.data_grid+" #paginas_total").val()){
                fn_certificate.pagina_actual = $(fn_certificate.data_grid+" #paginas_total").val();
                fn_certificate.fillgrid();
            }
        }, 
    ordenamiento : function(campo,objeto){
            $(fn_certificate.data_grid + " #grid-head2 td").removeClass('down').removeClass('up');

            if(campo == fn_certificate.orden){
                if(fn_certificate.sort == "ASC"){
                    fn_certificate.sort = "DESC";
                    $(fn_certificate.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('up');
                }else{
                    fn_certificate.sort = "ASC";
                    $(fn_certificate.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
                }
            }else{
                fn_certificate.sort = "ASC";
                fn_certificate.orden = campo;
                $(fn_certificate.data_grid + " #grid-head2 td:eq("+objeto+")").addClass('down');
            }
            fn_certificate.fillgrid();

            return false;
        }, 
    filtraInformacion : function(){
            fn_certificate.pagina_actual = 0;
            fn_certificate.filtro = "";
            if($(fn_certificate.data_grid+" .flt_id").val() != ""){ fn_certificate.filtro += "A.iConsecutivo|"+$(fn_certificate.data_grid+" .flt_id").val()+","}
            if($(fn_certificate.data_grid+" .flt_email").val() != ""){ fn_certificate.filtro += "email|"+$(fn_certificate.data_grid+" .flt_email").val()+","} 
            if($(fn_certificate.data_grid+" .flt_estatus").val() != ""){ fn_certificate.filtro += "sCholderA|"+$(fn_certificate.data_grid+" .flt_estatus").val()+","} 
            if($(fn_certificate.data_grid+" .flt_date").val() != ""){ fn_certificate.filtro += "dFechaIngreso|"+$(fn_certificate.data_grid+" .flt_date").val()+","} 
            fn_certificate.fillgrid();
   }, 
    edit: function(){
        $(fn_certificate.data_grid + " tbody td .edit").bind("click",function(){
                var clave = $(this).parent().parent().find("td:eq(0)").html();
                $.post("funciones_certificates.php",
                {
                    accion:"get_certificate_data", 
                    clave: clave, 
                    domroot : "certificate_edit_form"
                },
                function(data){
                    if(data.error == '0'){
                       $('#certificate_edit_form input, #certificate_edit_form textarea').val('').removeClass('error'); 
                       eval(data.fields); 
                       $('#certificate_edit_form #sDescription').val(data.descripcion);
                       $('#certificate_edit_form #email').addClass('readonly').attr('readonly','readonly');
                       fn_popups.resaltar_ventana('certificate_edit_form');   
                    }else{
                       fn_solotrucking.mensaje(data.msj);  
                    }       
        },"json");
        });
    },
    send_email: function(){
        $(fn_certificate.data_grid + " tbody .btn_send_email").bind("click",function(){
               var clave = $(this).parent().parent().find("td:eq(0)").html();
               msg = "<p style=\"text-align:center;\">Sending certificate by e-mail, please wait....<br><img src=\"images/ajax-loader.gif\" alt=\"ajax-loader.gif\" style=\"margin-top:10px;\"><br></p>";
               $('#Wait').empty().append(msg).dialog('open');
               $.post("funciones_certificates.php",{accion:"send_email_gmail", clave: clave},
                function(data){ 
                    $('#Wait').empty().dialog('close');
                    fn_solotrucking.mensaje(data.msj);
                    if(data.error == '0'){fn_certificate.fillgrid();}     
                },"json");
        });  
    },
    delete_confirm : function(){
      $(fn_certificate.data_grid + " tbody .btn_delete").bind("click",function(){
           var clave = $(this).parent().parent().find("td:eq(0)").html();
           $('#dialog_delete_certificate_co #id_certificate_co').val(clave);
           $('#dialog_delete_certificate_co').dialog( 'open' );
           return false;
       });  
    },
    delete_certificate : function(id){
      $.post("funciones_certificates.php",{accion:"delete_certificate", 'clave': id},
       function(data){
            fn_solotrucking.mensaje(data.msj);
            fn_certificate.fillgrid();
       },"json");  
    },
      
}  
function onAbrirDownloadAdd(id){window.open('download_certificate_add.php?cve='+id,'_blank');}  
</script> 
<div id="layer_content" class="main-section">
    <div id="ct_certificate" class="container">
        <div class="page-title">
            <h1>CERTIFICATES</h1>
            <h2>Certificate Layouts</h2>
        </div>
        <table id="data_grid_certificates" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'>
                <input class="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input class="flt_email" type="text" placeholder="E-mail:"></td> 
                <td><input class="flt_estatus" type="text" placeholder="Customer:"></td>  
                <td><input class="flt_date" type="text" placeholder="Income Date:"></td>
                <td style='width:160px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick=""><i class="fa fa-search"></i></div>
                    <div class="new btn-icon-2 btn-left" title="New Certificate +"  onclick="fn_certificate.add();"><i class="fa fa-plus"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid down" onclick="fn_certificate.ordenamiento('A.iConsecutivo',this.cellIndex);">ID</td>
                <td class="etiqueta_grid"      onclick="fn_certificate.ordenamiento('email',this.cellIndex);">E-mail</td> 
                <td class="etiqueta_grid"      onclick="fn_certificate.ordenamiento('sCholderA',this.cellIndex);">Customer </td>
                <td class="etiqueta_grid"      onclick="fn_certificate.ordenamiento('dFechaIngreso',this.cellIndex);">Income Date</td>
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
                        <button id="pgn-inicio"    onclick="fn_certificate.firstPage();" title="First page"><span></span></button>
                        <button id="pgn-anterior"  onclick="fn_certificate.previousPage();" title="Previous"><span></span></button>
                        <button id="pgn-siguiente" onclick="fn_certificate.nextPage();" title="Next"><span></span></button>
                        <button id="pgn-final"     onclick="fn_certificate.lastPage();" title="Last Page"><span></span></button>
                    </div>
                </td>
            </tr>
        </tfoot>
        </table>
        
    </div>
</div>
<!---- FORMULARIOS ------>
<div id="certificate_edit_form" class="popup-form">
    <div class="p-header">
        <h2>CREATE OR EDIT A CERTIFICATE</h2>
        <div class="btn-close" title="Close Window" onclick="fn_popups.cerrar_ventana('certificate_edit_form');"><i class="fa fa-times"></i></div>
    </div>
    <div class="p-container">
    <div>
        <form>
            <fieldset>
                <legend>E-mail Information</legend>
                <input id="iConsecutivoCertificate" name="iConsecutivo" type="hidden">
                <input id="iConsecutivo" name="iConsecutivo" type="hidden"> 
                <p class="mensaje_valido">&nbsp;The fields containing an (*) are required.</p> 
                <div class="field_item">
                    <label>E-mail to send *:</label><input id="email"  type="text" maxlength="55" class="txt-lowercase">
                </div>
                <div class="field_item">
                    <label>Message:</label>
                    <textarea id="sDescription" maxlength="1000"></textarea>
                </div>
                <legend>Certificate Cholder</legend>
                <div class="field_item cholder">
                    <label>Company Name *:</label> 
                    <input id="sCholderA"  type="text" class="txt-uppercase">
                </div>
                <div class="field_item cholder">
                    <label>Address *:</label> 
                    <input id="sCholderB"  type="text" class="txt-uppercase">
                </div>
                <div class="field_item cholder">
                    <label>State *:</label> 
                    <input id="sCholderC"  type="text" class="txt-uppercase">
                </div>
                <div class="field_item cholder">
                    <label>City *:</label> 
                    <input id="sCholderD"  type="text" class="txt-uppercase">
                </div>
                <div class="field_item cholder">
                    <label>Zip Code *:</label> 
                    <input id="sCholderE"  type="text" class="txt-uppercase">
                </div>
            </fieldset>
            <br>
            <button type="button" class="btn-1" onclick="fn_certificate.save();">SAVE</button>
        </form>
    </div>
    </div>
</div>
<div id="dialog_delete_certificate_co" title="SYSTEM ALERT" style="display:none;">
    <p>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
    <form id="elimina" method="post">
           <input type="hidden" name="id_certificate_co" id="id_certificate_co">
    </form>  
</div> 
<!---- FOOTER ----->
<?php include("footer.php"); ?>    
</body>
</html>
<?php } ?>