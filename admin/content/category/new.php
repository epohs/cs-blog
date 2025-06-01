

<h1>New Category</h1>


<form method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="new-category">
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
  
  
  <label for="CategoryName">Name</label>
  <input type="text" name="name" value="" id="CategoryName">
  
  <label for="CategoryDescription">Brief description</label>
  <textarea name="name" id="CategoryDescription"></textarea>
  
  <button type="submit">Submit</button>
  
</form>