<h1>Signup</h1>


<form method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

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