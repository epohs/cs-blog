<!DOCTYPE html>
<html lang="en">

<?php $Page->get_partial('html-head', null, false, 'admin/partials'); ?>

<body>

  

<?php $Page->get_partial('page-header', null, false, 'admin/partials'); ?>
  

<div class="page-wrap">

  <div class="page-body">

    <?php $Page->get_partial('primary', null, false, 'admin/partials/sidebar'); ?>
  

    <main class="content">
      
      <?php $Page->get_partial('page-alerts', null, false, 'admin/partials'); ?>
    
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

      
    </main>
    

    <?php $Page->get_partial('secondary', null, false, 'admin/partials/sidebar'); ?>
    

  </div> <!-- .page-body -->


</div> <!-- .page-wrap -->


<?php $Page->get_partial('page-footer', null, false, 'admin/partials'); ?>

</body>
</html>
