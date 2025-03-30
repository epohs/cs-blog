<h1>All Users</h1>


<?php if ( $users ): ?>

  <ol>

  <?php foreach ( $users as $user ): ?>

    <li><a href="<?php echo $Page->url_for("admin/user/edit/{$user['selector']}") ?>"><?php echo $user['selector']; ?></a></li>

  <?php endforeach; ?>

  </ol>

<?php else: ?>

  <p>No users to list</p>

<?php endif; ?>
