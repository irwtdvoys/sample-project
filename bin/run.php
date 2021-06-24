#!/usr/bin/env php
<?php
	use Bolt\Job\Output;
	use Bolt\Json;

	const ROOT_SERVER = __DIR__ . "/../";

	if (PHP_SAPI !== "cli")
	{
		die("Command line usage only");
	}

	include_once(ROOT_SERVER . "bin/init.php");

	$options = getopt("j:d:", array("job:", "data:"));

	if (!isset($options['j']) && !isset($options['job']))
	{
		throw new Exception("Missing Job");
	}

	$name = $options['j'] ?? $options['job'];

	if (!isset($options['d']) && !isset($options['data']))
	{
		$data = (object)array();
	}
	else
	{
		$data = $options['d'] ?? $options['data'];
		$data = Json::decode($data);
	}

	$class = "\\App\\Jobs\\" . str_replace(".", "\\", $name);


	$dbo = new Bolt\Connections\Dbo(new Bolt\Connections\Config\Dbo($connection));

	$job = new $class($dbo);
	$job->data($data);

	$result = $job->run();

	if ($result->success() === true)
	{
		$job->output("Completed successfully", Output::SYSTEM);
	}
	else
	{
		$job->output("Failure: " . $result->message(), Output::JOB);
	}

	echo(Json::encode($result) . "\n");
?>
