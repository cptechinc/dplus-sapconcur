<?php 
	namespace dplus\sapconcur;
	
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
                'ID'         => array('dbcolumn' => 'concurID', 'required' => false),
                'Level1Code' => array('dbcolumn' => 'ItemID', 'required' => false),
                'listID'     => array('dbcolumn' => 'listID', 'required' => false),
                'Name'       => array('dbcolumn' => 'ItemDescription', 'required' => false, 'strlen' => 64)
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
			$concurinventory = $this->get_concurinventory();
			$concuritemIDs = array_column($concurinventory, 'ID', 'Level1Code');
			
			foreach (array_keys($concuritemIDs) as $itemID)  {
				$this->log_sendlogitem($itemID);
			}
		}
		
		/**
		 * Handles both Updating and Creating Items for Concur
		 * @param  bool   $forceupdates Send only the Updated Items or Force Send All ?
		 * @return array                Responses for each send
		 */
		public function batch_inventory($forceupdates = false) {
			$updatedafter = $forceupdates ? date('Y-m-d', strtotime('-1 days')) : '';
			$existinginventory = get_itemsinsendlog($updatedafter);
			$newinventory = get_itemsnotinsendlog();
			
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
			$url = !empty($url) ? new Purl\Url($url) : new Purl\Url($this->endpoints['list-items']);
			$url->query->set('listID', $this->listID);
			$url->query->set('limit', '100');
			return $this->get_curl($url->getUrl());
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
		
		
		/* =============================================================
			INTERNAL CLASS FUNCTIONS
		============================================================ */
		/**
		 * Parses Response and logs the successful item Creation / Update into the sendlog
		 * while failured get logged
		 * @return void
		 */
		protected function process_response() {
			$this->response['Message'] = isset($this->response['Message']) ? $this->response['Message'] : '';
			$this->response['error'] = (strpos(strtolower($this->response['Message']), 'invalid') !== false) ? true : $this->response['error'];
			$this->response['error'] = (strpos(strtolower($this->response['Message']), 'the item code was specified more than once') !== false) ? true : $this->response['error'];
			
			if (!$this->response['error']) { // IF Error is False
				if (in_array($this->response['http_code'], array('200', '201', '202', '204'))) {
					$this->log_sendlogitem($this->request['Level1Code']);
				}
			} else {
				$this->log_error($this->response['Message']);
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
		 * Iterates through an array of Items and sends the Create Item Request
		 * @param  array $items  item_list table item
		 * @return array         Responses for each Item Request
		 */
		protected function create_items($items) {
			$response = array();
			foreach ($items as $item) {
				$response[] = $this->create_listitem($this->listID, $item);
			}
			return $response;
		}
		
		/**
		 * Iterates through an array of Items and sends the Update Item Request
		 * @param  array $items  item_list table item
		 * @return array         Responses for each Item Request
		 */
		protected  function update_items($items) {
			$response = array();
			foreach ($items as $item) {
				$response[] = $this->update_listitem($this->listID, $item);
			}
			return $response;
		}
	}
