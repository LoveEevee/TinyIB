<?php

// Rename this file to settings.php
// Then run /imgboard.php

define('TINYIB_MODPASS', "");
// Administrator's IP address hashed with ripemd160, e.g.
// hash('ripemd160',getenv('REMOTE_ADDR'));
// Admin panel will be at /imgboard.php?manage

define('TINYIB_BOARD', "froge");
// Unique identifier for this board using only letters and numbers

define('TINYIB_BOARDDESC', "Post froge");
// Displayed at the top of every page

define('TINYIB_THREADSPERPAGE', 10);
// Amount of threads shown per index page

define('TINYIB_MAXTHREADS', 0);
// Oldest threads are discarded over this limit  [0 to disable]

define('TINYIB_TRUNCATE', 15);
// Messages are truncated to this many lines on board index pages  [0 to disable]

define('TINYIB_PREVIEWREPLIES', 3);
// Amount of replies previewed on index pages

define('TINYIB_MAXREPLIES', 0);
// Maximum replies before a thread stops bumping  [0 to disable]

define('TINYIB_MAXKB', 512);
// Maximum file size in kilobytes  [0 to disable]

define('TINYIB_MAXKBDESC', "512 KB");
// Human-readable representation of the maximum file size

define('TINYIB_MAXW', 125);
// Maximum image width (reply) - Images exceeding these sizes will be thumbnailed

define('TINYIB_MAXH', 125);
// Maximum image height (reply)

define('TINYIB_MAXWOP', 250);
// Maximum image width (new thread)

define('TINYIB_MAXHOP', 250);
// Maximum image height (new thread)

define('TINYIB_DELAY', 60);
// Delay between posts to help control flooding  [0 to disable]

define('TINYIB_LOGO', "");
// Logo HTML

define('TINYIB_TRIPSEED', "");
// Enter some random text - Used when generating secure tripcodes - Must not change once set

define('TINYIB_ADMINPASS', "");
// Text entered at the manage prompt to gain administrator access

define('TINYIB_DBMODE', "flatfile");
// Choose: flatfile / mysql / sqlite

// Note: The following only apply when TINYIB_DBMODE is set to mysql
define('TINYIB_DBHOST', "localhost");
define('TINYIB_DBUSERNAME', "");
define('TINYIB_DBPASSWORD', "");
define('TINYIB_DBNAME', "");
define('TINYIB_DBPOSTS', TINYIB_BOARD . "_posts");
define('TINYIB_DBBANS', "bans");
?>