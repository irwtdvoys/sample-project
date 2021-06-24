<?php
	namespace App\Controllers;

	use App\Masks\Search as InputMask;
	use App\Models\Postcode;
	use App\Repositories\Postcodes as PostcodesRepo;
	use Bolt\Api;
	use Bolt\Api\Request\Range;
	use Bolt\Controller;
	use Bolt\Http\Codes as HttpCodes;

	class Postcodes extends Controller
	{
		public function getById(Api $api): Postcode
		{
			$postcode = (new Postcode($api->connections->dbo()))
				->code($api->route->info->id())
				->load();

			return $postcode;
		}

		public function postSearch(Api $api)
		{
			if (!isset($api->request->parameters->code) && !isset($api->request->parameters->location))
			{
				$api->response->status(HttpCodes::BAD_REQUEST, "Missing required field, `code` or `location` must be provided");
			}

			$parameters = $api->request->parameters->validate(InputMask::class, true);

			// validate location
			if (isset($parameters['location']))
			{
				$type = "\\Bolt\\GeoJson\\Geometry\\" . $parameters['location']['type'];

				try
				{
					$parameters['location'] = new $type($parameters['location']);
				}
				catch (\Exception $exception)
				{
					// catch error generating geojson object
					$api->response->status(HttpCodes::BAD_REQUEST, $exception->getMessage());
				}
			}

			$helper = new PostcodesRepo($api->connections->dbo());
			$total = $helper->count($parameters);
			$range = isset($api->request->headers->range) ? $api->request->getRangeData($total) : new Range("indices=0-24", $total);

			// requested range not satisfiable
			if ($range->start() > max(0, ($total - 1)))
			{
				$api->response->status(HttpCodes::REQUESTED_RANGE_NOT_SATISFIABLE);
			}

			$start = $range->start();
			$quantity = ($range->end() - $range->start()) + 1;

			// prepare response based on full/partial request
			$api->response->headers->add("Content-Range: indices " . $range->start() . "-" . $range->end() . "/" . $total);
			$api->response->code = ($quantity == $total || $total === 0) ? HttpCodes::OK : HttpCodes::PARTIAL_CONTENT;

			return $helper->search($parameters, $start, $quantity);
		}
	}
?>
