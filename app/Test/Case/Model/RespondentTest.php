<?php 
App::uses('Respondent', 'Model');

class RespondentTest extends CakeTestCase {
	public $fixtures = array('Respondent');
	
	
	public function setUp() {
		parent::setUp();
		$this->Respondent = ClassRegistry::init('Respondent');
	}
	
	public function testCalculatePearsonIdentical() {
		$ratings1 = array(3 => 4.0, 5 => 8.0);
		$ratings2 = array(3 => 4.0, 5 => 8.0);
		// Identical
		$result = $this->Respondent->calculatePearson($ratings1, $ratings2);
		$this->assertEquals(1, $result);
	}
	
	public function testCalculatePearsonSameSet(){
		// Different values for same set
		$ratings1 = array(3 => 4.0, 5 => 8.0);
		$ratings2 = array(3 => 8.0, 5 => 10.0);
		$result = $this->Respondent->calculatePearson($ratings1, $ratings2);
		$this->assertEquals(4/((sqrt(8)*sqrt(2))), $result);
	}
	
	public function testCalculatePearsonDifferentSet(){
		$ratings1 = array(3 => 4.0, 5 => 8.0);
		$ratings2 = array(3 => 8.0, 5 => 10.0, 6 => 6.0);
		$result = $this->Respondent->calculatePearson($ratings1, $ratings2);
		$this->assertEquals(4/((sqrt(8)*sqrt(4))), $result);
	}
}

?>