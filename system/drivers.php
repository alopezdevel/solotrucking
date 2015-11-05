<?php session_start();    
if ( !($_SESSION["acceso"] == 'C'  && $_SESSION["usuario_actual"] != "" && $_SESSION["usuario_actual"] != NULL  )  ){ //No ha iniciado session, redirecciona a la pagina de login
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
        //$("#aUpdateAccount").click(function() { actualizarCliente(usuario_actual); }); 
        llenadoGrid();  
        $.unblockUI();
    
}  

function validapantalla(usuario){
        
        if(usuario == ""  || usuario == null){
            location.href= "login.php";
        }
        
}              
function llenadoGrid(){      
    var fn_drivers = {
        domroot:"#ct_drivers",
        data_grid: "#data_grid_drivers",
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones.php", 
                data:{accion:"get_drivers"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_drivers.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_drivers.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_drivers.data_grid+" tbody tr:odd").addClass('white');
                }
            }); 
        }    
    }
    fn_drivers.fillgrid();    
}
function validarLoginCliente(usuario){
        //$.blockUI({ message: $('#domMessage') });
        $.post("funciones.php", { accion: "validar_cliente_acceso", usuario: usuario},
        function(data){ 
                                // 
         if(data.error == "0"){  
             switch(data.estatus){                                                                                                
                case "0":  
                                    //location.href= "login.php";
                                    break;
                                    
                case "1":           $.unblockUI();                          
                                    codigo_1 = data.codigo.substring(0, 10);
                                    codigo_2 = data.codigo.substring(5, 15);
                                    total_len = data.consecutivo.length;
                                    location.href= "company_register.php?ref="+ total_len + '_'  +  codigo_1 +  data.consecutivo;                                    
                                    break;
                case "2":          $.unblockUI();                          
                                    /*codigo_1 = data.codigo.substring(0, 10);
                                    codigo_2 = data.codigo.substring(5, 15);
                                    total_len = data.consecutivo.length;
                                    location.href= "company_register.php?ref="+ total_len + '_'  +  codigo_1 +  data.consecutivo;  */
                                    break;
                 
             }
         }else{
             //error
         }   
         
     }
     ,"json");
        
        
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
    <div id="ct_drivers" class="container">
        <div class="page-title">
            <h1>Catalogs</h1>
            <h2>Drivers</h2>
        </div>
        <table id="data_grid_drivers" class="data_grid">
        <thead>
            <tr id="grid-head1">
                <td style='width:45px;'><input id="flt_id" class="numeros" type="text" placeholder="ID:"></td>
                <td><input id="flt_name" type="text" placeholder="Name:"></td>
                <td><input id="flt_dob" type="text" placeholder="Date of Birth:"></td>
                <td><input id="flt_license" type="text" placeholder="License Number:"></td>
                <td><input id="flt_expirationdate" type="text" placeholder="Expiration Date:"></td> 
                <td><input id="flt_state" type="text" placeholder="State:"></td>
                <td><input id="flt_YOE" type="text" placeholder="Zip Code:"></td> 
                <td><input id="flt_DOH" type="text" placeholder="Phone(s):"></td>  
                <td style='width:50px;'>
                    <div class="btn-icon-2 btn-left" title="Search" onclick=""><i class="fa fa-search"></i></div>
                </td> 
            </tr>
            <tr id="grid-head2">
                <td class="etiqueta_grid">ID</td>
                <td class="etiqueta_grid">Name</td>
                <td class="etiqueta_grid">DOB</td>
                <td class="etiqueta_grid">License Number</td>
                <td class="etiqueta_grid">Expiration Date</td>
                <td class="etiqueta_grid">State</td>
                <td class="etiqueta_grid">Years of Experience</td> 
                <td class="etiqueta_grid">Date of Hire</td>
                <td class="etiqueta_grid">Documents</td>
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