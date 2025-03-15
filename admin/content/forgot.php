<h1>I forgot my password</h1>

<form method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="forgot">
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
  
  <label for="Email">Email:</label>
  <input type="text" id="Email" name="email" autocapitalize="off" required>
  <br>
  
  <button type="submit">Reset password</button>

</form>