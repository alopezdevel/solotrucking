<?php $codigo = $_GET['cuser']; ?>
<?php include("libs_header.php"); ?>
<script type="text/javascript">   
function confirmarUser(){
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
    var code = $.get("cuser");
    if(code != ""){
        $.post("funciones_users.php", { accion: "confirm_user", code: code},
        function(data){ 
             switch(data.error){
             case "0": $("#correct").show();
                    break;
             case "1":  
                       $("#error").show();
                                             
                    break;  
             case "2":
                       $("#error").show();
                       break;
             }
        },"json");   
    }else{
        window.location = "http://www.solo-trucking.com/";
    } 
}
$(document).ready(confirmarUser);
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
  <div id="correct" class="container txt-center" style='display:none;text-align:center;'>
        <img src="/system/images/nav/img-logo.png" border="0" alt="img-logo.png (6,517 bytes)">
        <h2 style="text-align:center;">Registration Completed!</h2>
        <p>Hello <strong id="usarname"></strong>,</p>
        <p>Thanks for signing in the Solo-Trucking Insurance System. Now you can make your requests and track them more easily and quickly.</p>
        <p>To continue with this procedure you MUST fill out a form with your data and so this'll be part of our database.</p>
        <br><br>
        <p><a href="login.php" class="btn_4">Continue</a></p>
  </div>
  
  
  <div id="error" class="container txt-center" style='display:none;text-align:center;'>
        <img src="/system/images/nav/img-logo.png" border="0" alt="img-logo.png (6,517 bytes)">
        <h2 style="text-align:center;">User certification code is invalid</h2>
        <p>This operation cannot be accepted. User certification is invalid or expired </p>
        <br><br>
        <p><a href="login.php" class="btn_4">Continue</a></p>
  </div>
</body>

</html>
