<?php
App::uses('AppModel', 'Model');
App::uses('LastfmComponent', 'Controller/Component');

/**
 * Fan Model
 *
 * @property Artist $Artist
 * @property LibraryArtist $LibraryArtist
 */
class Fan extends AppModel {
	
	public $actsAs = array('Containable');
	

/**
 * Display field
 *
 * @var string
 */

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
	
	public $belongsTo = array(
		'Artist', 'User'
	);	

}
