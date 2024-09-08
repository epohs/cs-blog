<!DOCTYPE html>
<html lang="en">

<?php $page->get_partial('html-head', null, false, 'admin/partials'); ?>

<body>
  

  

<div class="page-wrap">

  
  <?php $page->get_partial('page-header', null, false, 'admin/partials'); ?>
  

  <main class="content">
    
    <?php $page->get_partial('errors', null, false, 'admin/partials'); ?>
  
    <h1>Login</h1>
  
    <form method="POST" action="<?php echo $page->url_for('admin/form-handler'); ?>">
    
      <input type="hidden" name="form_name" value="login">
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
      
      <label for="Email">Email:</label>
      <input type="text" id="Email" name="email" autocapitalize="off" required>
      <br>
      <label for="Password">Password:</label>
      <input type="password" id="Password" name="password" required>
      
      <button type="submit">Sign in</button>
    
    </form>
    
  </main>


</div>





</body>
</html>
