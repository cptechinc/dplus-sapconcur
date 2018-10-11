<?php 
	namespace Dplus\SapConcur;
	
	/**
	 * Class for getting Authentication through Concur's API
	 */
	class Concur_Authentication extends Concur_Endpoint {
		protected $endpoints = array(
			'authentication' => 'https://us.api.concursolutions.com/oauth2/v0/token'
		);
		
		/**
		 * Concur Access Token
		 * @var string
		 */
		static $authtoken;
		
		/**
		 * Time Stamp of when access token Expires
		 * @var int
		 */
		static $tokenexpires;
		
		/**
		 * Sends POST cURL request
		 * @param  string $url  URL to make request to
		 * @param  array  $body Key Value array to send
		 * @return array        Response from Endpoint or response from cURL
		 */
		protected function curl_post($url, $body) {
			$curl = new \Dplus\Base\Curl();
			$curl->add_acceptheader('json');
			$curl->set_contenttype('url');
			return $curl->post($url, $body);
		}
		
		/**
		 * Gets and Sets the authentication token for accessing the Concur API
		 * @return array JSON Response
		 */
		public function create_authenticationtoken() {
			$appconfig = \DplusWire::wire('pages')->get('/config/');
			$body = [
				'client_id' => $appconfig->client_id,
				'client_secret' => $appconfig->client_secret,
				'grant_type' => 'password',
				'username' => $appconfig->concur_username,
				'password' => $appconfig->concur_password,
				'credtype '=>'password',
			];
			
			$this->response = $this->curl_post($this->endpoints['authentication'], $body);
			$this->accesstoken = $this->response['server']['error'] ? false : $this->response['response']['access_token'];
			self::$authtoken = $this->response['server']['error'] ? false : $this->response['response']['access_token'];
			self::$tokenexpires = $this->response['server']['error'] ? false : strtotime('now') + 3600;
			return $this->response;
		}
	}
