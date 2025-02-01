<!DOCTYPE html>
<html lang="en">

<?php $Page->get_partial('html-head', null, false, 'admin/partials'); ?>

<body>
  

  
<?php $Page->get_partial('page-header', null, false, 'admin/partials'); ?>
  

<div class="page-wrap">

  <div class="page-body">

    <?php $Page->get_partial('primary', null, false, 'admin/partials/sidebar'); ?>
    

    <main class="content">
      
      <?php $Page->get_partial('page-alerts', null, false, 'admin/partials'); ?>
    
      <h1>I forgot my password</h1>
  
      <form method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">
      
        <input type="hidden" name="form_name" value="forgot">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
        
        <label for="Email">Email:</label>
        <input type="text" id="Email" name="email" autocapitalize="off" required>
        <br>
        
        <button type="submit">Reset password</button>
      
      </form>
      
    </main> <!-- .content -->


    <?php $Page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $Page->get_partial('page-footer', null, false, 'admin/partials'); ?>



</body>
</html>
