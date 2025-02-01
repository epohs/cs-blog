<?php if ( $Page->has_alerts() ): ?>
  
  <?php $alerts = $Page->get_alerts(); ?>
  
  <ol class="page-alerts">
  
  <?php foreach ( $alerts as $alert ): ?>
    
    <li class="alert alert-level-<?php echo $alert['level']; ?>"><?php echo $alert['text']; ?></li>
    
  <?php endforeach; ?>
    
  </ol>
  
<?php endif; ?>