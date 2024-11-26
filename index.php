<?php
/**
 * This is the entry point to the entire application.
 * 
 * The server should route all requests for php files here.
 */



include('init.php');


$Routing->serve_route( $request_uri );

