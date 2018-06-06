<?php
	namespace App\Repositories;

	use Bolt\Repository;

	class Postcodes extends Repository
	{
		public function search($parameters = null, $from = 0, $quantity = null)
		{
			return $this->adapter->search($parameters, $from, $quantity);
		}

		public function count($parameters = null)
		{
			return $this->adapter->count($parameters);
		}

		public function bulk($postcodes)
		{
			return $this->adapter->bulkSave($postcodes);
		}
	}
?>
