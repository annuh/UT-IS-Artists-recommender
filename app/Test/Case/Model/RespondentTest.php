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
	
	public function testDCG(){
		$ratings = array(1=>3, 2=>2, 3=>3, 4=>0, 5=>1, 6=>2);
		$computed = array(0=>1, 1=>2, 2=>3, 3=>4, 4=>5, 5=>6);
		$expected = 8.1;
		$result = round($this->Respondent->getDCG($computed, $ratings), 1);
		$this->assertEquals($expected, $result);
	}
	
	public function testXtreme(){
		$rating1 = array(1=>9, 2=>9, 3=>4);
		$rating2 = array(1=>6, 2=>7, 3=>2);
		
		$expected = 1 - ((5/3)/9);
		$result = $this->Respondent->calculateXtreme($rating1, $rating2);
		
		$this->assertEquals($result, $expected);
		
	}

	public function testNDCG(){
		$ratings1 = array(1=>3, 2=>2, 3=>3, 4=>0, 5=>1, 6=>2);
		$computedRatings = array(1=>10, 2=>9, 3=>8, 4=>7, 5=>6, 6=>5);
		
		$expected = 0.932;
		$result = round($this->Respondent->getNDCG($computedRatings, $ratings1), 2);
		
		
	}
	
	
}

?>