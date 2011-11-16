<?php

/*
 * Feed River Wire
 * @author Jeremy Felt <jeremy.felt@gmail.com>
 * @license MIT License - see license.txt
 */

/*  MySQL configuration. Database must be created first, obviously.
    SQL file is included to help build. */
define( 'DB_HOST', 'localhost' );
define( 'DB_NAME', 'riverwire' );
define( 'DB_USER', 'database_username' );
define( 'DB_PASS', 'database_pass' );

/*  API Information. Visit the Guardian UK Open Platform and NY Times API sites for specifics. */
$guardian_api_key = '';
$nyt_api_key = '';

/*  Other config info. */
$site_title = 'Feed River Wire';
$script_max_run_time = 3420; // Scripts will run for 3420 seconds by default.
$river_source_ids = '2,3,4'; // Default source IDs. We'll handle this better.

/*  Extras configuration. Setting these loads files from extras/ */
$google_analytics_id = NULL; /* UA-XXXXXXXX-X */
$github_fork_display = 1; /* NULL to avoid displaying GitHub fork banner */

?>