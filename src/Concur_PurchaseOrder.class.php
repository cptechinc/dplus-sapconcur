<?php 
	namespace Dplus\SapConcur;
	
	use Dplus\Base\StringerBell;
	
	/**
	 * Class to handle dealing with Purchase Orders
	 */
	class Concur_PurchaseOrder extends Concur_Endpoint {
		use StructuredClassTraits;
		
		protected $endpoints = array(
			'purchase-order' => 'https://www.concursolutions.com/api/v3.0/invoice/purchaseorders'
		);
		
		/**
		 * Structure of Purchase Order
		 * @var array
		 */
		protected $structure = array(
			'header' => array(
				'BillToAddress' => array(
					'Address1'          => array('dbcolumn' => 'billtoAddress1', 'required' => false),
					'Address2'          => array('dbcolumn' => 'billtoAddress2', 'required' => false),
					'Address3'          => array('dbcolumn' => 'billtoAddress3', 'required' => false),
					'City'              => array('dbcolumn' => 'billtoCity', 'required' => false, 'default' => 'N/A'),
					'CountryCode'       => array('dbcolumn' => 'billtoCountryCode', 'required' => false),
					'ExternalID'        => array('dbcolumn' => 'billtoID', 'required' => false),
					'Name'              => array('dbcolumn' => 'billtoName', 'required' => false),
					'PostalCode'        => array('dbcolumn' => 'billtoZip', 'required' => false),
					'StateProvince'     => array('dbcolumn' => 'billtoState', 'required' => false)
				),
				'CurrencyCode'        => array('dbcolumn' => '', 'required' => false, 'auto' => 'USD'),
				'OrderDate'           => array('dbcolumn' => '', 'required' => false, 'format' => 'date', 'date-format' => 'Y-m-d'),
				'ID'                  => array('dbcolumn' => 'PurchaseOrderNumber', 'required' => false),
				'LedgerCode'          => array('dbcolumn' => '', 'required' => false),
				'Name'                => array('dbcolumn' => 'PurchaseOrderNumber', 'required' => false),
				'PolicyExternalID'    => array('dbcolumn' => '', 'required' => false),
				'PurchaseOrderNumber' => array('dbcolumn' => '', 'required' => false),
				'ShipToAddress' => array(
					'Address1'      => array('dbcolumn' => 'shiptoAddress1', 'required' => false, 'default' => 'N/A'),
					'Address2'      => array('dbcolumn' => 'shiptoAddress2', 'required' => false, 'default' => 'N/A'),
					'City'          => array('dbcolumn' => 'shiptoCity', 'required' => false, 'default' => 'N/A'),
					'CountryCode'   => array('dbcolumn' => 'shiptoCountryCode', 'required' => false, 'default' => 'N/A'),
					'ExternalID'    => array('dbcolumn' => 'shiptoID', 'required' => false),
					'Name'          => array('dbcolumn' => 'shiptoName', 'required' => false),
					'PostalCode'    => array('dbcolumn' => 'shiptoZip', 'required' => false, 'default' => 'N/A'),
					'State'         => array('dbcolumn' => 'shiptoState', 'required' => false, 'default' => 'N/A'),
					'StateProvince' => array('dbcolumn' => 'shiptoState', 'required' => false, 'default' => 'N/A')
				),
				'VendorCode'        => array('dbcolumn' => 'vendorID', 'required' => false),
				'VendorAddressCode' => array('dbcolumn' => 'vendorID', 'required' => false),
				'DiscountTerms'     => array('dbcolumn' => '', 'required' => false),
				'DiscountPercent'   => array('dbcolumn' => '', 'required' => false),
				'PaymentTerms'      => array('dbcolumn' => '', 'required' => false)
			),
			'detail' => array(
				'AccountCode'              => array('dbcolumn' => 'ExpenseType', 'required' => false),
				'Description'              => array('dbcolumn' => '', 'required' => false),
				'ExternalID'               => array('dbcolumn' => '', 'required' => false),
				'IsReceiptRequired'        => array('dbcolumn' => '', 'required' => false, 'default' => 'Y'),
				'LineNumber'               => array('dbcolumn' => '', 'required' => false),
				'PurchaseOrderReceiptType' => array('dbcolumn' => '', 'required' => false, 'default' => 'WQTY'),
				'Quantity'                 => array('dbcolumn' => '', 'required' => false),
				'UnitPrice'                => array('dbcolumn' => '', 'required' => false),
				'SupplierPartID'           => array('dbcolumn' => '', 'required' => false),
				'Custom7'                   => array('dbcolumn' => 'ItemID', 'required' => false),
			)
		);
		
		/**
		 * Partial Error Responses to look validate Errors against
		 * @var array
		 */
		protected $error_responses = array(
			'exists'         => 'Purchase Order Cannot be created as it does exist in system',
			'does-not-exist' => 'Purchase Order Cannot be updated as it does not exist in system'
		);
		
		/* =============================================================
			EXTERNAL / PUBLIC FUNCTIONS
		============================================================ */
		/**
		 * Sends GET Request to retreive Purchase Order
		 * @param  string $ponbr Purchase Order Number
		 * @return array         Response from Concur
		 */
		public function get_purchaseorder($ponbr) {
			$url = $this->endpoints['purchase-order'] . "/$ponbr";
			$response = $this->curl_get($url);
			return $response['response'];
		}
		
		/**
		 * Verifies if Purchase Order exits at Concur 
		 * If it exists then it updates the Purchase Order
		 * IF not, it will create the Purchase Order
		 * @param  string $ponbr Purchase Order Number
		 * @return void
		 */
		public function send_purchaseorder($ponbr) {
			if ($this->does_concurpoexist($ponbr)) {
				return $this->update_purchaseorder($ponbr);
			} else {
				return $this->create_purchaseorder($ponbr);
			}
		}
		
		/**
		 * Process a batch of Purchase Orders and handle the update / create for each
		 * @param  int    $limit Number of POs to do, If 0, Then There is no limit
		 * @param  string $ponbr Purchase Order Number to start after
		 * @return array         Response for each PO Number Keyed by Purchase Order Number
		 */
		public function batch_purchaseorders($limit = 0, $ponbr = '') {
			$original_limit = $limit;
			$responses = array('created' => array(), 'updated' => array());
			$nbr_new = count_dbpurchaseordernbrsnotinsendlog($ponbr);
			$nbr_existing = count_dbpurchaseordernbrsinsendlog($ponbr);
			
			$responses['created'] = $this->create_purchaseorders($limit, $ponbr);
			$limit = max($limit - $nbr_new, 0);
			
			if ($limit > 0 || $original_limit == 0) {
				$responses['updated'] = $this->update_purchaseorders($limit, $ponbr);
			}
			
			$this->response = $responses;
			return $this->response;
		}
		
		/**
		 * Updates Purchase Orders
		 * @param  int    $limit Number of Purchase Orders to update
		 * @param  string $ponbr Purchase Order Number to start with
		 * @return array         Concur Purchase Orders Update responses
		 */
		public function update_purchaseorders($limit = 0, $ponbr = '') {
			$responses = array();
			$purchaseorders_existing = get_dbpurchaseordernbrsinsendlog($limit, $ponbr);
			
			foreach ($purchaseorders_existing as $ponbr) {
				$po_response = $this->update_purchaseorder($ponbr);
				$result = $po_response['error'] ? 'error' : 'success';
				$responses[$result][$ponbr] = $po_response;
			}
			return $responses;
		}
		
		/**
		 * Creates Purchase Orders
		 * @param  int    $limit Number of Purchase Orders to update
		 * @param  string $ponbr Purchase Order Number to start with
		 * @return array         Concur Purchase Orders Create responses
		 */
		public function create_purchaseorders($limit = 0, $ponbr = '') {
			$responses = array();
			$purchaseorders_new = get_dbpurchaseordernbrsnotinsendlog($limit, $ponbr); 
			foreach ($purchaseorders_new as $ponbr) {
				$po_response = $this->create_purchaseorder($ponbr);
				$result = $po_response['error'] ? 'error' : 'success';
				$responses[$result][$ponbr] = $po_response;
			}
			return $responses;
		}
		
		/* =============================================================
			CONCUR INTERFACE FUNCTIONS
		============================================================ */
		
		/**
		 * Verifies if Purchase Order Exists at Concur
		 * @param  string $ponbr Purchase Order Number
		 * @return bool          Does Purchase Order Number exist?
		 */
		public function does_concurpoexist($ponbr) {
			$response = $this->get_purchaseorder($ponbr);
			return (isset($response['PurchaseOrderNumber'])) ? true : false;
		}
		
		/**
		 * Sends a POST request to add Purchase Order at Concur
		 * @param  string $ponbr Purchase Order Number
		 * @return array         Response
		 */
		public function create_purchaseorder($ponbr) {
			$purchaseorder = $this->create_purchaseorderheader($ponbr);
			$purchaseorder['LineItem'] = $this->create_purchaseorderdetails($ponbr);
			$this->response = $this->curl_post($this->endpoints['purchase-order'], $purchaseorder, $json = true);
			$this->response['response']['PurchaseOrderNumber'] = isset($this->response['response']['PurchaseOrderNumber']) ? $this->response['response']['PurchaseOrderNumber'] :  $purchaseorder['ID'];
			$this->process_response();
			return $this->response['response'];
		}
		
		/**
		 * Sends a PUT request to update Purchase Order at Concur
		 * @param  string $ponbr Purchase Order Number
		 * @return array         Response
		 */
		public function update_purchaseorder($ponbr) {
			$purchaseorder = $this->create_purchaseorderheader($ponbr);
			$purchaseorder['LineItem'] = $this->create_purchaseorderdetails($ponbr);
			$this->response = $this->curl_put($this->endpoints['purchase-order'], $purchaseorder, $json = true);
			$this->response['response']['PurchaseOrderNumber'] = isset($this->response['response']['PurchaseOrderNumber']) ? $this->response['response']['PurchaseOrderNumber'] :  $purchaseorder['ID'];
			$this->process_response();
			return $this->response['response'];
		}
		
		/* =============================================================
			ERROR CODES AND POSSIBLE SOLUTIONS
		============================================================ */
		/**
		 * 3000  The Currency Code is missing or invalid
		 * 2000  There was no vendor found for the supplied Vendor Code and Vendor Address Code, try sending that vendor then, resend PO
		 * 5007  The Line item total amount cannot be zero, Find Line Item, verify and Ask for cobol changes
		 * 5501  The line item distributions exceed the line item amount, Line Total is not what was expected for quantity and price, verify then ask for cobol changes
		 * 8000  The required field is missing, verify that the field code is indeed missing Inform customer
		 */
		
		/* =============================================================
			INTERNAL CLASS FUNCTIONS
		============================================================ */
		/**
		 * Processes Response and logs Errors if needed
		 * @return void
		 */
		protected function process_response() {
			$stringer = new StringerBell();
			
			$this->response['response']['Status']  = isset($this->response['response']['Status'])  ? $this->response['response']['Status']  : '';
			$this->response['response']['Message'] = isset($this->response['response']['Message']) ? $this->response['response']['Message'] : '';
			
			
			if (!empty($this->response['response']['error']) || $this->response['response']['Status'] == 'FAILURE') {
				$this->response['response']['error'] = true;
				$error = !empty($this->response['response']['ErrorCode']) ? "ErrorCode: " . $this->response['response']['ErrorCode'] . " -> " : '';
				$error .= !empty($this->response['response']['ErrorMessage']) ? $this->response['response']['ErrorMessage'] : $this->response['response']['Message'];
				$error .= " -> ";
				$error .= !empty($this->response['response']['FieldCode']) ? "FieldCode: " . $this->response['response']['FieldCode'] : '';
				$this->log_error($error);
			} elseif ($stringer->contains($this->error_responses['does-not-exist'], $this->response['response']['Message'])) {
				$this->response['response']['error'] = true;
				$this->log_error($this->response['response']['Message']);
			} elseif ($stringer->contains(strtolower($this->error_responses['exists']), strtolower($this->response['response']['Message']))) {
				$this->response['response']['error'] = true;
				$this->log_error($this->response['response']['Message']);
				$this->log_sendlogpo($this->response['response']['PurchaseOrderNumber']);
			} else {
				$this->log_sendlogpo($this->response['response']['PurchaseOrderNumber']);
			}
		}
		
		/**
		 * Adds or Updates send log for an Purchaser Order
		 * @param  string $ponbr Purchase Order Number
		 * @return bool          Was PO Able to be added / updated in the send log
		 * // NOTE This is a public function because so Concur_ExtractPurchaseOrders can use this function
		 */
		public function log_sendlogpo($ponbr) {
			if (does_pohavesendlog($ponbr)) {
				return update_sendlogpo($ponbr, date('Y-m-d H:i:s'));
			} else {
				return insert_sendlogpo($ponbr, date('Y-m-d H:i:s'));
			}
		}
		
		
		/**
		 * Gets Purchase Order header from Database and apply it to the structure needed
		 * @param  string $ponbr Purchase Order Number
		 * @return array         Array in the Header array Structure
		 */
		protected function create_purchaseorderheader($ponbr) {
			$purchaseorder = get_dbpurchaseorderheader($ponbr);
			return $this->create_sectionarray($this->structure['header'], $purchaseorder);
		}
		
		/**
		 * Gets the Purchase Order Details and foreach one puts them in the detail
		 * structure needed then returns an array of all of them
		 * @param  string $ponbr Purchase Order Number
		 * @return array         Details in the detail array Structure
		 */
		protected function create_purchaseorderdetails($ponbr) {
			$lines = array();
			$details = get_dbpurchaseorderdetails($ponbr);
			
			foreach ($details as $detail) {
				$line = $this->create_sectionarray($this->structure['detail'], $detail);
				$line['Allocation'] = array(array('Amount' => $detail['LineTotal']));
				$lines[] = $line;
			}
			return $lines;
		}
	}
