<?php

class Similarity {
	
	
	
	public function calculateNDCG($similarityFunction = "Pearson", $count = "10"){
		$this->Artist = \ClassRegistry::init('Artist');
		$this->Respondent = \ClassRegistry::init('Respondent');
		
		
		$artists = $this->Artist->query('SELECT DISTINCT Artist.id, Artist.name FROM artists as Artist RIGHT JOIN ratings ON Artist.id = ratings.artist_id ORDER BY Artist.name ASC');
		
		
		$respondent = $this->Respondent->find('first', array('contain' => 'Rating.grade > 0'));
		// Remove last 10 ratings.
		$removedArtists = array_splice($respondent['Rating'], -10);
		
		$respondents = $this->Respondent->find('all', array(
				'conditions'=>array('not'=>array('Respondent.id' => $respondent['Respondent']['id'])),
				'contain' => 'Rating.grade > 0'));
		
		$ratings1 = $this::formatUserRatings($respondent['Rating']);
		
		foreach($respondents as &$respondent){
			$ratings2 = $this::formatUserRatings($respondent['Rating']);
			$respondent['sim'] = $this->Respondent->calculate{$similarityFunction}($ratings1, $ratings2);
		}
		usort($respondents, array($this, "sortSimularities"));
	
		$respondents = array_splice($respondents, 0, 10);
		$computedRatings = array();
		foreach($artists as &$artist){
			//$artist['sim'] = $this->weightedSum($artist, $respondents);
			$computedRatings[$artist['Artist']['id']] = $this->weightedSum($artist, $respondents);
		}
		usort($artists, array($this, "sortSimularities"));
	
		die(debug($artists));
		
	}
	
	
	/**
	 * Calculate Pearson similarity for two rating sets
	 * @param unknown $user1
	 * @param unknown $user2
	 * @return number
	 */
	public function calculatePearson($user1, $user2){
		$grades = $this->combinedRatings($user1, $user2);
		if(empty($grades) || empty($grades[1])) return 0;
		$avg1 = $this->avg($grades[1]);
		$avg2 = $this->avg($grades[2]);
		$sumUser1Squared = $sumUser2Squared = $sumCombined = 0.0;
		foreach($grades[1] as $i=>$grade){
			$sumCombined += (($grades[1][$i] - $avg1) * ($grades[2][$i] - $avg2)); // For nominator
			$sumUser1Squared += pow($grades[1][$i] - $avg1, 2); // For denominator
			$sumUser2Squared += pow($grades[2][$i] - $avg2, 2);
				
		}
		// Hack(?) if user has rated all items the same.
		if($sumUser1Squared == 0) $sumUser1Squared = 0.1;
		if($sumUser2Squared == 0) $sumUser2Squared = 0.1;
	
		return ($sumCombined) / (sqrt($sumUser1Squared * $sumUser2Squared));
	}
	
	public static function formatUserRatings($ratings){
		$result = array();
		foreach($ratings as $rating){
			$result[$rating['artist_id']] = $rating['grade'];
		}
		return $result;
	}
	
	private function combinedRatings($a, $b) {
		$results = array();
		$results[1] = array_intersect_key($a, $b);
		foreach($results[1] as $artist=>$grade){
			//$results[1][$artist] = $a[$artist];
			$results[2][$artist] = $b[$artist];
		}
		return $results;
		foreach ($a['Rating'] as $ratinga) {
			foreach ($b['Rating'] as $keyb=>$ratingb) {
				if ($ratinga['artist_id'] == $ratingb['artist_id']) {
					$results[1][$ratinga['artist_id']] = round($ratinga['grade'], 2);
					$results[2][$ratinga['artist_id']] = round($ratingb['grade'], 2);
					//$results[] = array(round($ratinga, 2), round($ratingb), 2);
					unset($b['Rating'][$keyb]); // Speedup
					continue 2;
				}
			}
		}
		return $results;
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
	

}

?>