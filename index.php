<?php

include('functions.php');

$page = Page::get_instance();


?>
<!DOCTYPE html>
<html lang="en">

<?php $page->get_partial('html-head'); ?>

<body>

<h1>Hello.</h1>

<p><?= date("F j, Y, g:i a"); ?></p>

</body>
</html>
