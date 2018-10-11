<?php
	namespace Dplus\SapConcur;
	
	class Concur_Invoice extends Concur_Endpoint {
		use StructuredClassTraits;
		
		protected $endpoints = array(
			'invoice-search' => 'https://www.concursolutions.com/api/v3.0/invoice/paymentrequestdigests',
			'invoice' => 'https://www.concursolutions.com/api/v3.0/invoice/paymentrequest'
		);
		
		/**
		 * Structure of Purchase Order
		 * @var array
		 */
		protected $structure = array(
			'header' => array(
				'InvoiceNumber'        => array('dbcolumn' => '', 'required' => false),
				'CountryCode'          => array('dbcolumn' => '', 'required' => false),
				'OB10TransactionId'    => array('dbcolumn' => '', 'required' => false),
				'CheckNumber'          => array('dbcolumn' => '', 'required' => false),
				'PaymentTermsDays'     => array('dbcolumn' => '', 'required' => false),
				'CreatedByUsername'    => array('dbcolumn' => '', 'required' => false),
				'InvoiceDate'          => array('dbcolumn' => '', 'required' => false),
				'PaymentDueDate'       => array('dbcolumn' => '', 'required' => false),
				'InvoiceReceivedDate'  => array('dbcolumn' => '', 'required' => false),
				'InvoiceAmount'        => array('dbcolumn' => '', 'required' => false),
				'CalculatedAmount'     => array('dbcolumn' => '', 'required' => false),
				'TotalApprovedAmount'  => array('dbcolumn' => '', 'required' => false),
				'ShippingAmount'       => array('dbcolumn' => '', 'required' => false),
				'TaxAmount'            => array('dbcolumn' => '', 'required' => false),
				'LineItemTotalAmount'  => array('dbcolumn' => '', 'required' => false),
				'PurchaseOrderNumber'  => array('dbcolumn' => '', 'required' => false),
				'Custom9'              => array('dbcolumn' => 'Location', 'required' => false),
				'AmountWithoutVat'     => array('dbcolumn' => '', 'required' => false),
				'PurchaseOrderNumber'  => array('dbcolumn' => '', 'required' => false),
				'ID'  => array('dbcolumn' => '', 'required' => false),
				'VendorRemitAddress'  => array(
					'Name'          => array('dbcolumn' => 'VendorName', 'required' => false),
					'VendorCode'    => array('dbcolumn' => '', 'required' => false),
					'Address1'      => array('dbcolumn' => 'VendorAddress1', 'required' => false),
					'Address2'      => array('dbcolumn' => 'VendorAddress2', 'required' => false),
					'Address3'      => array('dbcolumn' => 'VendorAddress3', 'required' => false),
					'City'          => array('dbcolumn' => 'VendorCity', 'required' => false),
					'State'         => array('dbcolumn' => 'VendorState', 'required' => false),
					'PostalCode'    => array('dbcolumn' => 'VendorZip', 'required' => false),
					'CountryCode'   => array('dbcolumn' => 'VendorCountry', 'required' => false),
				),
			),
			'detail' => array(
				'LineItemId'               => array('dbcolumn' => '', 'required' => false),
				'RequestLineItemNumber'    => array('dbcolumn' => '', 'required' => false),
				'Quantity'                 => array('dbcolumn' => '', 'required' => false),
				'Description'              => array('dbcolumn' => '', 'required' => false),
				'SupplierPartId'           => array('dbcolumn' => '', 'required' => false),
				'UnitPrice'                => array('dbcolumn' => '', 'required' => false),
				'TotalPrice'               => array('dbcolumn' => '', 'required' => false),
				'AmountWithoutVat'         => array('dbcolumn' => '', 'required' => false),
				'Custom9'                  => array('dbcolumn' => 'Location', 'required' => false),
				'PurchaseOrderNumber'      => array('dbcolumn' => '', 'required' => false)
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
			return $this->get_curl($url);
		}
		
		/**
		 * Sends GET Request to retreive Invoices created after X date
		 * @param  string $date  Date YYYY-MM-DD
		 * @return array         Response from Concur
		 */
		public function get_invoicescreatedafter($date) {
			$url = new Purl\Url($this->endpoints['invoice']);
			$url->query->set('createDateAfter', $date);
			return $this->get_curl($url->getUrl());
		}
		
		/**
		 * Sends GET Request to retreive Invoices created after X date
		 * @param  string $date  Date YYYY-MM-DD
		 * @return array         Response from Concur
		 */
		public function get_invoicescreatedbefore($date) {
			$url = new Purl\Url($this->endpoints['invoice']);
			$url->query->set('createDateBefore', $date);
			return $this->get_curl($url->getUrl());
		}
		
		/* =============================================================
			DATABASE FUNCTIONS
		============================================================ */
		/**
		 * Inserts Invoice Header into database
		 * @param  array  $invoice Key-value with columns and their values to set
		 * @param  bool   $debug   Run in debug? If so, return SQL Query
		 * @return mixed           Int of affected rows | SQL Query
		 */
		public function insert_dbinvoicehead($invoice, $debug = false) {
			$invoiceheader = $this->create_dbarray($this->structure['header'], $invoice);
			$result = false;
			
			if (does_dbinvoiceexist($invoiceheader['InvoiceNumber'])) {
				$result = update_dbinvoice($invoiceheader, $debug = false);
			} else {
				$result = insert_dbinvoice($invoiceheader, $debug = false);
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
				$result = update_dbinvoiceline($invnbr, $invoiceline);
			} else {
				// ADD Invoice Number for insert
				$invoiceline['InvoiceNumber'] = $invnbr;
				$result = insert_dbinvoiceline($invnbr, $invoiceline);
			}
			return $result;
		}
		
		/* =============================================================
			CLASS INTERFACE FUNCTIONS
		============================================================ */
		/**
		 * Writes the Invoice and its Detail Lines
		 * @param  string $invoiceID SAP Invoice ID
		 * @return void
		 */
		public function write_invoice($invoiceID) {
			$invoice = $this->get_invoice($invoiceID);
			$this->insert_dbinvoicehead($invoice);
			
			foreach ($invoice['LineItems']['LineItem'] as $line) {
				$this->insert_dbinvoiceline($invoice['InvoiceNumber'], $line);
			} 
		}
	}
