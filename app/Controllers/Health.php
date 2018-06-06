<?php
	namespace App\Controllers;

	use Bolt\Api;
	use Bolt\Controller;

	class Health extends Controller
	{
		public function get(Api $api)
		{
			$api->response->status(200);
		}
	}
?>
