<?php

/* Prevent the browser (and any proxies) from caching this page.
   Include this on every page that requires a logged-in session,
   right after session_start(), before any HTML is sent. */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Saturday, 01 Jan 2000 00:00:00 GMT");

?>