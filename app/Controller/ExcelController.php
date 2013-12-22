<?php

use Lib\Similarity;
App::uses('AppController', 'Controller');

class ExcelController extends AppController {

	const ARTIST_ROW = 2;
	const RESPONDENTS_START = 19;
	/**
	 * This controller does not use a model
	 *
	 * @var array
	 */
	public $uses = array('Artist', 'Respondent', 'Rating');

	public $artists = array();

	
	public function test(){
		App::import('Vendor', 'PHPExcel/Classes/PHPExcel');
		
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$rowCount = 2;
	
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A1', "#Neighbors");
		$objPHPExcel->getActiveSheet()->SetCellValue('B1', "Pearson");
		$objPHPExcel->getActiveSheet()->SetCellValue('C1', "Cosine");
		$objPHPExcel->getActiveSheet()->SetCellValue('D1', "AdjustedCosine");
		
		
		
		
		for($i=1 ; $i<30; $i = $i+1){
			$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $i);
			//$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $this->Respondent->calculateNDCG("Pearson", $i));
			$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $this->Respondent->calculateNDCG("Cosine", $i));
			//$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $this->Respondent->calculateNDCG("AdjustedCosine", $i));
				
			$rowCount++;
			
		//	echo($this->Respondent->calculateNDCG("Pearson", $i));
		//	echo "<br />";
		}
		
		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
		$objWriter->save('some_excel_file.xlsx');
		/*
		echo "AdjustedCosine <br />";
		for($i=5 ; $i<100; $i = $i+5){
			echo($this->Respondent->calculateNDCG("AdjustedCosine", $i));
			echo "<br />";
		}
		*/
		die();
	}
	
	
	public function suggestions($similarityFunction = 'Pearson', $neighbors = '10') {
		$artists = $this->Artist->query('SELECT DISTINCT Artist.id, Artist.name FROM artists as Artist RIGHT JOIN ratings ON Artist.id = ratings.artist_id ORDER BY Artist.name ASC');
		
		if($this->request->is('post')){
			
			// Convert post-data (survey) to ratings array
			$ratings1 = array();
			foreach($this->request->data['Rating'] as $artist=>$rating){
				if(!empty($rating['grade'])){
					$ratings1[$artist] = $rating['grade'];
				}
			}
			
			$respondents = $this->Respondent->find('all', array('contain' => 'Rating.grade > 0'));
			//$respondents = $this->Respondent->find('stretched', array('contain' => 'Rating.grade > 0'));
			
			foreach($respondents as &$respondent){
				$ratings2 = $this->formatUserRatings($respondent['Rating']);
				//$respondent['sim'] = $this->Respondent->calculatePearson($ratings1, $ratings2);
				$respondent['sim'] = $this->Respondent->calculateAdjustedCosineSimilarity($ratings1, $ratings2);
			}
	
			usort($respondents, array($this, "sortSimularities"));
	
			$respondents = array_splice($respondents, 0, 10);	
			foreach($artists as &$artist){
				$artist['sim'] = $this->weightedSum($artist, $respondents);
			}
			usort($artists, array($this, "sortSimularities"));
		
			die(debug($artists));
		} else {
			$this->set(compact('artists'));
		}

	}
	
	private function weightedSum($artist, $neighbors){
		//(debug($artist));
		$nominator = $denominator = 0;
		foreach($neighbors as $neighbor){
			$neighbor['Rating'] = $this->formatUserRatings($neighbor['Rating']);
			if(!empty($neighbor['Rating'][(int)$artist['Artist']['id']])){
				$nominator += $neighbor['Rating'][(int)$artist['Artist']['id']] * $neighbor['sim'];
				$denominator += abs($neighbor['sim']);
			}
		}
		return $nominator/$denominator;
	}

	public function formatUserRatings($ratings){
		$result = array();
		foreach($ratings as $rating){
			$result[$rating['artist_id']] = $rating['grade'];
		}
		return $result;
	}
	
	function sortSimularities($a, $b) {
		if (abs($a["sim"] - $b["sim"]) < 0.00000001) {
			return 0; // almost equal
		} else if (($a["sim"] - $b["sim"]) > 0) {
			return -1;
		} else {
			return 1;
		}
	}
	
	function cmpsuggestion($a, $b) {
		if (abs($a["suggestion"] - $b["suggestion"]) < 0.00000001) {
			return 0; // almost equal
		} else if (($a["suggestion"] - $b["suggestion"]) > 0) {
			return -1;
		} else {
			return 1;
		}
	}

	private function similarity($usera, $userb) {
		$sim = 0;
		$avga = $this->avg($usera);
		$avgb = $this->avg($userb);
		$combinedRatings = $this->combinedRatings($usera, $userb);
		foreach ($combinedRatings as $rating) {
			$sim += (($rating[0] - $avga) * ($rating[1] - $avgb));
		}

		$a = 0;
		foreach ($usera['Rating'] as $rating) {
			$a += pow(($rating['grade'] - $avga), 2);
		}
		$a = sqrt($a);

		$b = 0;
		foreach ($userb['Rating'] as $rating) {
			$b += pow(($rating['grade'] - $avgb), 2);
		}
		$b = sqrt($b);
		/* debug($usera);
		debug($b);
		debug($sim);
		die(); */
		return ($sim / ($a * $b));

	}

	private function avg($user) {
		$avg = 0;
		foreach ($user['Rating'] as $rating) {
			$avg += $rating['grade'];
		}
		return round($avg / sizeof($user['Rating']), 5);
	}

	private function combinedRatings($a, $b) {
		$results = array();
		foreach ($a['Rating'] as $ratinga) {
			foreach ($b['Rating'] as $ratingb) {
				if ($ratinga['artist_id'] == $ratingb['artist_id']) {
					$results[] = array(round($ratinga, 2), round($ratingb), 2);
					continue 2;
				}
			}
		}
		return $results;
	}

	public function import($filename = '') {
		if (empty($filename)) {
			$filename = 'C:/Users/Anne/Desktop/Dataset_Rating_2013.xls';
		}
		$this->loadExcel($filename);
	}

	private function loadExcel($filename) {
		App::import('Vendor', 'PHPExcel/Classes/PHPExcel');
		$objPHPExcel = PHPExcel_IOFactory::load($filename);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$highestRow = $objWorksheet->getHighestRow();
		$rows = $objPHPExcel->getActiveSheet()->toArray();
		//die(debug($rows));
		foreach ($rows as $i => $row) {
			if ($i == self::ARTIST_ROW) {
				foreach ($row as $column => $cell) {
					if (!empty($cell)) {

						$this->Artist
								->save(array('Artist' => array('name' => $cell)));
						$this->artists[$column] = $this->Artist->id;
					}
				}
			} else if ($i >= self::RESPONDENTS_START - 1) {
				$this->loadRespondentRow($row);
			}
		}

	}

	private function loadRespondentRow($row) {
		$grades = array();

		foreach ($row as $i => $cell) {
			if ($i == 0) {
				if (preg_match('/Respondent (\d)+/', $cell, $matches) === FALSE)
					return;
				$user_id = $matches[1];
				$user = $cell;
				$grades['Respondent']['name'] = $user;
			} else {
				if ($cell == 'EM')
					$cell = 0;
				else if ($cell == 'DK')
					$cell = -1;
				else if ($cell == 'NO')
					$cell = -2;

				$grades['Rating'][] = array('artist_id' => $this->artists[$i],
						'grade' => $cell,);
			}
		}
		$this->Respondent->saveAll($grades);
	}
}
