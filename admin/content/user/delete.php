<?php if ( $deleting_myself ): ?>
  
  <h1>You are deleting yourself!</h1>

  <h2>This could be <strong>really</strong> bad.</h2>
  
  <p>Are you absolutely sure that you want to do this?</p>
  
<?php else: ?>

  <h1>Delete User: <?php echo $User->get_display_name($user['id']); ?>?</h1>
  
<?php endif; ?>

<h2>This cannot be undone.</h2>

<p>All posts, and comments made by this user will be permanently deleted.<p>
  
<p>This user has <strong><?php echo $post_count; ?></strong> posts.</p>
  
<p>Another option is banning. Banning this user will also remove their posts and comments from the public site, but copies will remain in the database and can be restored later.</p>

<h2>Really?</h2>

<form id="DeleteUserForm" method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="delete-user">
  <input type="hidden" name="selector" value="<?php echo $user['selector']; ?>">
  <input type="hidden" name="nonce" value="<?php echo $nonce_delete; ?>">
  <input type="hidden" name="nonce_delete_confirm" value="<?php echo $confirm_delete; ?>">
  <button type="submit">Permanently Delete User</button>

</form>