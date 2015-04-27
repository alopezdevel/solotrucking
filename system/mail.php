<?php
session_start();
require('phpmailer/class.phpmailer.php');
#require('lib/phpmailer/class.phpmailer.php');

class Mail extends PHPMailer {
    /**
     * Adds a string or binary attachment (non-filesystem) to the list.
     * This method can be used to attach ascii or binary data,
     * such as a BLOB record from a database.
     * @param string $string String attachment data.
     * @param string $filename Name of the attachment.
     * @param string $encoding File encoding (see $Encoding).
     * @param string $type File extension (MIME) type.
     * @return void
     */
    function AddStringEmbeddedImage($string, $filename, $cid ,$encoding = "base64", 
                                 $type = "application/octet-stream") {
        // Append to $attachment array
        $cur = count($this->attachment);
        $this->attachment[$cur][0] = $string;
        $this->attachment[$cur][1] = $filename;
        $this->attachment[$cur][2] = $filename;
        $this->attachment[$cur][3] = $encoding;
        $this->attachment[$cur][4] = $type;
        $this->attachment[$cur][5] = true; // isString
        $this->attachment[$cur][6] = "inline";
        $this->attachment[$cur][7] = $cid;
    }
}

$from = "support@solotrucking.com";
if ($_SESSION["opcion_FROM_correo"] == '1') {
    $from_name = $_SESSION["nombre_usuario_actual"];
} else {
    $from_name = $_SESSION["texto_FROM_correo"];
}
$host = "solotrucking.com";
$mailer = "sendmail";
?>