<h1>All Posts</h1>


<?php if ( $posts ): ?>

  <ol>

  <?php foreach ( $posts as $post ): ?>

    <li><a href="<?php echo $Page->url_for("admin/post/edit/{$post['selector']}") ?>"><?php echo $post['title']; ?></a></li>

  <?php endforeach; ?>

  </ol>

<?php else: ?>

  <p>No posts to list</p>

<?php endif; ?>
