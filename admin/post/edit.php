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
    
      <h1>Edit Post</h1>
      
      
      <form method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">
      
        <input type="hidden" name="form_name" value="edit-post">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
        <input type="hidden" name="selector" value="<?php echo $post['selector']; ?>">
        
        
        <label for="PostTitle">Title</label>
        <input type="text" name="title" value="<?php echo $post['title']; ?>" id="PostTitle">
        
        <input id="PostContent" type="hidden" name="content" value="<?php echo $post['content']; ?>" id="PostContent">
        <trix-editor input="PostContent"></trix-editor>
        
        <button type="submit">Submit</button>
        
      </form>
      
    </main>


    <?php $Page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $Page->get_partial('page-footer', null, false, 'admin/partials'); ?>

</body>
</html>
