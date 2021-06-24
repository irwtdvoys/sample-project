<?php
	namespace App\Jobs;

	use App\Repositories\Postcodes;
	use Bolt\Files;
	use Bolt\Job;
	use Bolt\Job\Output;
	use Exception;

	class Import extends Job
	{
		public function execute(): void
		{
			$file = $this->data->file ?? "";

			try
			{
				$handler = new Files();
				$handler->open(ROOT_SERVER . $file, "r");
			}
			catch (Exception $exception)
			{
				$this->output($exception->getMessage(), Output::ERROR);
				$this->metrics->message($exception->getMessage());
				return;
			}

			$helper = new Postcodes($this->connection);

			$count = 0;

			$headers = array();
			$fields = array("pcds", "lat", "long");
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
			
			$this->metrics->success(true);
		}
	}
?>
