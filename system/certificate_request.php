<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <!---- Fancybox -------->
    <script type="text/javascript" src="../fancybox/lib/jquery.mousewheel-3.0.6.pack.js"></script>
    <script type="text/javascript" src="../fancybox/source/jquery.fancybox.js"></script>
    <link rel="stylesheet" type="text/css" href="../fancybox/source/jquery.fancybox.css" media="screen">
     <script type="text/javascript" src="../fancybox/fancy.js"></script>
     
<script type="text/javascript">                 
function llenadoGrid(){      
        var fn_request_certificate = {
        domroot:"#fn_request_certificate",
        data_grid: "#data_grid_certificate",
        fillgrid: function(){
               $.ajax({             
                type:"POST", 
                url:"funciones.php", 
                data:{accion:"get_request_certificate"},
                async : true,
                dataType : "json",
                success : function(data){                               
                    $(fn_UsersClients.data_grid+" tbody").empty().append(data.tabla);
                    $(fn_UsersClients.data_grid+" tbody tr:even").addClass('gray');
                    $(fn_UsersClients.data_grid+" tbody tr:odd").addClass('white');
                                }
            }); 
        }    
    }
    fn_request_certificate.fillgrid();
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
    <div id="fn_request_certificate" class="container">
        <h2>Catalogs - Client Users</h2>
        <table id="data_grid_certificate" class="data_grid">
        <thead id="grid-head2">
            <tr>
                <td class="etiqueta_grid">Insured Name</td>
                <td class="etiqueta_grid">E-mail</td>
                <td class="etiqueta_grid">Certificate Holder</td>
                <td class="etiqueta_grid">Description of Operations / Locations / Vehicles / Additional Remarks</td>
                <td class="etiqueta_grid">Status</td>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
        </table>
        
    </div>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
