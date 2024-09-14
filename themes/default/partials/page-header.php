<header class="page-header">
  
  page header<br>
  
  <?php if ( $page->is_logged_in() ): ?>

    <a href="<?php echo $page->url_for('logout') ?>">logout</a> (<?php echo Session::get_key('user_selector'); ?>)<br>

  <?php else: ?>

    <a href="<?php echo $page->url_for('login') ?>">login</a><br>

  <?php endif; ?>
  
</header>