<?php
App::uses('AppController', 'Controller');
/**
 * Albums Controller
 *
 * @property Album $Album
 * @property PaginatorComponent $Paginator
 */
class ArtistsController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'RequestHandler');
	public $uses = array('Artist', 'User', 'Lastfm');

	public function index(){
			$this->Paginator->settings = array(
				'limit' => 20,
				'order' => array('Artist.name' => 'ASC'),
				'contain'=> array('Listener')
		);
	 	$artists = $this->Paginator->paginate('Artist');
		$this->set(compact('artists'));
	}
	
	public function view($id){
		if (!$this->Artist->exists($id)) {
			throw new NotFoundException(__('Invalid Artist'));
		}
		
		$this->set('artist', $this->Artist->findById($id));
	}

	public function add() {
		if ($this->request->is('post')) {
			$names = array_map('trim', explode(',', $this->request->data['Artist']['name']));
			$ids = array();
			foreach($names as $name){
				$this->Artist->create();
				$data = array('Artist'=>array('name'=>$name, 'fetched'=>true));
				if ($this->Artist->save($data)) {
					$ids[] = $this->Artist->id;
				} else {
					$this->Session->setFlash(__('The album could not be saved. Please, try again.'));	
				}
			}
			if(sizeof($ids) > 1){
				return $this->redirect(array('action' => 'import_fans_multiple', implode(",",$ids)));
			} else {
				return $this->redirect(array('action' => 'import_fans', $this->Artist->id));
			}
		}
	}
	
	public function import_fans_multiple($ids){
		$artists = array_map('trim', explode(',', urldecode($ids)));
		$artists = $this->Artist->find('all', array('conditions'=>array('Artist.id'=>$artists)));
		$this->set(compact('artists'));
	}
	
	
	public function import_fans($id){
		$artist = $this->Artist->findById($id);
		if(!$artist){
			throw new NotFoundException();
		}
		$this->Artist->fetch_fans($artist);
		
		/*
		$this->Lastfm->init(Configure::read('Lastfm.key'), Configure::read('Lastfm.secret'));
		$params = array(
				'artist' => $artist['Artist']['name'],
		);
		$fans = $this->Lastfm->get('artist.getTopFans', $params);
		$data = array();
		foreach($fans['topfans']['user'] as &$fan){
			//$fan['artist_id'] = $artist['Artist']['id'];
			//$fan['image'] = $fan['image'][1]['#text'];
			$data[] = array(
				'Artist' => array('id' => $id),
				'Fan'=>array('weight'=>$fan['weight']),
				'User' => array('name' => $fan['name'])
			);
		}
		$this->Artist->Fan->saveAll($data, array('deep'=>true));
		*/
		$params = array(
				'method' => 'artist.getTopFans',
				'artist' => $artist['Artist']['name'],
				'limit' => 100000
		);
		$fans = $this->Lastfm->find('all',array( 'conditions'=>$params));
		$artist['Fan'] = $fans['Lastfm']['topfans']['user'];
		$artist = $this->Artist->find('first', array('conditions'=>array('Artist.id'=>$id), 'contain'=>array('Fan'=>array('User'))));
		$this->set(compact('artist'));
	}
	
	public function get_fan_artists(){
		$user = $this->request->data('user');
		$user_id = $this->request->data('user_id');
		$artists = $this->User->fetch_artists($user, $user_id);
		$this->set('artists', $artists);
		$this->set('_serialize', array('artists'));
	}
	
	public function get_artist_fans(){
		$artist = $this->request->data('artist');
		$fans = $this->Artist->fetch_fans($artist);
		$this->set('fans', $fans);
		$this->set('_serialize', array('fans'));
	}

/**
 * admin_edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		
		if (!$this->Album->exists($id)) {
			throw new NotFoundException(__('Invalid album'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->Album->save($this->request->data)) {
				$this->Session->setFlash(__('The album has been saved.'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The album could not be saved. Please, try again.'));
			}
		} else {
			$options = array('conditions' => array('Album.' . $this->Album->primaryKey => $id));
			$this->request->data = $this->Album->find('first', $options);
		}
		$users = $this->Album->User->find('list');
		$this->set(compact('users'));
	}

/**
 * admin_delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_delete($id = null) {
		$this->Album->id = $id;
		if (!$this->Album->exists()) {
			throw new NotFoundException(__('Invalid album'));
		}
		$this->request->onlyAllow('post', 'delete');
		if ($this->Album->delete()) {
			$this->Session->setFlash(__('The album has been deleted.'));
		} else {
			$this->Session->setFlash(__('The album could not be deleted. Please, try again.'));
		}
		return $this->redirect(array('action' => 'index'));
	}
	
	public function uploadImages(){
		$files = array();
		if($this->Picture->saveAll($this->request->data)){
			$files[] = $this->request->data['Picture']['filename'];
			
		}
		
		$this->set('files', $files);
		$this->set('_serialize', array('files'));
	}
}