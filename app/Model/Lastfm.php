<?php
App::uses('AppModel', 'Model');
/**
 * Artist Model
 *
 * @property Fan $Fan
 */
class Lastfm extends AppModel {

	public $useDbConfig = 'lastfm';
	
	public function beforeFind($query){
		CakeLog::write('lastfm', implode(',', $query['conditions']));
		return true;
	}
	

}
