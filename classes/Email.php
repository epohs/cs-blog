<?php



use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;






/**
 * https://github.com/ActiveCampaign/postmark-php
 *
 */
class Email {
  
  
  
  
  public static function send(string $email_template, array $vars): bool {
    
    
    $Config = Config::get_instance();

    
    $server_token = $Config->get('POSTMARK_SERVER_TOKEN');
    
    $sender = $Config->get('POSTMARK_SENDER_SIGNATURE');
    





    if (
        !$Config->get('send_email') ||
        !$server_token ||
        !$sender
       ):

      return false;

    endif;
    
    
    $template_path = ROOT_PATH . "admin/email-templates/{$email_template}.html";
    
    
    $body_html = self::render_template($template_path, $vars);
    
    if ( $body_html ):
      
      $body_plain = self::make_plain($body_html);
      
    else:
      
      return false;
      
    endif;
    
    
    try {
      
      $Client = new PostmarkClient( $server_token );
      
      
      $send_result = $Client->sendEmail(
        $sender,
        $vars['to'],
        $vars['subject'],
        $body_html,
        $body_plain
      );
    
      // Getting the MessageID from the response
      $postmark_msg_id = $send_result->MessageID;
    
    } catch(PostmarkException $ex) {
      
      // If the client is able to communicate with the API in a timely fashion,
      // but the message data is invalid, or there's a server error,
      // a PostmarkException can be thrown.
      debug_log("Postmark exception on '{$email_template}': {$ex->postmarkApiErrorCode}.");
      debug_log($ex->message);
      
      return false;
    
    } catch(Exception $generalException) {
      
      // A general exception is thrown if the API
      // was unreachable or times out.
      debug_log("Unexpected error when sending email using '{$email_template}':");
      debug_log($generalException->getMessage());
      
      return false;
    }
    
    
    return true;
    
    
  } // send()
  
  
  
  
  
  
  
  
  
  
  
  private static function render_template(string $template_path, array $variables): false|string {
    
    
    if ( !file_exists($template_path) ):

      return false;
    
    endif;
    
    
    // Read the HTML template
    $template = file_get_contents($template_path);
    
    // Replace variables using a regex callback
    return preg_replace_callback(
      
      '/\{\{\s*([\w\d_]+)\s*\}\}/',
        
      function ($matches) use ($variables) {
        
        $key = $matches[1];
        
        // Replace with value or empty string if not set
        return $variables[$key] ?? ''; 
        
      },
        
      $template
        
    );
    
  } // render_template()
  
  
  
  
  
  
  
  
  private static function make_plain(string $html): string {
    
    $text = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    
    // Replace block-level tags with newlines
    $text = preg_replace('/<\/(p|div|h[1-6]|li|br|tr)>/i', "\n", $text);
    
    // Strip remaining HTML tags
    $text = strip_tags($text);
    
    // Replace multiple newlines with a single newline
    $text = preg_replace("/\n+/", "\n", $text);
    
    return trim($text);
    
  } // make_plain()
  
  
  
  
  
  
  
  
  

    
} // ::Email
