<h1>Edit User</h1>

<?php /*

<form id="EditPostForm" method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="edit-post">
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
  <input type="hidden" name="selector" value="<?php echo $post['selector']; ?>">
  
  
  <label for="PostTitle">Title</label>
  <input type="text" name="title" value="<?php echo $post['title']; ?>" id="PostTitle">
  
  <textarea name="content" id="my-text-area"><?php echo $post['content']; ?></textarea>
  
  <button type="submit">Edit post</button>
  
</form>

*/ ?>


<div style="white-space: pre-line;"><?php echo var_export($user, true); ?></div>



<hr>


<form id="DeleteUserForm" method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="delete-user">
  <input type="hidden" name="selector" value="<?php echo $user['selector']; ?>">
  <input type="hidden" name="nonce" value="<?php echo $nonce_delete; ?>">
  <button type="submit">Delete user</button>

</form>