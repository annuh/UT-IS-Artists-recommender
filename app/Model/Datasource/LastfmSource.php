<?php
App::uses('HttpSocket', 'Network/Http');

/**
 * LastFM Datasource
 * 
 * @package cake-bits
 * @subpackage datasource
 */
class LastfmSource extends DataSource {
	
	public $config = array(
			'apikey' => '',
	);
	
	public function __construct($config) {
		parent::__construct($config);
		$this->Http = new HttpSocket();
	}
	
	public function listSources($data = null) {
		return null;
	}
	
	private $apiurl = 'http://ws.audioscrobbler.com/2.0/';
	
	private $authurl = 'http://www.last.fm/api/auth/';
	
	private $apikey = null;
	
	private $apisecret = null;
	
	/**
	 * Initializes the last.fm API
	 *
	 * @param $apikey The API key for last.fm
	 * @param $apisecret The API secret for last.fm
	 * 	 */
	public function init($apikey, $apisecret) {
		$this->apikey = $apikey;
		$this->apisecret = $apisecret;
	}
	
	/**
	 * Request the last.fm api
	 *
	 * @param $method The method of the API to call
	 * @param $params The API params
	 * @param $signed If the API method should be signed
	 * @param $post If post should be used instead of get (required for signed requests)
	 * @return An array containing the requested data
	 */
	public function get($method, $params = null, $signed = false, $post = false) {
		$p = "format=json&api_key=$this->apikey&method=$method";
		if ($params!=null) {
			foreach ($params as $key => $value) {
				$p .= "&$key=" . urlencode($value);
			}
		}
		if ($signed) {
			$orderedParams = array_merge($params, array('api_key' => $this->apikey, 'method' => $method));
			ksort($orderedParams);
			$str = '';
			foreach ($orderedParams as $key => $value) {
				$str .= $key . $value;
			}
			$str .= $this->apisecret;
			$p .= "&api_sig=" .  md5($str);
		}
		if ($post) {
			$result = $this->get_data($this->apiurl, $p);
		} else {
			$result = $this->get_data($this->apiurl . '?' . $p);
		}
	
		return json_decode($result, true);
	}
	
	public function read(Model $model, $queryData = array(), $recursive = null) {
		
		$queryData['conditions']['api_key'] = $this->config['apikey'];
		$queryData['conditions']['format'] = 'json';
		$json = $this->Http->get($this->apiurl, $queryData['conditions']);
		$res = json_decode($json, true);
		if (is_null($res)) {
			$error = json_last_error();
			throw new CakeException($error);
		}
		return array($model->alias => $res);
	}
	
	
	/**
	 * Send your user to last.fm/api/auth with your API key as a parameter.
	 *
	 * @param $callback_url You can optionally specify a callback URL that is different to your API Account callback url. Include this as a query param cb. This allows you to have users forward to a specific part of your site after the authorisation process.
	 */
	public function authorize($callback_url = null) {
		$url = "$this->authurl?api_key=$this->apikey";
		if ($callback_url!=null) {
			$url .= "&cb=$callback_url";
		}
		header("Location: $url");
		exit();
	}
	
	/**
	 * Fetch a session key for a user.
	 *
	 * @param $token A 32-character ASCII hexadecimal MD5 hash returned after calling the authorize() method
	 */
	public function getSession($token) {
		return $this->get('auth.getSession', array('token' => $token), true);
	}
	
	/**
	 * Performs a cURL request on the given URL and returns the response.
	 *
	 * @param $url The URL to perform the cURL request to
	 * @param $post $_POST data to post to the URL
	 * @return The response of the given URL
	 */
	private function get_data($url, $post = null)
	{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		if ($post != null) {
			curl_setopt($ch, CURLOPT_POST, (count(explode('&', $post))+1));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
}
