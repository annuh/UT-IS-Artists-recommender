<?php
App::uses('AppModel', 'Model');

class Rating extends AppModel {
	
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
		'Artist', 'Respondent'
	);	

}
