<?php 
    namespace dplus\sapconcur;

	/**
	 * Class to handle dealing with List Items
	 */
	class Concur_ExtractPurchaseOrder extends Concur_Extract {
        /**
         * Extract Endpoint
         * @var string
         */
        protected $extract = 'purchase-order';
        
        
        public function import_concurpurchaseorders() {
            $this->get_extractinfo();
            $this->get_extractjobs();
            $this->get_jobstatus();
            $this->get_extractfile();
            $this->write_extractfile();
        }
    }
