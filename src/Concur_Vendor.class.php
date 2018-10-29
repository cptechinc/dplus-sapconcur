<?php 
	namespace Dplus\SapConcur;
	
	/**
	 * Class to handle dealing with Vendors 
	 */
	class Concur_Vendor extends Concur_Endpoint {
		use StructuredClassTraits;
		
		protected $endpoints = array(
			'vendor' => 'https://www.concursolutions.com/api/v3.0/invoice/vendors'
		);
		
		/**
		 * Structure for Vendor Array
		 * @var array
		 */
		protected $structure = array(
			'header' => array(
				'VendorCode' 						=> array('dbcolumn' => '', 'required' => false),
				'VendorName' 						=> array('dbcolumn' => '', 'required' => false),
				'AddressCode' 						=> array('dbcolumn' => '', 'required' => false),
				'Address1' 							=> array('dbcolumn' => '', 'required' => false),
				'Address2' 							=> array('dbcolumn' => '', 'required' => false),
				'Address3' 							=> array('dbcolumn' => '', 'required' => false),
				'City' 								=> array('dbcolumn' => '', 'required' => false),
				'State' 							=> array('dbcolumn' => '', 'required' => false),
				'PostalCode' 						=> array('dbcolumn' => '', 'required' => false),
				'CountryCode' 						=> array('dbcolumn' => '', 'required' => false, 'strlen' => 2),
				'Country' 							=> array('dbcolumn' => 'CountryCode', 'required' => false),
				'Approved' 							=> array('dbcolumn' => '', 'required' => false),
				'PaymentTerms' 						=> array('dbcolumn' => '', 'required' => false),
				'AccountNumber' 					=> array('dbcolumn' => '', 'required' => false),
				'TaxID' 							=> array('dbcolumn' => '', 'required' => false),
				'ProvincialTaxID' 					=> array('dbcolumn' => '', 'required' => false),
				'TaxType' 							=> array('dbcolumn' => '', 'required' => false),
				'CurrencyCode' 						=> array('dbcolumn' => '', 'required' => false, 'default' => 'USD'),
				'ShippingMethod' 					=> array('dbcolumn' => '', 'required' => false),
				'ShippingTerms' 					=> array('dbcolumn' => '', 'required' => false),
				'DiscountTermsDays' 				=> array('dbcolumn' => '', 'required' => false),
				'DiscountPercentage' 				=> array('dbcolumn' => '', 'required' => false),
				'ContactFirstName' 					=> array('dbcolumn' => '', 'required' => false),
				'ContactLastName' 					=> array('dbcolumn' => '', 'required' => false),
				'ContactPhoneNumber' 				=> array('dbcolumn' => '', 'required' => false),
				'ContactEmail' 						=> array('dbcolumn' => '', 'required' => false),
				'PurchaseOrderContactFirstName' 	=> array('dbcolumn' => '', 'required' => false),
				'PurchaseOrderContactLastName' 		=> array('dbcolumn' => '', 'required' => false),
				'PurchaseOrderContactPhoneNumber' 	=> array('dbcolumn' => '', 'required' => false),
				'PurchaseOrderContactEmail' 		=> array('dbcolumn' => '', 'required' => false),
				'DefaultEmployeeID' 				=> array('dbcolumn' => '', 'required' => false),
				'DefaultExpenseTypeName' 			=> array('dbcolumn' => '', 'required' => false),
				'PaymentMethodType' 				=> array('dbcolumn' => '', 'required' => false),
				'AddressImportSyncID' 				=> array('dbcolumn' => '', 'required' => false)
			)
		);
		
		/* =============================================================
			EXTERNAL / PUBLIC FUNCTIONS
		============================================================ */
		/**
		 * Imports a list of sent vendorIDs  into the sendlog_vendor table
		 * @return void
		 */
		public function import_concurvendors() {
			$concurvendors = $this->get_concurvendors();
			$concurvendorIDs = array_column($concurvendors, 'VendorCode');
			
			foreach ($concurvendorIDs as $vendorID)  {
				$this->log_sendlogvendor($vendorID);
			}
		}
		
		 /**
 		 * Handles both Updating and Creating Vendors for Concur
 		 * @param  bool   $forceupdates Send only the Updated Items or Force Send All ?
 		 * @return array                Responses for each send
 		 */
		public function batch_vendors($forceupdates = false) {
			$updatedafter = $forceupdates ? date('Y-m-d', strtotime('-1 days')) : '';
			$existingvendors = get_vendorIDsinsendlog($updatedafter);
			$newvendors = get_vendorIDsnotinsendlog();
			$response = array(
				'updated' => $this->update_vendors($existingvendors),
				'created' => $this->create_vendors($newvendors)
			);
			$this->response = $response;
			return $this->response;
		}
		
		/**
		 * Verifies if Vendor Exists at Concur
		 * If it exists then it updates the Vendor
		 * If not, it will create the Vendor
		 * @param  string $vendorID  Vendor Code
		 * @return void
		 */
		public function send_vendor($vendorID) {
			if (does_vendorhavesendlog($vendorID)) {
				return $this->update_vendor($vendorID);
			} else {
				return $this->create_vendor($vendorID);
			}
		}
		
		/* =============================================================
			CONCUR INTERFACE FUNCTIONS
		============================================================ */
		/**
		 * Sends GET request for vendors and returns the response
		 * @param  string $url URL to fetch Vendors
		 * @return array       Response
		 */
		public function get_vendors($url = '') {
			$url = !empty($url) ? new \Purl\Url($url) : new \Purl\Url($this->endpoints['vendor']);
			$url->query->set('limit', '1000');
			$body = '';
			return $this->get_curl($url->getUrl(), $body);
		}
		
		/**
		 * Returns an array of Existing Vendor Codes
		 * @return array Vendor Codes
		 */
		public function get_concurvendors() {
			$response = $this->get_vendors();
			$vendorlist = $response['Vendor'];
			
			while (!empty($response['NextPage'])) {
				$response = $this->get_vendors($response['NextPage']);
				$vendorlist = array_merge($vendorlist, $response['Vendor']);
			}
			return $vendorlist;
		}
		
		/**
		 * Sends a GET request for a vendor and returns the response
		 * @param  string $vendorID Vendor ID to grab Vendor
		 * @return array            Response
		 */
		public function get_vendor($vendorID) {
			$url = new \Purl\Url($this->endpoints['vendor']);
			$url->query->set('vendorCode', $vendorID);
			$response = $this->curl_get($url->getUrl());
			return $response['response'];
		}
		
		/**
		 * Sends request for Getting Vendor and sees if there's one vendor that matches 
		 * with the vendorCode field
		 * @param  string $vendorID Vendor ID to validate
		 * @return bool             Does Vendor Exist at Concur
		 */
		public function does_concurvendorexist($vendorID) {
			$vendors = $this->get_vendor($vendorID);
			return ($vendors['TotalCount']) ? true : false;
		}
		
		/**
		 * Sends a POST request to create Vendor at Concur
		 * @param  string $vendorID Vendor ID to use to load from database
		 * @return array            Response
		 */
		public function create_vendor($vendorID) {
			$dbvendor = get_dbvendor($vendorID);
			$vendor = $this->create_sectionarray($this->structure['header'], $dbvendor);
			$body = $this->create_vendorsendbody($vendor);
			$this->response = $this->curl_post($this->endpoints['vendor'], $body);
			$this->response['response']['vendorID'] = $vendorID;
			$this->process_response();
			if (!$response['response']['error']) {
				$this->log_sendlogvendor($vendorID) ;
			}
			return $response['response'];
		}
		
		/**
		 * Sends a PUT request to update current Vendor at Concur
		 * @param  string $vendorID Vendor ID to use to load from database
		 * @return array            Response
		 */
		public function update_vendor($vendorID) {
			$dbvendor = get_dbvendor($vendorID);
			$vendor = $this->create_sectionarray($this->structure['header'], $dbvendor);
			$body = $this->create_vendorsendbody($vendor);
			$body['Items'] = array();
			$this->response = $this->curl_put($this->endpoints['vendor'], $body);
			$this->response['response']['vendorID'] = $vendorID;
			$this->process_response();
			if (!$this->response['response']['error']) {
				$this->log_sendlogvendor($vendorID) ;
			}
			return $this->response['response'];
		}
		
		/* =============================================================
			INTERNAL CLASS FUNCTIONS
		============================================================ */
		protected function process_response($response) {
			if (strpos(strtolower($this->response['response']['Message']), 'missing' !== false)) {
				$this->response['response']['error'] = true;
				$this->log_error($this->response['response']['Message']);
			} elseif (isset($response['response']['Vendor'][0]['StatusList'][0])) {
				if ($response['response']['Vendor'][0]['StatusList'][0]['Type'] == 'WARNING') {
					$this->response['response']['error'] = true;
					$this->log_error($response['response']['Vendor'][0]['StatusList'][0]['Message']);
				}
			}
		}
		/**
		 * Adds or Updates send log for an Vendor ID
		 * @param  string $vendorID Vendor ID
		 * @return bool             Was $vendorID Able to be added / updated in the send log
		 */
		protected function log_sendlogvendor($vendorID) {
			if (does_vendorhavesendlog($vendorID)) {
				return update_sendlogvendor($vendorID, date('Y-m-d H:i:s'));
			} else {
				return insert_sendlogvendor($vendorID, date('Y-m-d H:i:s'));
			}
		}
		
		/**
		 * Returns the array format to send updates or adding of vendors at Concur API
		 * @return array Update Array
		 */
		protected function get_vendorsendschema() {
			return array(
				'Items' => array(),
				'NextPage' => '',
				'RequestRunSummary' => '',
				'TotalCount' => 1,
				'Vendor' => array()
			);
		}
		
		/**
		 * Returns the Send Array needed for the Vendor
		 * @param  array $vendor Concur Structured Vendor Array
		 * @return array         Vendor Send Array
		 */
		protected function create_vendorsendbody($vendor) {
			$body = $this->get_vendorsendschema();
			$body['Items'][] = $vendor;
			$body['TotalCount'] = 1;
			$body['Vendor'][] = $vendor;
			return $body;
		}
		
		/**
		 * Sends a PUT request to update current Vendors at Concur
		 * @param  array $vendorIDs  Vendor IDs
		 * @return array             Response
		 */
		protected function update_vendors(array $vendorIDs) {
			$responses = array();
			
			foreach ($vendorIDs as $vendorID) {
				$vend_response = $this->update_vendor($vendorID);
				$category = $vend_response['error'] ? 'error' : 'success';
				$responses[$category][] = $vend_response;
			}
			return $responses;
		}
		
		/**
		 * Sends a POST request to create Vendors at Concur
		 * @param  array $vendorIDs Vendor IDs to use to load from database
		 * @return array            Response
		 */
		protected function create_vendors($vendorIDs) {
			$response = array();
			
			foreach ($vendorIDs as $vendorID) {
				$vend_response = $this->create_vendor($vendorID);
				$category = $vend_response['response']['error'] ? 'error' : 'success';
				$responses[$category][] = $vend_response;
			}
			return $response;
		}
	}
