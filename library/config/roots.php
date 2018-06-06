<?php
	define("ROOT_API", "http://localhost/" . truncateVersion(VERSION_INTERNAL_API, 2, true) . "/");
	define("ROOT_CDN", getenv("ROOT_CDN"));
?>
