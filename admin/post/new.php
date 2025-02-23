<?php

if ( isset($_POST['content']) ):

  $pc = htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8');
  $mc = htmlspecialchars($converter->convert($_POST['content']), ENT_QUOTES, 'UTF-8');
  
else:
  
  $pc = 'no posted data';
  $mc = '';
  
endif;

?>
<!DOCTYPE html>
<html lang="en">

<?php $Page->get_partial('html-head', 'post', false, 'admin/partials'); ?>

<body>

  

<?php $Page->get_partial('page-header', null, false, 'admin/partials'); ?>
  

<div class="page-wrap">

  <div class="page-body">

    <?php $Page->get_partial('primary', null, false, 'admin/partials/sidebar'); ?>
  

  <main class="content">
    
    <?php $Page->get_partial('page-alerts', null, false, 'admin/partials'); ?>
  
    <h1>Test post page</h1>
    
    
    
    <form id="testForm" action="<?php echo $Page->url_for('admin/post/new'); ?>" method="post">
      
      <input id="testContent" value="<?php echo $pc; ?>" type="hidden" name="content">
      <trix-editor input="testContent"></trix-editor>
      
      <button type="submit">Submit</button>
    </form>
    
    
    
    <h2>Submitted form</h2>
    
    <div class="output" style="white-space: pre-line;"><?php echo var_export($pc, true); ?></div>
    
    
    
    <h2>Markdown</h2>
    
    <div id="mdo" class="output" style="white-space: pre-line;"><?php echo var_export($mc, true); ?></div>
    
  </main>


    <?php $Page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $Page->get_partial('page-footer', null, false, 'admin/partials'); ?>

</body>
</html>
