<!DOCTYPE html>
<html lang="en">

<?php $page->get_partial('html-head', null, false, 'admin/partials'); ?>

<body>
  

  
<?php $page->get_partial('page-header', null, false, 'admin/partials'); ?>
  

<div class="page-wrap">

  <div class="page-body">

    <?php $page->get_partial('primary', null, false, 'admin/partials/sidebar'); ?>
    

    <main class="content">
      
      <?php $page->get_partial('errors', null, false, 'admin/partials'); ?>
    
      <h1>Signup</h1>

      Nonce: <?= var_export($nonce, true); ?><br>
      Session raw: <?= var_export($_SESSION, true); ?><br>

      
      <form method="POST" action="<?php echo $page->url_for('admin/form-handler'); ?>">
      
        <input type="hidden" name="form_name" value="signup">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <br>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        
        <br>
        
        <button type="submit">Sign up</button>
      
      </form>
      
    </main> <!-- .content -->


    <?php $page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $page->get_partial('page-footer', null, false, 'admin/partials'); ?>



</body>
</html>