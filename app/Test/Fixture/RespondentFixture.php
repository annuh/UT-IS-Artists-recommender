<?php
class RespondentFixture extends CakeTestFixture {
	
	public $useDbConfig = 'test';
	
	public $fields = array(
			'id' => array('type' => 'integer', 'key' => 'primary'),
			'name' => array('type' => 'string', 'length' => 255, 'null' => false)
	);
}
?>