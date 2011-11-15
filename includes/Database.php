<?php

function db_connect(){

    $database_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=UTF-8' , DB_USER,
        DB_PASS , array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" ) );
    $database_connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

    return $database_connection;
}

?>
