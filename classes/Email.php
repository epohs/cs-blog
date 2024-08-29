<?php



use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;






/**
 * https://github.com/ActiveCampaign/postmark-php
 *
 */
class Email {

  
  private static $instance = null;
  
  

  
  private function __construct() {

    
      
  } // __construct()
  
  
  
  
  
  
  
  
  
  
  
  
  public function send() {
    
    return 'email disabled.';

    
    $config = Config::get_instance();
    
    $server_token = $config->get('POSTMARK_SERVER_TOKEN');
    
    $sender = $config->get('POSTMARK_SENDER_SIGNATURE');
    
    
    
    try {
      
      $client = new PostmarkClient( $server_token );
      
      
      $sendResult = $client->sendEmail(
        $sender,
        "testing@blurryphoto.com",
        "Hello from Postmark!",
        "This is just a friendly 'hello' from your friends at Postmark."
      );
    
      // Getting the MessageID from the response
      echo $sendResult->MessageID;
    
    } catch(PostmarkException $ex) {
      
      // If the client is able to communicate with the API in a timely fashion,
      // but the message data is invalid, or there's a server error,
      // a PostmarkException can be thrown.
      echo $ex->httpStatusCode;
      echo $ex->message;
      echo $ex->postmarkApiErrorCode;
    
    } catch(Exception $generalException) {
      
      // A general exception is thrown if the API
      // was unreachable or times out.
      
    }
    
    
    
  } // send()
  
  
  
  
  
  
  
  
  
  
  
  
  
  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new self();
    
    endif;
    
  
    return self::$instance;
  
  } // get_instance()
  
  

    
} // ::Email
