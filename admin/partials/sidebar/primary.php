<div class="sidebar-primary">

  <nav class="nav">
    
    <ul class="nav-items">
      
      <li class="nav-item"><a href="<?php echo $Page->url_for('/') ?>" class="nav-link">Home</a></li>
      
      <?php if ( $User->is_admin() ): ?>
  
        <li class="nav-item"><a href="<?php echo $Page->url_for('admin/dash') ?>" class="nav-link">Dashboard</a></li>
        
        <li class="nav-item"><a href="<?php echo $Page->url_for('admin/post/new') ?>" class="nav-link">New post</a></li>
        <li class="nav-item"><a href="<?php echo $Page->url_for('admin/post/list') ?>" class="nav-link">All Posts</a></li>
        <li class="nav-item"><a href="<?php echo $Page->url_for('admin/user/list') ?>" class="nav-link">All Users</a></li>
      
      <?php endif; ?>
      
    </ul>

  </nav>

</div>