<?php

/*  MySQL configuration. Database must be created first, obviously. */
define( 'DB_HOST', 'localhost' );
define( 'DB_NAME', 'riverwire' );
define( 'DB_USER', 'database_username' );
define( 'DB_PASS', 'database_pass' );

/*  API Information. Visit the Guardian and NY Times for specifics. */
$guardian_api_key = '';
$nyt_api_key = '';

/*  Extras configuration. Setting these loads files from extras/ */
$google_analytics_id = NULL; /* UA-XXXXXXXX-X */
$github_fork_display = 1; /* NULL to avoid displaying GitHub fork banner */

?>