<h1>Admin Profile</h1>

<p>Logged in as <strong><?php echo $cur_user['email']; ?></strong> (<?php echo $cur_user['selector']; ?>)</p>

<p>Your IP: <?php echo Utils::get_client_ip(); ?></p>

<details><summary>Session:</summary> <?php echo var_export($_SESSION, true); ?></details>