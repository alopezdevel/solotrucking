<?php
  $codigo = $_GET['cuser'];   
?>
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script src="/../../../code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script>
$(document).ready(inicio);
function inicio(){
    var code = $.get("cuser");
    $.get = function(key)   {  
        key = key.replace(/[\[]/, '\\[');  
        key = key.replace(/[\]]/, '\\]');  
        var pattern = "[\\?&]" + key + "=([^&#]*)";  
        var regex = new RegExp(pattern);  
        var url = unescape(window.location.href);  
        var results = regex.exec(url);  
        if (results === null) {  
            return null;  
        } else {  
            return results[1];  
        }  
    }  
    confirmarUser(code);
    
}
function confirmarUser(code){
     $.post("funciones.php", { accion: "confirm_user", code: code },
        function(data){ 
             switch(data.error){
             case "1":  alert(data.mensaje);
                    break;
             case "0":  
                        alert(data.mensaje);
                    break;  
             }
         }
         ,"json");
}
</script>
<!DOCTYPE>
<html>

<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title>SoloTrucking - Internal Control System</title>
<link rel="stylesheet" href="css/style_system.css" type="text/css">
<link rel="shortcut icon" href="images/favicon.png" type="img/x-icon">
</head>

<body>
  <div class="container txt-center">
        <img src="/system/images/nav/img-logo.png" border="0" alt="img-logo.png (6,517 bytes)">
        <h1>Registration Completed!</h1>
        <p>Hello <strong id="usarname"></strong>,</p>
        <p>Thanks for signing in the Control System Single-Trucking. Now you can make your requests and track them more easily and quickly.</p>
        <p>To continue with this procedure You MUST fill out a form with your data and so this'll be part of our database.</p>
        <br /><br />
        <p><a href="login.php" class="btn_4">Continue</a></p>
  </div>
</body>

</html>
