<h1>Edit Post</h1>


<form id="EditPostForm" method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="edit-post">
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
  <input type="hidden" name="selector" value="<?php echo $post['selector']; ?>">
  
  
  <label for="PostTitle">Title</label>
  <input type="text" name="title" value="<?php echo $post['title']; ?>" id="PostTitle">
  
  <textarea name="content" id="my-text-area"><?php echo $post['content']; ?></textarea>
  
  <button type="submit">Edit post</button>
  
</form>


<script>

  const easyMDE = new EasyMDE(
    {
      element: document.getElementById('my-text-area'),
      toolbar: [
                'bold',
                'italic',
                'link',
                'heading-2',
                'heading-3',
                "|",
                "unordered-list",
                "ordered-list",
                "quote",
                "|",
                "upload-image",
                "|",
                "preview"
              ],
      minHeight: '30em',
      uploadImage: true,
      imageAccept: 'image/png, image/jpeg',
      imageMaxSize: (1024 * 1024 * 20),
      spellChecker: false,
      status: ["lines", "words"]
    }
  );

</script>

<hr>


<form id="DeletePostForm" method="POST" action="<?php echo $Page->url_for('form-handler'); ?>">

  <input type="hidden" name="form_name" value="delete-post">
  <input type="hidden" name="selector" value="<?php echo $post['selector']; ?>">
  <input type="hidden" name="nonce" value="<?php echo $nonce_delete; ?>">
  <button type="submit">Delete post</button>

</form>