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
			//$query['contain'] = 'Rating';
			return $query;
		} else if($state === 'after'){
			foreach($results as &$respondent){
				//$grades = Hash::extract($respondent['Rating'], '{n}.grade');
				$grades = $respondent['Rating'];
				$max = max($grades);
				$min = min($grades);
				foreach($respondent['Rating'] as &$rating){
					$rating = (($rating-$min)*9)/($max-$min) + 1;
				}
			}
			return $results;
		}
	}
	
	public function afterFind($results, $primary = false) {
		if(isset($results[0]['Rating'])){
			foreach($results as &$respondent){
				$result = array();
				foreach($respondent['Rating'] as $rating){
					$result[$rating['artist_id']] = (float) $rating['grade'];
				}
				$respondent['Rating'] = $result;
			}				
		}	
		return $results;
		
	}
	
	
	
	public function calculateNDCG($similarityFunction = "AdjustedCosine", $top_neighbors = 10){
		// Load all artists
		$artists = Cache::read("Artists");
		if(empty($artists)){
			$artists = ClassRegistry::init('Artist')->query('SELECT DISTINCT Artist.id, Artist.name FROM artists as Artist RIGHT JOIN ratings ON Artist.id = ratings.artist_id ORDER BY Artist.name ASC');
			Cache::write("Artists", $artists);
		}
		
		$nDCG = 0;
		// NUMBER OF USERS
		$userCount = 20;
		
		// Load respondents, from cache when possible
		$training_set = Cache::read('Respondents');
		if(empty($training_set)){
			$training_set = $this->find('all', array(
					//'conditions'=>array('not'=>array('Respondent.id' => $user['Respondent']['id'])),
					'contain' => 'Rating.grade > 0',
					'limit' => $userCount));
			Cache::write('Respondents', $training_set);
		}
		
		foreach($training_set as $user){
			$ratings1 = $user['Rating'];
			// Load user and remove random 25 ratings
			$user['Rating'] = $this->shuffle_assoc($user['Rating']);
			$removedRatings = array_slice($user['Rating'], -25, null, true);
			
			// Set of neigbors for current user (current user will be removed later from this array)
			$neighbors = $training_set;
			foreach($neighbors as $id=>&$respondent){
				
				// Remove current user from neighbor set
				if($user['Respondent']['id'] == $respondent['Respondent']['id']) {
					unset($neighbors[$id]);
					continue;
				}
				
				$ratings2 = $respondent['Rating'];
				$respondent['sim'] = call_user_func(array($this, 'calculate'.$similarityFunction), $ratings1, $ratings2);
				
			}

			// Select best $count neighbors based on 'sim'
			usort($neighbors, array($this, "sortSimularities"));
			
			$neighbors = array_splice($neighbors, 0, $top_neighbors);
			
			
			
			// Guess users grade for the removedRatings
			$computedRatings = array();
			foreach($removedRatings as $artistid=>$rating){
				$computedRatings[$artistid] = $this->weightedSum($artistid, $neighbors);
			}
			
			
			
			$computedTopArtists = $computedRatings;
			arsort($computedTopArtists);
			array_slice($computedTopArtists, 5);
			$computedTopArtists = array_keys($computedTopArtists);
			
// 			// TOP 5 suggestions, reassigns key
// 			$computedTopArtists = array_flip($computedRatings); // {[grade] => [artist_id]}
// 			// Sort on ratings
// 			krsort($computedTopArtists);
// 			// Resets keys, so [0] => 'Highest rated artist'
// 			array_values($computedTopArtists);
// 			// Take 5 best artists
// 			array_splice($computedTopArtists, 5);
			
			
			
			// TOP 5 user ratings
			//rsort($removedRatings);
			//array_splice(($removedRatings), 5);
			
			$dcg = $this->getDCG($computedTopArtists, $ratings1);
			$idcg = $this->getIDCG($removedRatings);
			
			$nDCG += ($dcg / $idcg);
			
			//die(debug($nDCG));
			
		}
		
		return $nDCG / $userCount;
	}
	
	function shuffle_assoc($list) {
		if (!is_array($list)) return $list;
	
		$keys = array_keys($list);
		shuffle($keys);
		$random = array();
		foreach ($keys as $key) {
			$random[$key] = $list[$key];
		}
		return $random;
	}
	
	function array_splice_assoc(&$input, $offset, $length=0, $replacement) {
		$replacement = (array) $replacement;
		$key_indices = array_flip(array_keys($input));
		if (isset($input[$offset]) && is_string($offset)) {
			$offset = $key_indices[$offset];
		}
		if (isset($input[$length]) && is_string($length)) {
			$length = $key_indices[$length] - $offset;
		}
	
		$input = array_slice($input, 0, $offset, TRUE)
		+ $replacement
		+ array_slice($input, $offset + $length, NULL, TRUE);
	}
	
	/**
	 * Computes DCG
	 * @param array $computedTopArtists - sorted list of artist_id [0] => 'Best artist_id', [1] => '2nd best artist_id', etc..
	 * @param array $removedRatings Original ratings - [artist_id] => rating
	 * @return number
	 */
	public function getDCG($computedTopArtists, $removedRatings = array()){
		
		$result = (float) $removedRatings[$computedTopArtists[0]];
		for($i = 1; $i<count($computedTopArtists); $i++) {
			$result += (float)$removedRatings[$computedTopArtists[$i]] / (log($i + 1 ,2));
		}
		return $result;
	}
	
	public function getIDCG($ratings, $at = 5){
		$computedTopArtists = $ratings;
		arsort($computedTopArtists);
		array_slice($computedTopArtists, 5);
		$computedTopArtists = array_keys($computedTopArtists);
		return $this->getDCG($computedTopArtists, $ratings);
		
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
	
	/**
	 * Returns suggestion
	 * @param unknown $artistid
	 * @param unknown $neighbors
	 * @return number
	 */
	public function weightedSum($artistid, $neighbors){
		$nominator = $denominator = 0;
		foreach($neighbors as $neighbor){
			//$neighbor['Rating'] = $this->formatUserRatings($neighbor['Rating']);
			if(!empty($neighbor['Rating'][$artistid])){
				$nominator += ($neighbor['Rating'][$artistid] * $neighbor['sim']);
				$denominator += abs($neighbor['sim']);
			}
		}
		
		if($denominator == 0){
			return -1;
		}
	
		return (int) $nominator/$denominator;
	}
	
	public function filter($x, $items = array()){
		$result = array();
		foreach($items as $key=>$value){
			if($x === $value){
				$result[$key] = $value;
			}
		}
		return $result;
	}
	
	public function calculateXtreme($item1, $item2){
		
		$max = max($item1);
		$min = min($item1);
		
		$max_items = $this->filter($max, $item1);
		$min_items = $this->filter($min, $item1);
		
		$artists_max = array_intersect(array_keys($max_items), array_keys($item2));
		$artists_min = array_intersect(array_keys($min_items), array_keys($item2));
		
		//$artists = array_intersect($artists_max, $artists_min);
		$common_artists_count = (count($artists_max) + count($artists_min) == 0);
		if($common_artists_count == 0) return 0;
		
		$difference = 0;
		foreach($max_items as $artist=>$rating){
			if(isset($item2[$artist]) && $item2[$artist] < $rating){
				$difference += $rating-$item2[$artist];
			}
		}
		
		foreach($min_items as $artist=>$rating){
			if(isset($item2[$artist]) && $item2[$artist] > $rating){
				$difference += $item2[$artist]-$rating;
			}
		}
		
		return 1- (($difference /$common_artists_count) / 9);
		
	}
	
	/**
	 * Calculate Pearson similarity for two rating sets
	 * @param unknown $user1
	 * @param unknown $user2
	 * @return number
	 */
	
	public function calculatePearson($item1, $item2){
		// Items rated in both sets
		$items = array_intersect(array_keys($item1), array_keys($item2));
		
		//$grades = $this->combinedRatings($user1, $user2);
		if(empty($items)) return 0;
		$avg1 = $this->avg($item1);
		$avg2 = $this->avg($item2);
		$sumUser1Squared = $sumUser2Squared = $sumCombined = 0.0; 
		foreach($items as $i){
			$sumCombined += (($item1[$i] - $avg1) * ($item2[$i] - $avg2)); // For nominator
			$sumUser1Squared += pow($item1[$i] - $avg1, 2); // For denominator
			$sumUser2Squared += pow($item2[$i] - $avg2, 2);
			
		}
		// Hack(?) if user has rated all items the same.
		if($sumUser1Squared == 0) $sumUser1Squared = 0.1;
		if($sumUser2Squared == 0) $sumUser2Squared = 0.1;
		
		return ($sumCombined) / (sqrt($sumUser1Squared * $sumUser2Squared));
	}
	
	public function calculateCosine($user1, $user2){
		//$grades = $this->combinedRatings($user1, $user2);
		//if(empty($grades) || empty($grades[1])) return 0;
		// Find complete set of rated items
		$items = array_merge(array_keys($user1), array_keys($user2));
		$sumCombined = $sumUser1 = $sumUser2 = 0.0;
		foreach($items as $i=>$item){
			if(!empty($user1[$item]) && !empty($user2[$item])) {
				$sumCombined += $user1[$item] * $user2[$item];
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
	
	public function calculateAdjustedCosine($item1, $item2){
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
