<?php 
    namespace Dplus\SapConcur;
    
    use Dplus\ProcessWire\DplusWire;
    use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
    use Dplus\FileServices\UploadedFile;
    
	/**
	 * Class to handle dealing with List Items
	 */
	class Concur_ExtractPurchaseOrder extends Concur_Extract {
        /**
         * Extract Endpoint
         * @var string
         */
        protected $extract = 'purchase-order';
        
        /**
         * Column Indexes
         * // ARRAYS ARE ZERO INDEXED
         * @var array
         */
        protected $indexes = array(
            'ponbr' => 49  // COLUMN AX IN A SPREADSHEET IS COLUMN 50
        );
        
        /**
         * Spreadsheet Delimiter
         * @var string
         */
        protected $delimiter = "|";
        
        
        public function import_concurpurchaseorders() {
            // $this->get_extractinfo();
            // $this->get_extractjobs();
            // $this->get_jobstatus();
            // $this->get_extractfile();
            // $this->write_extractfile();
            $this->filename = $this->extract.".txt";
            $ponbrs = $this->parse_ponbrsfromcsv(); 
            $ponbrs_valid = $this->parse_ponbrs($ponbrs);
            $this->log_ponbrs($ponbrs_valid);
            return $ponbrs_valid;
        }
        
        /**
         * Parses the Extracted PO File
         * grabs the Purchase Order Number
         * @return array Purchase Order Numbers
         */
        protected function parse_ponbrsfromcsv() {
            $pos = array();
            $readfile = UpLoadedFile::create_fromuploadedfile($this->filename);
            
            $file = file($readfile->get_filepath());
            foreach ($file as $line) {
                $linearray = explode($this->delimiter, $line);
                if (isset($linearray[$this->indexes['ponbr']])) {
                    $pos[] = $linearray[$this->indexes['ponbr']];
                }
            }
            unset($file);
            return array_unique($pos);
        }
        
        /**
         * Validates the Purchase Numbers and filters out
         * non-valid Purchase Order Numbers
         * @param  array  $ponbrs Purchase Order Numbers
         * @return array         Valid Purchase Order Numbers
         */
        protected function parse_ponbrs(array $ponbrs) {
            $ponbrs_valid = array();
            foreach ($ponbrs as $ponbr) {
                if (is_numeric($ponbr)) {
                    $ponbrs_valid[] = $ponbr;
                }
            }
            return $ponbrs_valid;
        }
        
        protected function log_ponbrs($ponbrs) {
            $poendpoint = new Concur_PurchaseOrder();
            
            foreach ($ponbrs as $ponbr) {
                $poendpoint->log_sendlogpo($ponbr);
            }
        }
        
    }
