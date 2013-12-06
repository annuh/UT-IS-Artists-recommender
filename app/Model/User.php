<?php
App::uses('AppModel', 'Model');
App::uses('Lastfm', 'Model');

/**
 * Fan Model
 *
 * @property Artist $Artist
 * @property LibraryArtist $LibraryArtist
 */
class User extends AppModel {

	public $actsAs = array('Containable');
	
/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'name';

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
	
	public $hasMany = array(
		'Fan', 'Listener'
	);
	
	
	public function exists($id = null) {
		
		if(empty($this->data['User']['name'])){
			return parent::exists($id);
		}
		
		$username = $this->data['User']['name'];
		$user = $this->findByName($username);
		if(!empty($user)){
			$this->id = $user['User']['id'];
			//$this->data['Artist']['id'] = $artis['Artist']['id'];
		} else {
			//App::import('Component','Lastfm');
			//$this->Lastfm = new LastfmComponent();
			//$this->Lastfm->init(Configure::read('Lastfm.key'), Configure::read('Lastfm.secret'));
			$this->Lastfm = new Lastfm();
			$params = array(
					'method' => 'user.getInfo',
					'user' => $username,);
				
			$data = $this->Lastfm->find('all',array( 'conditions'=>$params));
			$this->data['User']['name'] = $data['Lastfm']['user']['name'];
			$this->data['User']['image'] = $data['Lastfm']['user']['image'][1]['#text'];
			$this->data['User']['url'] = $data['Lastfm']['user']['url'];
			$this->data['User']['country'] = $data['Lastfm']['user']['country'];
			$this->data['User']['age'] = $data['Lastfm']['user']['age'];
			$this->data['User']['gender'] = $data['Lastfm']['user']['gender'];
			$this->data['User']['playcount'] = $data['Lastfm']['user']['playcount'];
			$this->data['User']['registered'] = $data['Lastfm']['user']['registered']['#text'];
		}
		return !empty($user);
	}
	
	public function fetch_artists($username, $id = null){
		if($id == null){
			$id = $this->field('id', array('User.name'=>$username));
		}
		$this->Lastfm = new Lastfm();
		$params = array(
				'method' => 'library.getArtists',
				'user' => $username,
				'limit' => 10
		);
		
		$artists = $this->Lastfm->find('all',array( 'conditions'=>$params));
		$artists = $artists['Lastfm'];
		$data = array();
		
		foreach($artists['artists']['artist'] as &$artist){
			$data[] = array(
				'User' => array('id' => $id),
				'playcount' =>$artist['playcount'],
				'Artist' => array('name' => $artist['name'])
				
			);
		}
		$this->Listener->saveAll($data, array('deep' => true));
		
		return String::toList(array_slice(Set::extract('{n}.name', $artists['artists']['artist']),0, 3));
	}

}
