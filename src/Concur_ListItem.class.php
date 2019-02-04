<?php 
	namespace Dplus\SapConcur;
	
	/**
	 * Class to handle dealing with List Items
	 */
	class Concur_ListItem extends Concur_Endpoint {
		use StructuredClassTraits;
		
		protected $endpoints = array(
			'list-items' => 'https://www.concursolutions.com/api/v3.0/common/listitems'
		);
		
		/**
		 * Structure of Purchase Order
		 * @var array
		 */
		protected $structure = array(
			'header' => array(
                'ID'          => array(),
                'Level1Code'  => array(),
                'Level2Code'  => array(),
                'Level3Code'  => array(),
                'Level4Code'  => array(),
                'Level5Code'  => array(),
                'Level6Code'  => array(),
                'Level7Code'  => array(),
                'Level8Code'  => array(),
                'Level9Code'  => array(),
                'Level10Code' => array(),
                'listID'      => array(),
                'Name'        => array('strlen' => 64),
                'ParentID'    => array(),
                'URI'         => array(),
            )
		);
		
		/**
		 * List ID
		 * @var string
		 */
		protected $listID;
		
		/* =============================================================
			CONCUR INTERFACE FUNCTIONS
		============================================================ */
        /**
		 * Sends GET Request to retreive List by ID
		 * // Example URL https://www.concursolutions.com/api/v3.0/common/listitems/?listID=gWvhrLa3BoKEGwQA$plZyPLJpe2Jy$pse9YAw
		 * @param  string $id List ID
		 * @return array      Response from Concur
		 */
		public function get_list($id) {
			$url = new \Purl\Url($this->endpoints['list-items']);
            $url->query->set('listID', $id);
			return $this->get_curl($url->getUrl());
		}
        
        /**
		 * Sends GET Request to retreive list item by ID
		 * // Example : https://www.concursolutions.com/api/v3.0/common/listitems/?listID=gWoOk4$p8qPNb8y5o2wnWKByWYG1zauXN7fA
		 * @param  string $listitemID List Item ID
		 * @return array              Response from Concur
		 */
		public function get_listitem($listitemID) {
			$url = $this->endpoints['list-items'] . "/$listitemID";
			return $this->get_curl($url);
		}
        
        /**
         * Sends POST Request to create list item
         * @param  string $listID  Concur List Item ID
         * @param  array  $item    Item Key Value array to send
         * @return array           Array Response
         */
        public function create_listitem(string $listID, array $item) {
            $listitem = $this->structure_concuritem($item);
			$this->request = $listitem;
            $this->response = $this->curl_post($this->endpoints['list-items'], $listitem, $json = true);
			$this->process_response();
			return $this->response['response'];
        }
        
        /**
         * Sends PUT Request to update list item
         * @param  string $listID   Concur List Item ID
         * @param  array  $item     Item Key Value array to send
         * @return array            Array Response
         */
        public function update_listitem(string $listID, array $item) {
            $listitem = $this->structure_concuritem($item);
			$this->request = $listitem;
            $this->response = $this->curl_put($this->endpoints['list-items'] . "/$listID", $listitem, $json = true);
			$this->process_response();
			return $this->response['response'];
        }
		
        
		/* =============================================================
			ERROR CODES AND POSSIBLE SOLUTIONS
		============================================================ */
		
		
		/* =============================================================
			CLASS FUNCTIONS
		============================================================ */
		/**
		 * Returns Item Array in the Concur Structure
		 * // NOTE This also sets listID, and Name properties
		 * @param  array $item  Item Array based of item_list table
		 * @return array        Concur Structure for Item
		 */
		public function structure_concuritem($item) {
			$concuritem = $this->create_sectionarray($this->structure['header'], $item);
			$concuritem['listID'] = $this->listID;
			$concuritem['Name'] = !empty(trim($concuritem['Name'])) ? $concuritem['Name'] : $concuritem['Level1Code'];
			return $concuritem;
		}
		
	}
