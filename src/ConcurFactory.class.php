<?php 
	namespace dplus\sapconcur;
	
	/**
	 * Factory to instatiate Concur Endpoint classes, also provides them with the Access token needed
	 */
	Class ConcurFactory {
		use \MagicMethodTraits;
		use \ThrowErrorTrait;
		
		/**
		 * Is there an Error
		 * @var bool
		 */
		protected $error = false;
		
		/**
		 * Endpoints by their type => Class Name
		 * @var array
		 */
		protected $endpoints = array(
			'authentication'          => 'Concur_Authentication',
			'vendor'                  => 'Concur_Vendor',
			'purchase-order'          => 'Concur_PurchaseOrder',
			'purchase-order-receipts' => 'Concur_PurchaseOrderReceipts',
			'invoice'                 => 'Concur_Invoice',
			'list-item'               => 'Concur_ListItem',
			'list-item-inventory'     => 'Concur_ListItemInventory',
			'extract'                 => 'Concur_Extract',
			'extract-purchase-order'  => 'Concur_ExtractPurchaseOrder'
		);
		
		/**
		 * Creates factory, then we create an instance
		 * of the Authentication class which we use to retrieve
		 * an access token we can save to this class and supply it to the endpoint classes
		 */
		public function __construct() {
			$api = new Concur_Authentication();
			$api->create_authenticationtoken();
			
			if ($api->response['server']['error']) {
				echo json_encode($api->response);
				exit;
			}
		}
		
		/**
		 * Creates an instance of Endpoint and returns it
		 * @param  string $endpoint Endpoint to use
		 * @return mixed            Endpoint
		 */
		public function create_endpoint($endpoint) {
			if (in_array($endpoint, array_keys($this->endpoints))) {
				$class = $this->endpoints[$endpoint];
				return new $class();
			} else {
				$this->error("Endpoint $endpoint does not exist");
			}
		}
	}
	
