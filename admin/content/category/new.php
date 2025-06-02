

<h1>New Category</h1>


<form method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="new-category">
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
  
  
  <label for="CategoryName">Name</label><br>
  <input type="text" name="name" value="" id="CategoryName" style="width: 100%;">
  
  <label for="CategoryDescription">Brief description</label>
  <textarea name="name" id="CategoryDescription" style="display: block; width: 100%;"></textarea>
  
  <button type="submit">Submit</button>
  
</form>