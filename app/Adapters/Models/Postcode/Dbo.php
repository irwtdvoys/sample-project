<?php
	namespace App\Adapters\Models\Postcode;

	use App\Interfaces\Models\Postcode;
	use Bolt\Interfaces\Model;
	use Bolt\Connections\Dbo as Connection;
	use Cruxoft\Logbook;

	class Dbo extends Model implements Postcode
	{
		private $parent;
		private $resource;
		private $table = "postcodes";

		public function __construct(Connection $resource, \App\Models\Postcode $parent)
		{
			$this->parent = $parent;
			$this->resource = $resource;
		}

		private function format($data)
		{
			$record = (object)array(
				"code" => $data['code'],
				"location" => (object)array(
					"type" => "Point",
					"coordinates" => array(
						$data['lng'],
						$data['lat']
					)
				)
			);

			return $record;
		}

		public function save()
		{
			if (isset($this->parent->id))
			{
				$result = $this->update();
			}
			else
			{
				$result = $this->add();
			}

			return $result;
		}

		public function load()
		{
			$SQL = "SELECT * FROM `" . $this->table . "` WHERE `code` = :code";
			$result = $this->resource->query($SQL, array(":code" => $this->parent->code()), true);

			if ($result !== false)
			{
				$result = $this->format($result);
			}

			return $result;
		}

		private function add()
		{
			$SQL = "INSERT INTO `" . $this->table . "` 
					(
						`id`,
						`code`,
						`lat`,
						`lng`
					) 
					VALUES 
					(
						:id,
						:code,
						:lat,
						:lng
					)";

			$values = array(
				":id" => $this->parent->id(),
				":code" => $this->parent->code(),
				":lat" => $this->parent->location->lat(),
				":lng" => $this->parent->location->lng()
			);

			try
			{
				$result = $this->resource->query($SQL, $values);
			}
			catch (\Bolt\Exceptions\Dbo $exception)
			{
				Logbook::get("logger")->error($exception->getMessage(), get_object_vars($this->parent));

				return false;
			}

			return $this->load();
		}

		private function update()
		{
			$SQL = "UPDATE `" . $this->table . "` 
			        SET
			            `feed` = :feed,
			            `type` = :type,
			            `mood` = :mood,
						`data` = :data,
						`special` = :special
					WHERE `id` = :id";

			$values = array(
				":id" => $this->parent->id(),
				":feed" => $this->parent->feed(),
				":open" => json_encode($this->parent->open()),
				":data" => json_encode($this->parent->data()),
				":special" => $this->parent->special()
			);

			try
			{
				$this->resource->query($SQL, $values);
			}
			catch (\Bolt\Exceptions\Dbo $exception)
			{
				Logbook::get("logger")->error($exception->getMessage(), get_object_vars($this->parent));

				return false;
			}

			return true;
		}
	}
?>
