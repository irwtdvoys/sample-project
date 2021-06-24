<?php
	const ROOT_SERVER = __DIR__ . "/../";

	require_once(ROOT_SERVER . "bin/init.php");
	require_once(ROOT_SERVER . "vendor/autoload.php");

	$dbo = new Bolt\Connections\Dbo(new Bolt\Connections\Config\Dbo($connection));
	$api = new Bolt\Api();

	// Add connections to api object here
	$api->connections->add($dbo, "dbo");
	$api->activate();
?>
