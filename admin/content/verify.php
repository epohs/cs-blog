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