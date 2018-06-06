<?php
	define("ROOT_SERVER", __DIR__ . "/../");

	require_once(ROOT_SERVER . "vendor/autoload.php");
	require_once(ROOT_SERVER . "library/config.php");

	set_error_handler(array("Bolt\\Handler", "error"), E_ALL & ~E_NOTICE);
	set_exception_handler(array("Bolt\\Handler", "exception"));

	$dbo = new Bolt\Connections\Dbo(new Bolt\Connections\Config\Dbo($connection));
	$api = new Bolt\Api();

	// Add connections to api object here
	$api->connections->add($dbo, "dbo");
	$api->activate();

	$controllerName = "App\\Controllers\\" . $api->route->controller;

	if (class_exists($controllerName))
	{
		$controller = new $controllerName();

		if (method_exists($controller, $api->route->method))
		{
			$api->response->data = $controller->{$api->route->method}($api);
		}
	}
	elseif ($api->route->controller == "")
	{
		$config = new App\Config();
		$versioning = $config->versionInfo();

		$api->response->data = array(
			"name" => API_NAME,
			"deployment" => DEPLOYMENT,
			"versioning" => $versioning['version']
		);
	}

	$api->response->output();
?>
