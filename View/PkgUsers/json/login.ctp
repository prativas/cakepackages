{"user": <?php echo empty($userData) ? 'null' : json_encode(array('username' => $userData['username'], 'slug' =>  $userData['slug'])); ?>}