<?php 
	namespace Dplus\SapConcur;
	
	class Concur_PurchaseOrderReceipts extends Concur_Endpoint {
		use StructuredClassTraits;
		
		protected $endpoints = array(
			'receipts' => 'https://www.concursolutions.com/api/v3.0/invoice/purchaseorderreceipts'
		);
		
		/**
		 * Structure of Purchase Order
		 * @var array
		 */
		protected $structure = array(
			'header' => array(
				'PurchaseOrderNumber' => array('dbcolumn' => '', 'required' => false),
				'LineNumber'          => array('dbcolumn' => '', 'required' => false),
				'LineItemExternalID'  => array('dbcolumn' => '', 'required' => false),
				'ReceivedDate'        => array('dbcolumn' => '', 'required' => false, 'format' => 'date', 'date-format' => 'Y-m-d'),
				'ReceivedQuantity'    => array('dbcolumn' => '', 'required' => false)
			)
		);
		
		/**
		 * Sends PUT Request to update a Purchase Order with receipt data 
		 * for a Purchase Order Line
		 * @param string $ponbr      Purchase Order Number
		 * @param int    $linenumber Line Number
		 */
		public function add_receipt($ponbr, $linenumber) {
			$receipt = get_dbreceipt($ponbr, $linenumber);
			$data = $this->create_sectionarray($this->structure['header'], $receipt);
			$this->response = $this->put_curl($this->endpoints['receipts'], $data, $json = true);
			$this->process_response();
			return $this->response;
		}
		
		/**
		 * Gets all the PO Numbers
		 * @param int     $limit Number of POs to do
		 * @param string  $ponbr Purchase Order Number to start after
		 * @return array         Generated Response
		 */
		public function batch_addreceipts($limit = 0, $ponbr = '') {
			$purchaseordernumbers = get_dbdistinctreceiptponbrs($limit, $ponbr);
			$response = $sortedresponse = array();
			
			foreach ($purchaseordernumbers as $ponbr) {
				$response[$ponbr] = $this->add_receiptsforpo($ponbr);
			}
			$sortedresponse = $this->sort_response($response);
			return $sortedresponse;
		}
		
		/**
		 * Sorts reponses into two categories in array ok | failed
		 * @param  array $response  Receipts send Response
		 * @return array            array('failed' => $failed, 'ok' => $successful)
		 */
		public function sort_response($response) {
			$sortedresponse = array('failed' => array(), 'ok' => array());
			
			foreach ($response as $ponbr => $purchaselines) {
				foreach ($purchaselines as $linenbr => $line) {
					if ($this->did_detailfail($line)) {
						$sortedresponse['failed'][$ponbr][$linenbr] = $line;
					} else {
						$sortedresponse['ok'][$ponbr][$linenbr] = $line;
					}
				}
			}
			return $sortedresponse;
		}
		
		/**
		 * Returns if the Detail Response returned failture
		 * @param  array $detail  Receipt Response
		 * @return bool           Did Receipt send fail?
		 */
		public function did_detailfail($detail) {
			return ($detail['Status'] == 'FAILURE') ? true : false;
		}
		
		/**
		 * Adds receipts for specific Purchase Order Numbers
		 * @param array $ponumbers Purchase Order Numbers
		 */
		public function add_receiptsforspecifiedpos($ponumbers) {
			$response = array();
			
			foreach ($ponumbers as $ponbr) {
				$response[$ponbr] = $this->add_receiptsforpo($ponbr);
			}
			return $response;
		}
		
		/**
		 * Adds all the receipts necessary for one Purchase Order
		 * @param string $ponbr Purchase Order Number
		 */
		public function add_receiptsforpo($ponbr) {
			$receiptlines = get_dbreceiptslinenbrs($ponbr);
			$response = array();
			
			foreach ($receiptlines as $linenumber) {
				$response[$linenumber] = $this->add_receipt($ponbr, $linenumber);
			}
			return $response;
		}
		
		
		
		/**
		 * Processes Response and logs Errors if needed
		 * @return void
		 */
		protected function process_response() {
			if (!isset($this->response['Message'])) {
				$this->response['Message'] = '';
			}
			if ($this->response['error'] || $this->response['Status'] == 'FAILURE') {
				$error = !empty($this->response['ErrorCode']) ? "PO # ".$this->response['PurchaseOrderNumber'] . " -> ErrorCode: " . $this->response['ErrorCode'] . " -> " : '';
				$error .= !empty($this->response['ErrorMessage']) ? $this->response['ErrorMessage'] : $this->response['Message'];
				$error .= " -> ";
				$error .= !empty($this->response['FieldCode']) ? "FieldCode: " . $this->response['FieldCode'] : '';
				$this->log_error($error);
			} 
		}
	}
