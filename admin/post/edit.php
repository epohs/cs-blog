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
      
      
      <form id="EditPostForm" method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">
      
        <input type="hidden" name="form_name" value="edit-post">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
        <input type="hidden" name="selector" value="<?php echo $post['selector']; ?>">
        
        
        <label for="PostTitle">Title</label>
        <input type="text" name="title" value="<?php echo $post['title']; ?>" id="PostTitle">
        
        <input type="hidden" name="content" id="PostContent">
        <div id="editor"></div>
        
        <button type="submit">Submit</button>
        
      </form>
    
      
      <script>

        var content = `<?php echo addslashes($post['content']); ?>`;

        var form = document.getElementById('EditPostForm');
        var form_post_content = document.getElementById('PostContent');

        const editor = new toastui.Editor({
          el: document.querySelector('#editor'),
          height: 'auto',
          initialValue: content,
          initialEditType: 'wysiwyg'
        });


        form.addEventListener('submit', function() {

          form_post_content.value = editor.getMarkdown();

        });

      </script>
      
      <hr>
      RAW Content: <?php echo var_export($post['content'], true); ?>
      <hr>
      
    </main>
    

    <?php $Page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $Page->get_partial('page-footer', null, false, 'admin/partials'); ?>

</body>
</html>
