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
    
      <h1>Login</h1>
  
      <form method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">
      
        <input type="hidden" name="form_name" value="login">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
        
        <label for="Email">Email:</label>
        <input type="text" id="Email" name="email" autocapitalize="off" required>
        <br>
        <label for="Password">Password:</label>
        <input type="password" id="Password" name="password" required>
        <br>
        
        <input type="checkbox" id="RememberMe" name="remember_me" value="1" tabindex="0">
        <label for="RememberMe">Remember me</label>

        <br>
        <button type="submit" tabindex="0">Sign in</button>

        <p><a href ="<?php echo $Page->url_for('forgot'); ?>">I forgot my password</a>.</p>

        <p>Don't have an account? <a href ="<?php echo $Page->url_for('signup'); ?>">Sign up</a>.</p>
      
      </form>
      
    </main> <!-- .content -->


    <?php $Page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $Page->get_partial('page-footer', null, false, 'admin/partials'); ?>



</body>
</html>
