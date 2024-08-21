<?php

// This is important for security.
// If our root path constant is not defined
// that should mean that you're accessing this
// file directly. We don't want that so we end
// the script immediately, without displaying 
// any of our config values.
// For better security this file should be blocked
// by nginx or htaccess.
if ( !defined('ROOT_PATH') ):

  die();
  
endif;

?>
{
  "debug" : true,
  "public" : false,
  "site_name" : "Dad's Blog",
  "site_url" : ""
}