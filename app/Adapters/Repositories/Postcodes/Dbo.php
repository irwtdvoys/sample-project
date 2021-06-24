<?php
	namespace App\Adapters\Repositories\Postcodes;

	use App\Interfaces\Repositories\Postcodes;
	use App\Models\Postcode;
	use Bolt\Adapter;
	use Bolt\Connections\Dbo as Connection;
	use Bolt\GeoJson\Geometry;

	class Dbo extends Adapter implements Postcodes
	{
		public function __construct(Connection $resource)
		{
			$this->resource = $resource;
		}

		protected function searchByCode(string $code, int $from = 0, int $quantity = null): array
		{
			$limit = ($quantity !== null) ? " LIMIT " . $from . ", " . $quantity : "";

			$SQL = "SELECT * 
					FROM `postcodes` 
					WHERE `code` LIKE :code 
					ORDER BY `code` DESC" . $limit;
			return $this->resource->query($SQL, array(":code" => "%" . $code . "%"));
		}

		protected function searchByLocation(Geometry $location, int $from = 0, int $quantity = null): array
		{
			$limit = ($quantity !== null) ? " LIMIT " . $from . ", " . $quantity : "";

			$SQL = "SELECT *, (
						ROUND(
							SQRT(
								POW(69.1 * (`lat` - :lat), 2) +
								POW(69.1 * (:lng - `lng`) * COS(`lat` / 57.3), 2)
						  	) * 1609.344
						)
					) AS `distance`
					FROM `postcodes`
					ORDER BY `distance` ASC" . $limit;

			$point = $location->toPoint();

			$parameters = array(
				":lat" => $point->lat(),
				":lng" => $point->lng()
			);

			return $this->resource->query($SQL, $parameters);
		}

		protected function countByCode(string $code): int
		{
			$SQL = "SELECT COUNT(*) AS 'quantity' FROM `postcodes` WHERE `code` LIKE :code";
			$result = $this->resource->query($SQL, array(":code" => "%" . $code . "%"), true);

			return ($result === false) ? 0 : $result['quantity'];
		}

		protected function countByLocation(Geometry $location): int
		{
			$SQL = "SELECT COUNT(*) AS 'quantity' FROM (
						SELECT `code`, (ROUND(SQRT(
							POW(69.1 * (`lat` - :lat), 2) +
							POW(69.1 * (:lng - `lng`) * COS(`lat` / 57.3), 2)) * 1609.344)) AS `distance`
						FROM `postcodes`
					) s";

			$point = $location->toPoint();

			$parameters = array(
				":lat" => $point->lat(),
				":lng" => $point->lng()
			);

			$result = $this->resource->query($SQL, $parameters, true);

			return ($result === false) ? 0 : $result['quantity'];
		}

		public function search($parameters = null, $from = 0, $quantity = null): array
		{
			$results = false;

			if (isset($parameters['code']))
			{
				$results = $this->searchByCode($parameters['code'], $from, $quantity);
			}
			elseif (isset($parameters['location']))
			{
				$results = $this->searchByLocation($parameters['location'], $from, $quantity);
			}

			$postcodes = array();

			if ($results !== false)
			{
				foreach ($results as $result)
				{
					$postcodes[] = new Postcode($this->resource, $result);
				}
			}

			return $postcodes;
		}

		public function count($parameters = null): int
		{
			$result = 0;

			if (isset($parameters['code']))
			{
				$result = $this->countByCode($parameters['code']);
			}
			elseif (isset($parameters['location']))
			{
				$result = $this->countByLocation($parameters['location']);
			}

			return $result;
		}

		public function bulkSave($postcodes): array
		{
			$SQL = "INSERT INTO `postcodes` VALUES 
					(
						:code,
						:lat,
						:lng
					) ON DUPLICATE KEY UPDATE
						`lat` = :lat2, 
						`lng` = :lng2";

			$parameters = array();

			foreach ($postcodes as $next)
			{
				$parameters[] = array(
					":code" => $next['pcds'],
					":lat" => $next['lat'],
					":lng" => $next['long'],
					":lat2" => $next['lat'],
					":lng2" => $next['long']
				);
			}

			$results = $this->resource->query($SQL, $parameters);

			return $results;
		}
	}
?>
