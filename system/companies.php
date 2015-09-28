<?php session_start();    
if ( !($_SESSION["acceso"] == 'U'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
    header("Location: login.php");
    exit;
}else{ ?>
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 

<link rel="stylesheet" href="/../../../code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="/../../../code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="/js/jquery.blockUI.js" type="text/javascript"></script>
<script type="text/javascript"> 
$(document).ready(inicio);

function inicio(){  
        $.blockUI();
        var usuario_actual = <?php echo json_encode($_SESSION['usuario_actual']);?>        
        var tipo_usuario = <?php echo json_encode($_SESSION['acceso']);?> 
        validapantalla(usuario_actual);
        if(tipo_usuario == "C"){
            validarLoginCliente(usuario_actual);
        }  
        $("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); }); 
        llenadoGrid();  
        $.unblockUI();
    
}  

function validapantalla(usuario){
        
        if(usuario == ""  || usuario == null){
            location.href= "login.php";
        }
        
    }              
function llenadoGrid(){      
    var fn_companies = {
        domroot:"#ct_companies",
        data_grid: "#data_grid_companies",
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones.php", 
                data:{accion:"get_companies"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_companies.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_companies.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_companies.data_grid+" tbody tr:odd").addClass('white');
                }
            }); 
        }    
    }
    fn_companies.fillgrid();    
}
 
</script> 
<!---- HEADER ----->
<?php include("header.php"); ?> 
<script> 
$(document).ready(inicio);
function inicio(){   

    llenadoGrid();  
}
</script>
<div id="layer_content" class="main-section">
    <div id="ct_companies" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
            <h2>Insured Companies</h2>
        </div>
        <table id="data_grid_companies" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td><input id="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input id="flt_name" type="text" placeholder="Name:"></td>
                <td><input id="flt_email" type="text" placeholder="E-mail:"></td>
                <td><input id="flt_address" type="text" placeholder="Address:"></td> 
                <td><input id="flt_country" type="text" placeholder="Country:"></td>
                <td><input id="flt_zip" type="text" placeholder="Zip Code:"></td> 
                <td><input id="flt_phone" type="text" placeholder="Phone(s):"></td> 
                <td><input id="flt_usdot" type="text" placeholder="USDOT:"></td> 
                <td style='width:70px;'>
                    <div class="btnicon btn-left" title="Search" onclick=""><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid">ID</td>
                <td class="etiqueta_grid">Name</td>
                <td class="etiqueta_grid">E-mail</td>
                <td class="etiqueta_grid">Address</td>
                <td class="etiqueta_grid">Country</td>
                <td class="etiqueta_grid">Zip Code</td> 
                <td class="etiqueta_grid">Phone(s)</td>
                <td class="etiqueta_grid">USDOT</td>
                <td class="etiqueta_grid"></td> 
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
            <tr>
                <td colspan="100%"></td>
            </tr>
        </tfoot>
        </table>
        
    </div>
</div>
<div id="dialog-confirm" title="Delete">
  <p><span class="ui-icon ui-icon-alert" ></span>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
<?php } ?>