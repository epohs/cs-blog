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

<?php $page->get_partial('html-head', 'post'); ?>

<body>
  

  

<div class="page-wrap">

  
  <?php $page->get_partial('page-header'); ?>
  

  <main class="content">
    
    <?php $page->get_partial('page-alerts'); ?>
  
    <h1>Test post page</h1>
    
    
    
    <form id="testForm" action="/post" method="post">
      
      <input id="testContent" value="<?php echo $pc; ?>" type="hidden" name="content">
      <trix-editor input="testContent"></trix-editor>
      
      <button type="submit">Submit</button>
    </form>
    
    
    
    <h2>Submitted form</h2>
    
    <div class="output" style="white-space: pre-line;"><?php echo var_export($pc, true); ?></div>
    
    
    
    <h2>Markdown</h2>
    
    <div id="mdo" class="output" style="white-space: pre-line;"><?php echo var_export($mc, true); ?></div>
    
  </main>


</div>





</body>
</html>
