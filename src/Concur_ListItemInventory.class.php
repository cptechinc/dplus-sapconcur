<?php 
	namespace Dplus\SapConcur;
	
	/**
	 * Class to handle dealing with List Items for Alumacraft's Inventory
	 */
	class Concur_ListItemInventory extends Concur_ListItem {
		use StructuredClassTraits;
		
		/**
		 * Structure of Purchase Order
		 * @var array
		 */
		protected $structure = array(
			'header' => array(
                'ID'         => array('dbcolumn' => 'concurID'),
                'Level1Code' => array('dbcolumn' => 'ItemID'),
                'listID'     => array('dbcolumn' => 'listID'),
                'Name'       => array('dbcolumn' => 'ItemDescription', 'strlen' => 64)
            )
		);
		
		/* =============================================================
			PUBLIC CLASS FUNCTIONS
		============================================================ */
		/**
		 * Imports a list of sent Items into the sendlog_item_list table
		 * @return void
		 */
		public function import_concurinventory() {
			$concuritemIDs = $this->get_concurinventoryitemids();
			
			foreach (array_keys($concuritemIDs) as $itemID)  {
				$this->log_sendlogitem($itemID);
			}
			return array(
				'count' => sizeof($concuritemIDs),
				'items' => $concuritemIDs
			);
		}
		
		/**
		 * Handles both Updating and Creating Items for Concur
		 * @param  bool   $forceupdates Send only the Updated Items or Force Send All ?
		 * @return array                Responses for each send
		 */
		public function batch_inventory($forceupdates = false) {
			$updatedafter = $forceupdates ? date('Y-m-d', strtotime('-1 days')) : '';
			$existinginventory = get_itemidsinsendlog($updatedafter);
			$newinventory = get_itemidsnotinsendlog();
			
			$response = array(
				//'updated' => $this->update_items($existinginventory),
				'created' => $this->create_items($newinventory)
			);
			return $response;
		}
		
		/* =============================================================
			ERROR CODES AND POSSIBLE SOLUTIONS
		============================================================ */
		
		/* =============================================================
			CONCUR INTERFACE FUNCTIONS
		============================================================ */
        /**
		 * Sends GET Request to retreive Inventory List by ID
		 * // Example URL https://www.concursolutions.com/api/v3.0/common/listitems/?listID=gWvhrLa3BoKEGwQA$plZyPLJpe2Jy$pse9YAw
		 * @param  string $url URL FROM Next Page response
		 * @return array       Response from Concur
		 */
		public function get_inventory($url = '') {
			$url = !empty($url) ? new \Purl\Url($url) : new \Purl\Url($this->endpoints['list-items']);
			$url->query->set('listID', $this->listID);
			$url->query->set('limit', '100');
			$response = $this->curl_get($url->getUrl());
			return $response['response'];
		}
		
		/**
		 * Returns an array of Inventory Item IDs from Concur
		 * @return array Inventory Item IDs
		 */
		public function get_concurinventory() {
			$response = $this->get_inventory();
			$inventory = $response['Items'];
			
			while (!empty($response['NextPage'])) {
				$response = $this->get_inventory($response['NextPage']);
				$inventory = array_merge($inventory, $response['Items']);
			}
			return $inventory;
		}
		
		public function get_concurinventoryitemids() {
			$response = $this->get_inventory();
			$inventory = array_column($response['Items'], 'ID', 'Level1Code');
			
			while (!empty($response['NextPage'])) {
				$response = $this->get_inventory($response['NextPage']);
				$inventory = array_merge($inventory, array_column($response['Items'], 'ID', 'Level1Code'));
			}
			return $inventory;
		}
		
		
		/* =============================================================
			INTERNAL CLASS FUNCTIONS
		============================================================ */
		/**
		 * Checks if Item has been sent, then creates it or updates it as needed
		 * @param  string $itemID Item ID
		 * @return array          API response
		 */
		public function send_inventoryitem(string $itemID) {
			$item = get_item($itemID);
			if (does_itemhavesendlog($itemID)) {
				return $this->create_listitem($this->listID, $item);
			} else {
				return $this->update_listitem($this->listID, $item);
			}
		}
		/**
		 * Parses Response and logs the successful item Creation / Update into the sendlog
		 * while failured get logged
		 * @return void
		 */
		protected function process_response() {
			$this->response['response']['Message'] = isset($this->response['response']['Message']) ? $this->response['response']['Message'] : '';
			$this->response['response']['error'] = (strpos(strtolower($this->response['response']['Message']), 'invalid') !== false) ? true : $this->response['response']['error'];
			$this->response['response']['error'] = (strpos(strtolower($this->response['response']['Message']), 'the item code was specified more than once') !== false) ? true : $this->response['response']['error'];
			$this->response['response']['Level1Code'] = $this->request['Level1Code'];
			
			if (!$this->response['response']['error']) { // IF Error is False
				if (in_array($this->response['server']['http_code'], array('200', '201', '202', '204'))) {
					$this->log_sendlogitem($this->request['Level1Code']);
				}
			} else {
				$this->log_error($this->response['response']['Message']);
			}
		}
		
		/**
		 * Adds or Updates send log for an Item ID
		 * @param  string $itemID Item ID
		 * @return bool           Was Item Able to be added / updated in the send log
		 */
		protected function log_sendlogitem($itemID) {
			if (does_itemhavesendlog($itemID)) {
				return update_sendlogitem($itemID, date('Y-m-d H:i:s'));
			} else {
				return insert_sendlogitem($itemID, date('Y-m-d H:i:s'));
			}
		}
		
		/**
		 * Iterates through an array of Item IDs and sends the Create Item Request
		 * @param  array $itemIDs Item IDs
		 * @return array          Responses for each Item Request
		 */
		protected function create_items($itemIDs) {
			$responses = array();
			foreach ($itemIDs as $itemID) {
				$item = get_item($itemID);
				$item_response = $this->create_listitem($this->listID, $item);
				$responses[] = $item_response;
			}
			return $responses;
		}
		
		/**
		 * Iterates through an array of Item IDs and sends the Update Item Request
		 * @param  array $itemIDs Item IDs
		 * @return array          Responses for each Item Request
		 */
		protected  function update_items($itemIDs) {
			$responses = array();
			foreach ($itemIDs as $itemID) {
				$item = get_item($itemID);
				$item_response = $this->update_listitem($this->listID, $item);
				$responses[] = $item_response;
			}
			return $responses;
		}
	}
