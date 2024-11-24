<header class="page-header">

  
  <?php if ( $page->is_logged_in() ): ?>

    <?php if ( !$User->is_verified() ): ?>
      <a href="<?php echo $page->url_for('verify') ?>">Verify</a><br>
    <?php endif; ?>

    <a href="<?php echo $page->url_for('logout') ?>">logout</a> (<?php echo Session::get_key(['user', 'selector']); ?>)<br>
    <a href="<?php echo $page->url_for('profile') ?>">profile</a>

  <?php else: ?>

    <a href="<?php echo $page->url_for('login') ?>">login</a><br>

  <?php endif; ?>
  
  
</header>