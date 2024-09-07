<!DOCTYPE html>
<html lang="en">

<?php $page->get_partial('html-head'); ?>

<body>
  
<?php $page->get_partial('errors'); ?>
  
  
<h1>Non-admin profile page.</h1>

<p>RAW Session: <?= var_export($_SESSION, true); ?></p>

</body>
</html>
