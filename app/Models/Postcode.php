<?php
	namespace App\Models;

	use Bolt\GeoJson\Geometry;
	use Bolt\Interfaces\Connection;
	use Bolt\Model;
	use Bolt\Traits\Outputable;

	class Postcode extends Model
	{
		use Outputable;

		public $code;
		public $location;
		public $distance;

		public function __construct(Connection $connection = null, $data = null)
		{
			parent::__construct($connection, $data);

			if (isset($data['lat']) && isset($data['lng']))
			{
				$this->location(new Geometry\Point(array($data['lng'], $data['lat'])));
			}
		}

		public function location($data = null)
		{
			if ($data === null)
			{
				return $this->location;
			}

			if ($data instanceof Geometry)
			{
				$this->location = $data;
			}
			else
			{
				$type = "\\Bolt\\GeoJson\\Geometry\\";
				$type .= (gettype($data) === "array") ? $data['type'] : $data->type;

				$this->location = new $type($data);
			}

			return $this;
		}
	}
?>
