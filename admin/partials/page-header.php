<header class="page-header">

  
  <?php if ( $User->is_logged_in() ): ?>

    <?php if ( !$User->is_verified() ): ?>
      <a href="<?php echo $Page->url_for('verify') ?>">Verify</a><br>
    <?php endif; ?>

    <a href="<?php echo $Page->url_for('logout') ?>">logout</a> (<?php echo Session::get_key(['user', 'selector']); ?>)<br>
    <a href="<?php echo $Page->url_for('profile') ?>">profile</a>

  <?php else: ?>

    <a href="<?php echo $Page->url_for('login') ?>">login</a><br>

  <?php endif; ?>
  
  
</header>