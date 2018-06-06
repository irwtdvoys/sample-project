<?php
	use App\Repositories\Postcodes;
	use Bolt\Connections\Config\Dbo as Config;
	use Bolt\Connections\Dbo as Connection;
	use Bolt\Files;

	define("ROOT_SERVER", __DIR__ . "/../");

	require_once(ROOT_SERVER . "vendor/autoload.php");
	require_once(ROOT_SERVER . "library/config.php");

	$config = new Config($connection);
	$dbo = new Connection($config);

	$options = getopt("f:", array("file:"));

	if (!$options['f'] && !$options['file'])
	{
		die("Missing file\n");
	}

	$file = isset($options['f']) ? $options['f'] : $options['file'];

	if (!file_exists($file))
	{
		die("File not found\n");
	}

	$handler = new Files();
	$handler->open($file, "r");

	$helper = new Postcodes($dbo);

	$count = 0;

	$headers = array();
	$fields = array("pcds", "lat", "long");

	$records = array();

	$data = array();

	while (($row = fgetcsv($handler->resource, 0, ",", '"')) !== false)
	{
		if ($count === 0)
		{
			$headers = $row;
		}
		else
		{
			$record = array();

			foreach ($row as $key => $value)
			{
				if (in_array($headers[$key], $fields))
				{
					$record[$headers[$key]] = $value;
				}
			}

			$data[] = $record;
		}

		$count++;
	}

	echo("Found " . count($data) . "\n");

	$result = $helper->bulk($data);

	// todo: process result

	$handler->close();
?>
