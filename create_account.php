<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, maximum-scale=1">

<title>SoloTrucking - Commercial Truck Insurance</title>
<link rel="icon" href="images/favicon.png" type="image/png">
<link rel="shortcut icon" href="favicon.ico" type="img/x-icon">
<link href="css/bootstrap.css" rel="stylesheet" type="text/css">
<link href="css/style.css" rel="stylesheet" type="text/css">
<link href="css/font-awesome.css" rel="stylesheet" type="text/css">
<link href="css/responsive.css" rel="stylesheet" type="text/css">
<link href="css/animate.css" rel="stylesheet" type="text/css">

<script type="text/javascript" src="js/jquery.1.8.3.min.js"></script>
<script type="text/javascript" src="js/bootstrap.js"></script>
<script type="text/javascript" src="js/jquery-scrolltofixed.js"></script>
<script type="text/javascript" src="js/jquery.easing.1.3.js"></script>
<script type="text/javascript" src="js/jquery.isotope.js"></script>
<script type="text/javascript" src="js/wow.js"></script>
<script type="text/javascript" src="js/classie.js"></script>

	<!-----SLIDER HOMEPAGE----->
	<link rel='stylesheet' id='camera-css'  href='camera/css/camera.css' type='text/css' media='all'>     
    <script type='text/javascript' src='camera/scripts/jquery.min.js'></script>
    <script type='text/javascript' src='camera/scripts/jquery.mobile.customized.min.js'></script>
    <script type='text/javascript' src='camera/scripts/jquery.easing.1.3.js'></script> 
    <script type='text/javascript' src='camera/scripts/camera.js'></script> 
    <script type='text/javascript' src='camera/scripts/script.js'></script>
</head>
<body>
<script src="./code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script type="text/javascript">
$(document).ready(inicio);
function inicio(){
    //variable 
    mensaje = $( ".mensaje_valido" );
    $("#btn_register").click(onInsertarUsuario);
    //focus
    $("#name").focus(onFocus);
    $("#email").focus(onFocus);
    $("#password").focus(onFocus);
    $("#recapturapassword").focus(onFocus);
    //blur
    $("#name").blur(onBlur);
    $("#email").blur(onBlur);
    $("#password").blur(onBlur);
    $("#recapturapassword").blur(onBlur);
}
function onInsertarUsuario(){
    //Variables
    var emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    var name = $("#name");
    var email = $("#email");
    var password = $("#password");
    var re_password = $("#recapturapassword");
    todosloscampos = $( [] ).add( name ).add( email ).add( password ).add( re_password );
    todosloscampos.removeClass( "error" );
    
    
    $("#name").focus().css("background-color","#FFFFC0");
    actualizarMensajeAlerta( "" );     
    
    //validaciones
    var valid = true;
    
    //tamano
    valid = valid && checkLength( name, "Company name", 5, 25 );
    valid = valid && checkRegexp( name, /^[a-z]([0-9a-z_\s])+$/i, "Company name of a-z, 0-9, underscores, spaces and must begin with a letter." );
    
    valid = valid && checkLength( email, "E-mail", 6, 80 );
    valid = valid && checkRegexp( email, emailRegex, "eg. ui@solotrucking.com" );
    
    valid = valid && checkLength( password, "password", 6, 25 );
    valid = valid && checkRegexp( password, /^([0-9a-zA-Z])+$/, "Password field only allow : a-z 0-9" );
    
    valid = valid && checkLength( re_password, "password", 6, 25 );
    valid = valid && checkRegexp( re_password, /^([0-9a-zA-Z])+$/, "Password field only allow : a-z 0-9" );
    
    if(password.val() != re_password.val() && valid){
        actualizarMensajeAlerta( "thats not the same password as the first one" );
        re_password.addClass( "error" );
        re_password.focus();
        valid = false;
    }
    
    //exp
    
    
    
    
 
    
    if ( valid ) {
        $.post("system/funciones.php", { accion: "alta_usuario", name: name.val() , email: email.val(), password: password.val(), nivel: "C"},
        function(data){ 
             switch(data.error){
             case "1":   actualizarMensajeAlerta( data.mensaje);
                         $("#email").focus();
                         email.addClass( "error" ); 
                    break;
             case "0":   actualizarMensajeAlerta("All form fields are required.");
                         $("#newuserinput").val("");
                         $("#name").focus();
                         //alert("Thank you. The user has been successfully registered.");
                         //actualizarMensajeAlerta("Thank you.the user has been successfully registered.Please check the email you to confirm your account registration.");
                         $("#newuser").fadeOut('fast');
                         $(".msg-success").fadeIn('fast');
                    break;  
             }
         }
         ,"json"); 
    }          
   
}

function onFocus(){$(this).css("background-color","#FFFFC0");}
function onBlur(){$(this).css("background-color","#FFFFFF");}
function actualizarMensajeAlerta( t ) {
      mensaje
        .text( t )
        .addClass( "alertmessage" );
      setTimeout(function() {
        mensaje.removeClass( "alertmessage", 2500 );
      }, 700 );
}
function checkRegexp( o, regexp, n ) {
    if ( !( regexp.test( o.val() ) ) ) {
        actualizarMensajeAlerta( n );
        o.addClass( "error" );
        o.focus();
        return false;
    } else {                     
        return true;        
    }
}
function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
        actualizarMensajeAlerta( "Length of " + n + " must be between " + min + " and " + max + "."  );
        o.addClass( "error" );
        o.focus();
        return false;    
    } else {             
        return true;                     
    }                    
}
</script>

