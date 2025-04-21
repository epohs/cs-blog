<div class="sidebar-primary">

  <nav class="nav">
    
    <ul class="nav-items">

      <li class="nav-item"><a href="<?php echo $Page->url_for('/') ?>" class="nav-link">Home</a></li>

      <?php if ( $User->is_admin() || $User->is_author() ): ?>

        <li class="nav-item"><a href="<?php echo $Page->url_for('admin/dash') ?>" class="nav-link">Admin</a></li>
        
      <?php endif; ?>

    </ul>

  </nav>

</div>