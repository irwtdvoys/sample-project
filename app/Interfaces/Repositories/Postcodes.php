<?php
	namespace App\Interfaces\Repositories;

	interface Postcodes
	{
		public function search($parameters = null, $from = 0, $quantity = null);
		public function count($parameters = null);
		public function bulkSave($postcodes);
	}
?>
