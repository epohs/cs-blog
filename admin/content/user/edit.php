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


<hr>


<form id="EditUserForm" method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="edit-user">
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
  <input type="hidden" name="selector" value="<?php echo $user['selector']; ?>">
  
  
  <div class="form-row">
    <label for="DisplayName">Display name</label>
    <input type="text" name="display_name" value="<?php echo $user['display_name']; ?>" placeholder="<?php echo $User->get_display_name($user['id']); ?>" id="DisplayName">
  </div>
  
  
  <div class="form-row">
    <label for="EmailAddress">Email Address</label>
    <input type="text" name="email" value="<?php echo $user['email']; ?>" id="EmailAddress">
  </div>
    
  
  <div class="form-row">
    <label for="UserRole">User Role</label>
    <select name="role" id="UserRole">
      <option value="user" <?php echo Utils::is_selected('user', $user['role']); ?>>User</option>
      <option value="author" <?php echo Utils::is_selected('author', $user['role']); ?>>Author</option>
      <option value="admin" <?php echo Utils::is_selected('admin', $user['role']); ?>>Admin</option>
    </select>
  </div>
  
  
  <div class="form-row">

    <label for="LockOut">Temporary Time Out</label>    
    <select name="lock_out" id="LockOut">
      <?php if (
          Utils::is_valid_datetime($user['locked_until']) &&
          Utils::is_future_datetime($user['locked_until']) ): ?>
      <option value="-1">Remove time out</option>
      <?php endif; ?>
      <option value="" selected class="placeholder">None</option>
      <option value="3600">1 hour</option>
      <option value="43200">12 hours</option>
      <option value="86400">1 day</option>
      <option value="604800">1 week</option>
    </select>

  </div>
    
  
  <div class="form-row">
    <input type="checkbox" name="is_banned" value="1" <?php echo Utils::is_checked($user['is_banned']); ?> id="IsBanned">
    <label for="IsBanned">User Banned?</label>
  </div>
  
  <div class="form-row">
    <button type="submit">Update User</button>
  </div>
  
</form>


<div style="white-space: pre-line;"><?php echo var_export($user, true); ?></div>



<hr>


<form id="TriggerPassReset" method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="trigger-pass-reset">
  <input type="hidden" name="selector" value="<?php echo $user['selector']; ?>">
  <input type="hidden" name="nonce" value="<?php echo $nonce_pass_reset; ?>">
  <button type="submit">Send password reset</button>

</form>


<form id="DeleteUserForm" method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="delete-user">
  <input type="hidden" name="selector" value="<?php echo $user['selector']; ?>">
  <input type="hidden" name="nonce" value="<?php echo $nonce_delete; ?>">
  <button type="submit">Delete user</button>

</form>