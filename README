Feed River Wire
=================

Feed River Wire is an experiment in news flow. As packaged, a river is created with streams of news from The Guardian UK, The New York Times, and Hacker News.

The river updates every 3 seconds to show you new articles that scripts pull down from various locations in the background.

Releasing this code into the wild could make it easier on somebody to create their own personal news river, with custom streams. So, go for it!

Requirements
-------------------

+ I'm using PHP 5.2.10 and MySQL 5.1.37, nothing too complex in the code to cause issues though.
+ cURL is used to grab the feeds.
+ Guardian Open Platform API Key - http://www.guardian.co.uk/open-platform
+ New York Times API Key - http://developer.nytimes.com/

Configuration / Installation
----------------------------------

DIY for sure, but shouldn't be too hard.
+ You'll need to make a **config.php** file based on config.sample.php in includes. This contains the MySQL DB config, API keys, and other general settings.
+ A database needs to be created in MySQL, and the tables in **riverwire.sql** should be added.
+ Cron should be setup to run **catch_guardian_items.php**, **catch_hn_items.php**, and **catch_nyt_items.php** on a regular basis.
    + The regular basis should match up with your **script_max_run_time** setting in config.php.
    + This could technically be infinite (I think), but I run the cron once an hour and set the script to run for 57 minutes. Probably because I'm weird.
+ You **may** need to change the **river_source_ids** setting in **config.php** to match your DB inserts, but I'm working on smoothing that out now.
+ That's it. River is flowing.

Live Example
---------------

A live example of Feed River Wire can be found at http://feedriverwire.com/, always running on the master branch.

Contact
-------

**Jeremy Felt**

+ jeremy.felt@gmail.com
+ http://www.jeremyfelt.com
+ http://twitter.com/jeremyfelt
+ http://github.com/jeremyfelt

License
---------------------

Copyright 2011 Jeremy Felt

Licensed under the MIT License - check the license.txt file.