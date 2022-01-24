<?php
    require_once('Database.class.php');
    $db = new Database(true);

    $id = 1;

    $db->base = "cms";
    $db->query = "SELECT * FROM users WHERE id = ?";
    $db->content = [[$id, 'int']];
    $user = $db->selectOne();
?>