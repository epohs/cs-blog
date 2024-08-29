<?php if ( $page->has_errors() ): ?>
  
  <?php $errors = $page->get_errors(); ?>
  
  <ol class="errors">
  
  <?php foreach ( $errors as $err ): ?>
    
    <li class="error <?php echo $err['level']; ?>"><?php echo $err['msg']; ?></li>
    
  <?php endforeach; ?>
    
  </ol>
  
<?php endif; ?>