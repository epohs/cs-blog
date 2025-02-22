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
    
      <h1>Verify your email address</h1>
      
      <p>A one time code was sent to the email address you used to sign up. Enter
      that code below to activate your account.</p>
    
      <form method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

        <input type="hidden" name="form_name" value="verify">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
        
        <label for="VerifiyKey">One time code:</label>
        <input type="text" id="VerifyKey" name="verify_key" value="<?php echo $verify_key; ?>" required>
        
        <button type="submit">Verify</button>

      </form>
      
    </main> <!-- .content -->


    <?php $Page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $Page->get_partial('page-footer', null, false, 'admin/partials'); ?>



</body>
</html>