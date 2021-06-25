<?php
	use Bolt\Deployment;
	use Dotenv\Dotenv;

	require_once(ROOT_SERVER . "vendor/autoload.php");

	set_error_handler(["Bolt\\Handler", "error"], E_ALL & ~E_NOTICE);
	set_exception_handler(["Bolt\\Handler", "exception"]);

	$dotenv = new Dotenv(ROOT_SERVER);
	$dotenv->load();

	define("DEPLOYMENT", getenv("DEPLOYMENT")); // development or production
	define("API_NAME", "Sample Project");

	// Versioning
	#define("VERSION_EXTERNAL_AUTH", "v1.6");
	define("VERSION_INTERNAL_CODE", "1.7.0");
	define("VERSION_INTERNAL_API", "dev");

	require_once(ROOT_SERVER . "library/functions.php");

	$config = array("database", "roots");

	foreach ($config as $next)
	{
		require_once(ROOT_SERVER . "library/config/" . $next . ".php");
	}

	$connection = array(
		"type" => DB_TYPE,
		"host" => DB_HOST,
		"port" => DB_PORT,
		"database" => DB_NAME,
		"username" => DB_USER,
		"password" => DB_PASS,
		"auto" => true
	);

	if (DEPLOYMENT === Deployment::DEVELOPMENT) // Framework expects server to be setup with no errors displayed
	{
		ini_set("display_errors", 1);
		ini_set("error_reporting", E_ALL & ~E_NOTICE);
	}
?>
