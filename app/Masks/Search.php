<?php
	namespace App\Masks;

	use Bolt\Api\Request\InputMask;

	class Search extends InputMask
	{
		public function __construct($data = null)
		{
			parent::__construct($data);

			$this
				->add("code", null, [])
				->add("location", null, [])
			;
		}
	}
?>
