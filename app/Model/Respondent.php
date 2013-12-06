<?php
App::uses('AppModel', 'Model');

/**
 * Fan Model
 *
 * @property Artist $Artist
 * @property LibraryArtist $LibraryArtist
 */
class Respondent extends AppModel {

	public $findMethods = array('stretched' =>  true);
	
	public $hasMany = array('Rating');

	protected function _findStretched($state, $query, $results = array()) {
		if ($state === 'before') {
			$query['contain'] = 'Rating';
			return $query;
		} else if($state === 'after'){
			foreach($results as &$respondent){
				$grades = Hash::extract($respondent['Rating'], '{n}.grade');
				$max = max($grades);
				$min = min($grades);
				foreach($respondent['Rating'] as &$rating){
					$rating['grade'] = (($rating['grade']-$min)*9)/($max-$min) + 1;
				}
			}
			return $results;
		}
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
	
	public function calculateCosineSimilarity($user1, $user2){
		//$grades = $this->combinedRatings($user1, $user2);
		//if(empty($grades) || empty($grades[1])) return 0;
		// Find complete set of rated items
		$items = array_merge(array_keys($user1), array_keys($user2));
		$sumCombined = $sumUser1 = $sumUser2 = 0.0;
		foreach($items as $item){
			if(!empty($user1[$item]) && !empty($user2[$item])) {
				$sumCombined += $user1[$i] * $user2[$i];
			}
			
		}
		foreach($user1 as $i=>$grade){
			$sumUser1 += pow($grade, 2);
		}
		foreach($user2 as $i=>$grade){
			$sumUser2 += pow($grade, 2);
		}
		
		return $sumCombined / (sqrt($sumUser1) * sqrt($sumUser2));
	}	
	
	public function calculateAdjustedCosineSimilarity($item1, $item2){
		// Items that are rated in both sets
		$items = array_intersect(array_keys($item1), array_keys($item2));
		
		$avg1 = array_sum($item1) / sizeof($item1);
		$avg2 = array_sum($item2) / sizeof($item2);
		
		$sumItem1Squared = $sumItem2Squared = $sumCombined = 0.0;
		foreach($items as $i){
			$sumCombined += (($item1[$i] - $avg1) * ($item2[$i] - $avg2)); // For nominator
		}
		
		foreach($item1 as $i=>$grade){
			$sumItem1Squared += pow($grade - $avg1, 2);
		}
		foreach($item2 as $i=>$grade){
			$sumItem2Squared += pow($grade - $avg2, 2);
		}
		// Hack(?) if user has rated all items the same.
	//	if($sumItem1Squared == 0) $sumItem1Squared = 0.1;
	//	if($sumItem2Squared == 0) $sumItem2Squared = 0.1;
		
		return ($sumCombined) / (sqrt($sumItem1Squared * $sumItem2Squared));	
	}
	
	public function calculateDistanceSimilarity($item1, $item2){
		// Items that are rated in both sets
		$items = array_intersect(array_keys($item1), array_keys($item2));
	
		return 0;
	}
	
	/**
	 * Returns list of ratings that are rated by both users.
	 * @param unknown $a
	 * @param unknown $b
	 * @return multitype:multitype:number
	 */
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
	
	private function avg($ratings) {
		if(empty($ratings)) return 0;
		return array_sum($ratings)/ count($ratings);
	}
	

	public function formatUserRatings($ratings){
		$result = array();
		foreach($ratings as $rating){
			$result[$rating['artist_id']] = $rating['grade'];
		}
		return $result;
	}
	
}
