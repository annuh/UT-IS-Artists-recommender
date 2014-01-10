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
	
	//public function beforeFind($query = array())
	
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
	
	public function calculateBaseLine(){
		$respondents = $this->find('all', array(
				'conditions'=>array('respondent_id <' =>100),
				'contain' => 'Rating.grade > 0',
		));
		
		$this->Rating->virtualFields = array(
				'sum' => 'SUM(Rating.grade)',
				'count' => 'COUNT(*)');
		$ratings_sum = $this->Rating->find('list', array(
				'conditions' => array('Rating.grade >' => 0, 'respondent_id <' => 100),
				'fields'=>array('count', 'sum','Rating.artist_id', ), 'group'=>array('Rating.artist_id'), 'order'=>'sum'));
		
		$nDCG = 0;
		
		foreach($respondents as $user){
			$ratings1 = $user['Rating'];
			
			$neighbors = $respondents;
			$neighborRatings = $computedRatings = array();
			foreach($neighbors as $id=>&$respondent){
				// Remove current user from neighbor set
				if($user['Respondent']['id'] == $respondent['Respondent']['id']) {
					unset($neighbors[$id]);
					continue;
				}	
			}
			$neighbors = $this->shuffle_assoc($respondents);
			$neighbors = array_slice($neighbors, 0, 100, true);

			foreach($ratings1 as $artist_id=>$userRating){
				$voteCount = (float)key($ratings_sum[$artist_id]);
				$voteSum = (float)array_values($ratings_sum[$artist_id])[0];
				
				if($user['Respondent']['id'] < 100){
					$voteSum = $voteSum - $userRating;
					$voteCount = $voteCount - 1;
				}
				$computedRatings[$artist_id] = ($voteSum) /  ($voteCount);
			}
			
			$nDCG += $this->getNDCG($computedRatings, $ratings1);			
		}
		die(debug($nDCG/count($respondents)));
		return $nDCG / count($respondents);
	}
	
	public function calculateNDCG($similarityFunction = "AdjustedCosine", $top_neighbors = 10, $suggestionsFunction = "avg", $extra_options = array()){
		// Load all artists
		$artists = Cache::read("Artists");
		if(empty($artists)){
			$artists = ClassRegistry::init('Artist')->query('SELECT DISTINCT Artist.id, Artist.name FROM artists as Artist RIGHT JOIN ratings ON Artist.id = ratings.artist_id ORDER BY Artist.name ASC');
			Cache::write("Artists", $artists);
		}
		
		$nDCG = 0;
		// NUMBER OF USERS
		$userCount = 100;
		
		// Load respondents, from cache when possible
		$training_set = Cache::read('Respondents'.$userCount);
		if(empty($training_set)){
			$training_set = $this->find('all', array(
					'contain' => 'Rating.grade > 0',
					'limit' => $userCount));
			Cache::write('Respondents'.$userCount, $training_set);
		}
		
		foreach($training_set as $user){
			$ratings1 = $user['Rating'];
			// Load user and remove random 25 ratings
			$user['Rating'] = $this->shuffle_assoc($user['Rating']);
			
			if(count($user['Rating']) < 25){
				continue;
			}
			
			$removedRatings = array_slice($user['Rating'], 0, 25, true);
			$ratingsWithoutRemovedRatings = array_slice($user['Rating'], 26);
			
			// Set of neigbors for current user (current user will be removed later from this array)
			$neighbors = $training_set;
			foreach($neighbors as $id=>&$respondent){
				
				// Remove current user from neighbor set
				if($user['Respondent']['id'] == $respondent['Respondent']['id']) {
					unset($neighbors[$id]);
					continue;
				}
				
				$ratings2 = $respondent['Rating'];
				$respondent['sim'] = call_user_func(array($this, 'calculate'.$similarityFunction),$removedRatings, $ratings2, $extra_options);
			}

			// Select best $top_neighbors neighbors based on 'sim'
			usort($neighbors, array($this, "sortSimularities"));
			$neighbors = array_slice($neighbors, 0, $top_neighbors);
			
			// Guess users grade for the removedRatings
			$computedRatings = array();
			foreach($removedRatings as $artistid=>$rating){
				$computedRatings[$artistid] = call_user_func(array($this, 'suggestions'.$suggestionsFunction),$artistid, $neighbors);
				//$computedRatings[$artistid] = $this->weightedSumSuggestions($artistid, $neighbors);
			}
			
			$nDCG += $this->getNDCG($computedRatings, $removedRatings);			
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

	/**
	 * Computed NDCG for two rating sets
	 * @param unknown $computedRatings
	 * @param unknown $orignalRatings
	 * @return number
	 */
	public function getNDCG($computedRatings, $originalRatings, $at=5){
		$computedTopArtists = $this->getBestItems($computedRatings, $at);
		$dcg = $this->getDCG($computedTopArtists, $originalRatings);
		
		$originalTopItems = $this->getBestItems($originalRatings, $at);
		$idcg = $this->getDCG($originalTopItems, $originalRatings);
		
		return $dcg / $idcg;
	}
	
	/**
	 * Returns best x items from a rating set, indexed by order
	 * @param unknown $ratings
	 * @param integer $at - How many items should be returns111
	 */
	private function getBestItems($ratings, $at){
		arsort($ratings);
		$ratings = array_slice($ratings, 0, $at, true);
		return array_keys($ratings);
	}
	
	/**
	 * Function to sort items based on 'sim' value
	 * @param unknown $a
	 * @param unknown $b
	 * @return number
	 */
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
	 * Computes suggestions based on avarage of neighbors
	 * @param unknown $artistid
	 * @param unknown $neighbors
	 * @return number
	 */
	public function suggestionsAvg($artistid, $neighbors){
		$nominator = $denominator = 0;
		foreach($neighbors as $neighbor){
			if(!empty($neighbor['Rating'][$artistid])){
				$nominator += $neighbor['Rating'][$artistid];
				$denominator++;
			}
		}
		
		if($denominator == 0){
			return -1;
		}
		
		return $nominator/$denominator;
	}
	
	/**
	 * Returns suggestion by weighted avarage
	 * @param unknown $artistid
	 * @param unknown $neighbors
	 * @return number
	 */
	public function suggestionsWeightedSum($artistid, $neighbors){
		$nominator = $denominator = 0;
		foreach($neighbors as $neighbor){
			if(!empty($neighbor['Rating'][$artistid])){
				$nominator += ($neighbor['Rating'][$artistid] * $neighbor['sim']);
				$denominator += abs($neighbor['sim']);
			}
		}
		
		if($denominator == 0){
			return -1;
		}
	
		return $nominator/$denominator;
	}
	
	public function filter($x, $items = array(), $offset = 0){
		$result = array();
		foreach($items as $key=>$value){
			if($x === $value || ($x === $value+$offset)){
				$result[$key] = $value;
			}
		}
		return $result;
	}
	
	/**
	 * Calculates similarity based on extreme values
	 * @param unknown $item1 - Rating set 1
	 * @param unknown $item2 - Rating set 2
	 * @return number - Similarity between two users
	 */
	public function calculateXtreme($item1, $item2, $extra_options = array()){
		$max = max($item1);
		$min = min($item1);
		
		if(!empty($extra_options['offset'])){
			$offset = $extra_options['offset'];
			$max_items = $this->filter($max, $item1, -$offset);
			$min_items = $this->filter($min, $item1, +$offset);
		} else {
			$max_items = $this->filter($max, $item1);
			$min_items = $this->filter($min, $item1);
		}
		
		$artists_max = array_intersect(array_keys($max_items), array_keys($item2));
		$artists_min = array_intersect(array_keys($min_items), array_keys($item2));
		
		$common_artists_count = (count($artists_max) + count($artists_min));
		
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
		//debug($difference);
		return 1- (($difference /$common_artists_count) / 9);
		
	}
	
	/**
	 * Calculate Pearson similarity for two rating sets
	 * @param unknown $user1
	 * @param unknown $user2
	 * @return number
	 */
	public function calculatePearson($item1, $item2, $extra_options = array()){
		// Items rated in both sets
		
		$items = array_keys(array_intersect_key($item1, $item2));

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
	
	public function calculateCosine($user1, $user2, $extra_options = array()){
		
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
		if( (sqrt($sumUser1) * sqrt($sumUser2)) == 0){
			debug($user1);
			debug($user2);
		}
		
		return $sumCombined / (sqrt($sumUser1) * sqrt($sumUser2));
	}	
	
	public function calculateAdjustedCosine($item1, $item2){
		// Items that are rated in both sets
		//$items = array_intersect(array_keys($item1), array_keys($item2));
		$items = array_keys(array_intersect_key($item1, $item2));
		
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
