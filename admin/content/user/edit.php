<h1>Edit User</h1>

<h2>
  <?php echo $User->get_display_name($user['id']); ?>
  <?php if ( $user['display_name'] ): ?>
    <span class="user-selector">(@<?php echo $user['selector']; ?>)</span>
  <?php endif; ?>
</h2>

<ul class="user-meta">
  <li class="user-meta-item"><strong>Member Since:</strong> <?php echo Utils::format_date($user['created_at'], 'M j, Y'); ?></li>
    <li class="user-meta-item"><strong>Last Login:</strong> 
      <?php
      if ( $user['last_login'] ):
      
        echo Utils::format_date($user['last_login'], 'M j, Y'); 
      
      else:
        
        echo 'Never';
        
      endif;
      ?>
    </li>
</ul>

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

<ol>
  <li>Update: email, display name, role</li>
  <li>Trigger password reset</li>
  <li>Ban user</li>
  <li>Time out: 1 hour, 12 hours, 1 day, 1 week, indefinite</li>
</ol>


<div style="white-space: pre-line;"><?php echo var_export($user, true); ?></div>



<hr>


<form id="DeleteUserForm" method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="delete-user">
  <input type="hidden" name="selector" value="<?php echo $user['selector']; ?>">
  <input type="hidden" name="nonce" value="<?php echo $nonce_delete; ?>">
  <button type="submit">Delete user</button>

</form>