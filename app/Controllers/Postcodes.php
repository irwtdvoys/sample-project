<?php
	namespace App\Controllers;

	use App\Models\Postcode;
	use Bolt\Api;
	use Bolt\Api\Request\Range;
	use Bolt\Controller;

	class Postcodes extends Controller
	{
		public function getById(Api $api)
		{
			$postcode = new Postcode($api->connections->dbo());
			$postcode->code($api->route->info->id());

			$result = $postcode->load();

			if ($result === false)
			{
				$api->response->status(404, "Postcode not found");
			}

			return $postcode;
		}

		public function postSearch(Api $api)
		{
			if (!isset($api->request->parameters->code) && !isset($api->request->parameters->location))
			{
				$api->response->status(400, "Missing required field, `code` or `location` must be provided");
			}

			$parameters = $api->request->parameters->filter(array("code", "location"));

			// validate location
			if (isset($parameters['location']))
			{
				$type = "\\Bolt\\GeoJson\\Geometry\\" . $parameters['location']->type;

				try
				{
					$parameters['location'] = new $type($parameters['location']);
				}
				catch (\Exception $exception)
				{
					// catch error generating geojson object
					$api->response->status(400, $exception->getMessage());
				}
			}

			$helper = new \App\Repositories\Postcodes($api->connections->dbo());

			$total = $helper->count($parameters);

			if (isset($api->request->headers->range))
			{
				$range = $api->request->getRangeData($total);
			}
			else
			{
				// set default range if none supplied in header
				$range = new Range("indices=0-24", $total);
			}

			// requested range not satisfiable
			if ($range->start() > max(0, ($total - 1)))
			{
				$api->response->status(416);
			}

			$start = $range->start();
			$quantity = ($range->end() - $range->start()) + 1;

			// prepare response based on full/partial request
			$api->response->headers->add("Content-Range: indices " . $range->start() . "-" . $range->end() . "/" . $total);
			$api->response->code = ($quantity == $total || $total === 0) ? 200 : 206;

			return $helper->search($parameters, $start, $quantity);
		}
	}
?>
