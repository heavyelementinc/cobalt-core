<?php
namespace Mail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SendMail{
  var $SMTP_host    =  __APP_SETTINGS__["Mail_smtp_host" ];
  var $SMTP_auth    =  __APP_SETTINGS__["Mail_smtp_auth"];
  var $SMTP_opts    =  __APP_SETTINGS__['Mail_SMTP_options'];
  var $username     =  __APP_SETTINGS__["Mail_username"];
  var $password     =  __APP_SETTINGS__["Mail_password"];
  var $port         =  __APP_SETTINGS__["Mail_port"];
  
  var $from_address       =  __APP_SETTINGS__["Mail_from_address"];
  var $from_address_name  =  __APP_SETTINGS__["Mail_from_name"];
  var $reply_to_address   =  __APP_SETTINGS__["Mail_reply_to_address"];
  var $reply_to_name      =  __APP_SETTINGS__["Mail_reply_to_name"];
  var $is_HTML            =  true;



  function __construct(){
    $this->renderer   = new \Render\Render();

    $this->mail = new PHPMailer();
    $this->mail->isSMTP();                                      // Send using SMTP
    $this->mail->Host       = $this->SMTP_host;                 // Set the SMTP server to send through
    $this->mail->SMTPAuth   = $this->SMTP_auth;                 // Enable SMTP authentication
    if(!empty($this->SMTP_opts)) $this->mail->SMTPOptions = $this->SMTP_opts;
    // if(app('debug')) $this->mail->SMTPDebug = 1;
    $this->mail->Username   = $this->username;                  // SMTP username
    $this->mail->Password   = $this->password;                  // SMTP password
    $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $this->mail->Port       = $this->port;                      // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
    $this->mail->setFrom($this->from_address, $this->from_address_name);
    $this->mail->addReplyTo($this->reply_to_address, $this->reply_to_name);
  }

  function set_from($address){
    $address = trim($address);
    if(!filter_var($address,FILTER_VALIDATE_EMAIL)) throw new \Exception("Invalid email address");
    $this->from_address = $address;
  }

  function set_body(string $body){
    $this->renderer->set_body($body);
  }

  function set_body_template(string $template){
    $this->renderer->from_template($template);
  }

  function set_vars($vars){
    if(!is_array($vars) && !is_object($vars)) throw new \Exception("Vars must be an array or object");
    $this->renderer->set_vars($vars);
  }

  function send($to,$subject,$cc = [],$bcc = []){
    $body = $this->renderer->execute();
    
    $this->mail->isHTML(true);

    if(\is_string($to)) $to = [$to];
    $this->mail->addAddress(...$to);
    
    $this->mail->Subject = $subject;
    $this->mail->Body = $body;
    if(!$this->mail->send()) throw new Exception("Mail not sent!");
  }
}