<div style="overflow:hidden;" id="home">
<nav class="main-nav-outer" id="test"><!--main-nav-start-->
	<div class="container">
		<a href="./#home" class="img-logo">
			<img  src="images/nav/img-logo.png" alt="logo">
		</a>
		<ul class="top-nav">
			<li><a href="system/login" class="login-btn" title="Log In" target="_blank"><span>My Account</span></a></li>
			<li><a href="https://www.facebook.com/solotrucking" class="icon facebook" title="follow us in Facebook!" target="_blank"><span></span></a></li>
			<li style="display:none"><a href="https://www.youtube.com/" class="icon youtube" title="follow us in YouTube!" target="_blank"><span></span></a></li>
			<li><a href="https://twitter.com/" class="icon twitter" title="follow us in Twitter!" target="_blank"><span></span></a></li>
			<li><a href="https://www.google.com/" class="icon google" title="follow us in Google +!" target="_blank"><span></span></a></li>
		</ul>
        <ul class="main-nav">
        	<li><a href="./#home">Home</a></li>
            <li><a href="aboutus">About Us</a></li>
            <li><a href="products">Products</a></li>
            <li><a href="system/login" target="_blank" style="display: none;">Claims</a></li>
            <li><a href="providers">Providers</a></li>
            <li class="active"><a href="quotes">Get a Quote</a></li>
            <li><a href="#contact">Contact Us</a></li>
        </ul>
        <a class="res-nav_click right" href="#"><i class="fa-bars"></i></a>
    </div>
</nav><!--main-nav-end-->
<!--- HEADER----->
<div class="header section-quotes">
	<h1 class="wow animated fadeInLeft delay-02s">Get a Quote</h1>
	<h3 class="wow animated fadeInRight delay-03s">Let's Get Started!</h3>
</div>
<!--- TERMINA HEADER----->
<section class="main-section "><!--main-section-start-->
	<div class="container">
    	<h2>How Can We Help You?</h2>
    	<h6>There are only a few steps that we will walk you through:</h6>
    	<form id="newuser" method="post" action="">
            <p class="mensaje_valido">&nbsp;All form fields are required.</p>
			<input  id = "name"   name="name" type="text" placeholder="Company Name:">
			<input  id = "email"name="email" type="email" placeholder="E-mail:">
			<input  id = "password"name="password" type="password" placeholder="Password:">
			<input  id = "recapturapassword"name="recapturapassword" type="password" placeholder="Repeat the Password:">
			<button id = "btn_register" class="btn_register btn_4" type="button">Register</button>
		</form>
    	<div class="msg-success">
			<p class="txt-center">Thank you, once you send an email so you can confirm your account.
			<br><br><a href="./" class="btn_2">Back to Homepage</a>
			</p>
		</div>
    </div>
</section><!--main-section-end-->
<div class="footer-line"></div>
<footer class="footer">
    <div class="container">
    	 <div class="nav_foot">
    	 	<a href="./">Home</a>    /    
    	 	<a href="aboutus">About Us</a>    /    
    	 	<a href="products">Products</a>     /    
    	 	<a href="system/login" target="_blank" style="display: none;">Claims</a>     /    
    	 	<a href="providers">Providers</a>     /    
    	 	<a href="quotes" class="active">Get a Quote</a>    
    	 	<a href="./#contact">Contact Us</a>    
    	 </div>
    	 <div class="nav_foot_2">
    	 <a href="terms_conditions">Terms & Conditions</a>  |   
    	 <a href="privacypolicy">Privacy Policy</a>  |   
    	 <a href="faq">F.A.Q.</a></div>    
   	</div>
</footer>
<div class="copyright">SoloTrucking 2015 . © All rights reserved.</div>

<script type="text/javascript">
    $(document).ready(function(e) {
        $('#test').scrollToFixed();
        $('.res-nav_click').click(function(){
            $('.main-nav').slideToggle();
            return false    
            
        });
        
    });
</script>

  <script>
    wow = new WOW(
      {
        animateClass: 'animated',
        offset:       100
      }
    );
    wow.init();
    $('#top').onclick = function() {
      var section = document.createElement('section');
      section.className = 'wow fadeInDown';
      this.parentNode.insertBefore(section, this);
    };
  </script>


<script type="text/javascript">
	$(window).load(function(){
		
		$('a').bind('click',function(event){
			var $anchor = $(this);
			
			$('html, body').stop().animate({
				scrollTop: $($anchor.attr('href')).offset().top - 80
			}, 1500,'easeInOutExpo');
			/*
			if you don't want to use the easing effects:
			$('html, body').stop().animate({
				scrollTop: $($anchor.attr('href')).offset().top
			}, 1000);
			*/
			event.preventDefault();
		});
	})
</script>

<script type="text/javascript">

$(window).load(function(){
  
  
  var $container = $('.portfolioContainer'),
      $body = $('body'),
      colW = 375,
      columns = null;

  
  $container.isotope({
    // disable window resizing
    resizable: true,
    masonry: {
      columnWidth: colW
    }
  });
  
  $(window).smartresize(function(){
    // check if columns has changed
    var currentColumns = Math.floor( ( $body.width() -30 ) / colW );
    if ( currentColumns !== columns ) {
      // set new column count
      columns = currentColumns;
      // apply width to container manually, then trigger relayout
      $container.width( columns * colW )
        .isotope('reLayout');
    }
    
  }).smartresize(); // trigger resize to set container width
  $('.portfolioFilter a').click(function(){
        $('.portfolioFilter .current').removeClass('current');
        $(this).addClass('current');
 
        var selector = $(this).attr('data-filter');
        $container.isotope({
			
            filter: selector,
         });
         return false;
    });
  
});

</script>
</body>
</html>