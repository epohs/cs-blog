<div class="sidebar-primary">

  <nav class="nav">
    
    <ul class="nav-items">

      <li class="nav-item"><a href="<?php echo $page->url_for('/') ?>" class="nav-link">Home</a></li>

      <?php if ( $page->is_admin() ): ?>

        <li class="nav-item"><a href="<?php echo $page->url_for('admin/dash') ?>" class="nav-link">Admin</a></li>
        
      <?php endif; ?>

    </ul>

  </nav>

</div>