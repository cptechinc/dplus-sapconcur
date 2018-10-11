<?php 
	namespace Dplus\SapConcur;
	/**
	 * Class to handle dealing with List Items
	 */
	class Concur_Extract extends Concur_Endpoint {
		use StructuredClassTraits;
        
        /**
         * Extract Endpoint
         * @var string
         */
        protected $extract;
		
		protected $endpoints = array(
			'extract-list'    => 'https://www.concursolutions.com:443/api/expense/extract/v1.0/',
            'vendor'          => 'https://www.concursolutions.com:443/api/expense/extract/v1.0/gWikjBezeKqgMOrksVVL8FnHTEtYEPz2YFA',
            'purchase-order'  => 'https://www.concursolutions.com:443/api/expense/extract/v1.0/gWikjBezeKqR$sLpbF9ElWP4Jkf7gKX5zf6A',
            'invoice'         => 'https://www.concursolutions.com:443/api/expense/extract/v1.0/gWikjBezeKtwKBxM3tK7FajSPu0ATF0sqTg'
        );
        
        /**
         * Information for Extract
         * @var array
         */
        protected $extractinfo = array(
            'id' => '',
            'name' => '',
            'job-link' => '',
        );
        
        /**
         * Template for Job
         * @var array
         */
        static $job = array(
            'id' => '',
            'status-link' => '',
            'start-time' => '',
            'stop-time' => '',
            'status' => ''
        );
        
        /**
         * Jobs available for this Extract
         * @var array
         */
        protected $jobs = array();
        
        /**
         * Information about 1 job
         * @var string
         */
        protected $jobstatus = array(
            'id' => '',
            'status-link' => '',
            'start-time' => '',
            'stop-time' => '',
            'status' => '',
            'file-link' => ''
        );
		
		/**
		 * File Content
		 * @var string
		 */
		protected $file;
        
        /**
         * Gets the Information for $this->extract type
         * @return void
         */
        public function get_extractinfo() {
            $url = $this->endpoints[$this->extract];
			$this->response = $this->curl_get($url);
			
            foreach ($this->extractinfo as $key => $value) {
                $this->extractinfo[$key] = $this->response['response'][$key];
            }
			return $this->extractinfo;
        }
        
        /**
         * Returns the Jobs Completed for $this->extract
         * @return array Jobs
         */
        public function get_extractjobs() {
            $url = $this->extractinfo['job-link'];
			$this->response = $this->curl_get($url);
            $this->jobs = $this->response['response'];
            return $this->jobs;
        }
        
        /**
         * Returns Data about 1 Job
         * @param  string $statuslink URL to Job 
         * @return array              Job Status
         */
        public function get_jobstatus($statuslink = '') {
            $url = empty($statuslink) ? $this->jobs[0]['status-link'] : $statuslink;
			$this->response = $this->curl_get($url);
            $this->jobstatus = $this->response['response'];
            return $this->jobstatus;
        }
		
		public function get_extractfile($filelink = '') {
			$url = empty($filelink) ? $this->jobstatus['file-link'] : $filelink;
			$this->response = $this->curl_getcsv($url);
			$this->file = $this->response['response'];
            return $this->file;
		}
		
		public function write_extractfile() {
			$vard = DplusWire::wire('config')->documentstoragedirectory . "$this->extract.txt";
			$handle = fopen($vard, "w") or die("cant open file");
			fwrite($handle, $this->file);
			fclose($handle);
		}
		
    }
