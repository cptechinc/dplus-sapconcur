<?php
	namespace Dplus\SapConcur;
	
	use Purl\Url as Url;
	use Dplus\Base\Validator;
	
	class Concur_Invoice extends Concur_Endpoint {
		use StructuredClassTraits;
		
		protected $endpoints = array(
			'invoice-search' => 'https://www.concursolutions.com/api/v3.0/invoice/paymentrequestdigests',
			'invoice'        => 'https://www.concursolutions.com/api/v3.0/invoice/paymentrequest'
		);
		
		/**
		 * Structure of Purchase Order
		 * @var array
		 */
		protected $structure = array(
			'header' => array(
				'InvoiceNumber'        => array('format' => 'string'),
				'CountryCode'          => array(),
				'OB10TransactionId'    => array(),
				'CheckNumber'          => array(),
				'PaymentTermsDays'     => array(),
				'CreatedByUsername'    => array(),
				'InvoiceDate'          => array(),
				'PaymentDueDate'       => array(),
				'InvoiceReceivedDate'  => array(),
				'InvoiceAmount'        => array('format' => 'currency'),
				'CalculatedAmount'     => array('format' => 'currency'),
				'TotalApprovedAmount'  => array('format' => 'currency'),
				'ShippingAmount'       => array('format' => 'currency'),
				'TaxAmount'            => array('format' => 'currency'),
				'LineItemTotalAmount'  => array('format' => 'currency'),
				'PurchaseOrderNumber'  => array('format' => 'string'),
				'Custom9'              => array('dbcolumn' => 'Location'),
				'AmountWithoutVat'     => array('format' => 'currency'),
				'PurchaseOrderNumber'  => array(),
				'ID'                   => array(),
				'VendorRemitAddress'  => array(
					'Name'          => array('dbcolumn' => 'VendorName'),
					'VendorCode'    => array(),
					'Address1'      => array('dbcolumn' => 'VendorAddress1'),
					'Address2'      => array('dbcolumn' => 'VendorAddress2'),
					'Address3'      => array('dbcolumn' => 'VendorAddress3'),
					'City'          => array('dbcolumn' => 'VendorCity'),
					'State'         => array('dbcolumn' => 'VendorState'),
					'PostalCode'    => array('dbcolumn' => 'VendorZip'),
					'CountryCode'   => array('dbcolumn' => 'VendorCountry'),
				),
			),
			'detail' => array(
				'LineItemId'               => array(),
				'RequestLineItemNumber'    => array(),
				'Quantity'                 => array(),
				'Description'              => array(),
				'SupplierPartId'           => array(),
				'UnitPrice'                => array('format' => 'currency'),
				'TotalPrice'               => array('format' => 'currency'),
				'AmountWithoutVat'         => array('format' => 'currency'),
				'Custom9'                  => array('dbcolumn' => 'Location'),
				'PurchaseOrderNumber'      => array()
			)
		);
		
		/* =============================================================
			CONCUR INTERFACE FUNCTIONS
		============================================================ */
		/**
		 * Sends GET Request to retreive Invoice
		 * @param  string $invoiceID Invoice ID
		 * @return array             Response from Concur
		 */
		public function get_invoice($invoiceID) {
			$url = $this->endpoints['invoice'] . "/$invoiceID";
			$response = $this->curl_get($url);
			return $response['response'];
		}
		
		/**
		 * Sends GET Request to retreive Invoice IDs created after $date
		 * // Example URL https://www.concursolutions.com/api/v3.0/invoice/paymentrequestdigests/?createDateAfter=2018-01-01
		 * @param  string $date Date to start looking for invoices in YYYY-MM-DD Format
		 * @param  string $url  URL FROM Next Page response
		 * @return array        Response from Concur
		 */
		public function get_invoiceIDs_created_after($date, $url = '') {
			$date = $this->convert_date($date);
			$url = !empty($url) ? new Url($url) : new Url($this->endpoints['invoice-search']);
			$url->query->set('createDateAfter', $date);
			$response = $this->curl_get($url->getUrl());
			return $response['response'];
		}
		
		/**
		 * Sends GET Request to retreive Invoices created after X date
		 * @param  string $date  Date YYYY-MM-DD
		 * @return array         Response from Concur
		 */
		public function get_all_invoiceIDs_created_after($date) {
			$response = $this->get_invoiceIDs_created_after($date);
			$invoiceIDs = array_column($response['PaymentRequestDigest'], 'ID');
			
			while (!empty($response['NextPage'])) {
				$response = $this->get_invoiceIDs_created_after($date, $response['NextPage']);
				$invoiceIDs = array_merge($invoiceIDs, array_column($response['PaymentRequestDigest'], 'ID'));
			}
			
			return $invoiceIDs;
		}
		
		/**
		 * Imports the Invoices created after $date
		 * @param  string $date Date
		 * @return array        Invoices that were imported
		 */
		public function get_invoices_created_after($date) {
			$invoiceIDs = $this->get_all_invoiceIDs_created_after($date);
			$invoices = array();
			
			foreach ($invoiceIDs as $invoiceID) {
				$invoice = $this->get_invoice($invoiceID);
				$invoice = $this->process_invoice($invoice);
				$this->write_dbinvoice($invoice);
				$invoices[$invoice['InvoiceNumber']] = $invoice;
			}
			return $invoices;
		}
		
		/**
		 * Sends GET Request to retreive Invoice IDs created before $date
		 * // Example URL https://www.concursolutions.com/api/v3.0/invoice/paymentrequestdigests/?createDateBefore=2018-01-01
		 * @param  string $date Date to start looking for invoices in YYYY-MM-DD Format
		 * @param  string $url  URL FROM Next Page response
		 * @return array        Response from Concur
		 */
		public function get_invoiceIDs_created_before($date, $url = '') {
			$date = $this->convert_date($date);
			$url = !empty($url) ? new Url($url) : new Url($this->endpoints['invoice-search']);
			$url->query->set('createDateBefore', $date);
			$response = $this->curl_get($url->getUrl());
			return $response['response'];
		}
		
		/**
		 * Sends GET Request to retreive Invoices created befre X date
		 * @param  string $date  Date YYYY-MM-DD
		 * @return array         Invoice IDs
		 */
		public function get_all_invoiceIDs_created_before($date) {
			$response = $this->get_invoiceIDs_created_before($date);
			$invoiceIDs = array_column($response['PaymentRequestDigest'], 'ID');
			
			while (!empty($response['NextPage'])) {
				$response = $this->get_invoiceIDs_created_before($date, $response['NextPage']);
				$invoiceIDs = array_merge($invoiceIDs, array_column($response['PaymentRequestDigest'], 'ID'));
			}
			return $invoiceIDs;
		}
		
		/**
		 * Imports the Invoices created before $date
		 * @param  string $date Date
		 * @return array        Invoices that were imported
		 */
		public function get_invoices_created_before($date) {
			$invoiceIDs = $this->get_all_invoiceIDs_created_before($date);
			$invoices = array();
			foreach ($invoiceIDs as $invoiceID) {
				$invoice = $this->get_invoice($invoiceID);
				$invoice = $this->process_invoice($invoice);
				$this->write_dbinvoice($invoice);
				$invoices[$invoice['InvoiceNumber']] = $invoice;
			}
			return $invoices;
		}
		
		/**
		 * Converts Date to YYYY-MM-DD Format, will use today's date if empty
		 * @param  string $date YYYY-MM-DD
		 * @return string        Date in YYYY-MM-DD format
		 */
		public function convert_date($date) {
			$validator = new Validator();
			$date = !empty($date) ? $date : date('Y-m-d');
			return $validator->date_yyyymmdd_dashed($date) ? $date : date('Y-m-d', strtotime($date));
		}
		
		/* =============================================================
			DATABASE FUNCTIONS
		============================================================ */
		/**
		 * Writes Invoice Header into database
		 * @param  array  $invoice Key-value with columns and their values to set
		 * @param  bool   $debug   Run in debug? If so, return SQL Query
		 * @return mixed           Int of affected rows | SQL Query
		 */
		public function write_dbinvoice_head($invoice, $debug = false) {
			$invoiceheader = $this->create_dbarray($this->structure['header'], $invoice);
			$result = false;

			if (does_dbinvoiceexist($invoice['InvoiceNumber'])) {
				$result = update_dbinvoice($invoiceheader, $debug);
			} else {
				$result = insert_dbinvoice($invoiceheader, $debug);
			}
			return $result;
		}
		
		/**
		 * Inserts invoice detail line for Invoice
		 * @param  string $invnbr Invoice Number
		 * @param  array  $line   Key-value with columns and their values to set
		 * @param  bool   $debug  Run in debug? If so, return SQL Query
		 * @return mixed          Int of affected rows | SQL Query
		 */
		public function insert_dbinvoiceline($invnbr, $line, $debug = false) {
			$invoiceline = $this->create_dbarray($this->structure['detail'], $line);
			$result = false;
			
			if (does_dbinvoicelineexist($invnbr, $invoiceline['RequestLineItemNumber'])) {
				$result = update_dbinvoiceline($invnbr, $invoiceline, $debug);
			} else {
				// ADD Invoice Number for insert
				$invoiceline['InvoiceNumber'] = $invnbr;
				$result = insert_dbinvoiceline($invnbr, $invoiceline, $debug);
			}
			return $result;
		}
		
		/* =============================================================
			CLASS INTERFACE FUNCTIONS
		============================================================ */
		protected function process_invoice(array $invoice) {
			$i = 0;
			
			foreach ($invoice['LineItems']['LineItem'] as $line) {
				$invoice['LineItems']['LineItem'][$i]['PurchaseOrderNumber'] = !empty($line['PurchaseOrderNumber']) ? $line['PurchaseOrderNumber'] : $invoice['PurchaseOrderNumber'];
				$i++;
			}
			return $invoice;
		}
		/**
		 * Writes the Invoice and its Detail Lines
		 * @param  array $invoice SAP Invoice
		 * @return void
		 */
		public function write_dbinvoice(array $invoice) {
			$response = array(
				'header'  => $this->write_dbinvoice_head($invoice),
				'details' => array()
			);
			foreach ($invoice['LineItems']['LineItem'] as $line) {
				$response['details'][$line['RequestLineItemNumber']] = $this->insert_dbinvoiceline($invoice['InvoiceNumber'], $line);
				$response['details'][$line['RequestLineItemNumber']] = does_dbinvoicelineexist($invoice['InvoiceNumber'], $line['RequestLineItemNumber']);
			}
			return $response;
		}
	}
