<?php
App::uses('AppModel', 'Model');
App::uses('Lastfm', 'Model');
/**
 * Artist Model
 *
 * @property Fan $Fan
 */
class Artist extends AppModel {
	
	public $actsAs = array('Containable');
	

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'name';

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array('Fan', 'Listener', 'Rating');	
	
	public function fetch_fans($artist) {
		if(is_string($artist)){
			$artist = $this->findByName($artist);
		} else if(is_integer($artist)){
			$artist = $this->findById($artist);
		}
		$this->Fan->deleteAll(array('artist_id' => $artist['Artist']['id']));
		$this->Lastfm = new Lastfm();
		$params = array(
				'method' => 'artist.getTopFans',
				'artist' => $artist['Artist']['name'],
				'limit' => 100
		);
		$fans = $this->Lastfm->find('all',array( 'conditions'=>$params));
		$data = array();
		$fansdata = array();
		foreach($fans['Lastfm']['topfans']['user'] as &$fan){
			//$fan['artist_id'] = $artist['Artist']['id'];
			//$fan['image'] = $fan['image'][1]['#text'];
			$data[] = array(
					'Artist' => array('id' => $artist['Artist']['id']),
					'Fan'=>array('weight'=>$fan['weight']),
					'User' => array('name' => $fan['name'])
			);
			$fansdata[] = $fan['name'];
		}
		$this->Fan->saveAll($data, array('deep'=>true));
		
		
		
		return $fansdata;
		
	}
	
	public function exists($id = null) {
		if(empty($this->data['Artist']['name'])){
			return parent::exists($id);
		}

		$artistName = $this->data['Artist']['name'];
		$artist = $this->findByName($artistName);
		if(!empty($artist)){
			$this->id = $artist['Artist']['id'];
		} else {
			$this->Lastfm = new Lastfm();
			$params = array(
					'method' => 'artist.getInfo',
					'artist' => $artistName,
					'autocorrect' => 1);
			
			$data = $this->Lastfm->find('all',array( 'conditions'=>$params));
			$artist = $this->findByName($data['Lastfm']['artist']['name']);
			if(!empty($artist)){
				$this->id = $artist['Artist']['id'];
				return;
			}
			
			$this->data['Artist']['name'] = $data['Lastfm']['artist']['name'];
			$this->data['Artist']['image'] = $data['Lastfm']['artist']['image'][2]['#text'];
			$this->data['Artist']['listeners'] = $data['Lastfm']['artist']['stats']['listeners'];
			$this->data['Artist']['playcount'] = $data['Lastfm']['artist']['stats']['playcount'];
		}
		return !empty($artist);
	}

}
