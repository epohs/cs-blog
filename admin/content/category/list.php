<h1>Categories</h1>

<ul>
  <li><a href="<?php echo $Page->url_for('admin/category/new') ?>">New Category</a></li>
</ul>

<?php if ( $categories ): ?>

  <ol>

  <?php foreach ( $categories as $category ): ?>

    <li>
      
      <a href="<?php echo $Page->url_for("admin/category/edit/{$category['selector']}"); ?>"><?php echo $category['name']; ?></a>
      
    </li>

  <?php endforeach; ?>

  </ol>

<?php else: ?>

  <p>No categories to list.</p>
  
  <p>By default Posts with no Category will not appear in the public blog roll. If you don't want to use Categories, you can enable displaying uncategorized Posts by setting the '<strong>show_uncategorized_posts</strong>' setting to true in your config.php.</p>
  
  <p>Otherwise, <a href="<?php echo $Page->url_for('admin/category/new') ?>">create a new Category</a> now.</p>

<?php endif; ?>
