<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  /**
   * Canvas API cURL Class
   *
   * This class was built specifically for use with the Instructure Canvas REST
   * API.
   *
   * PHP version >= 5.2.0
   *
   * @author Christopher Esbrandt <chris.esbrandt@gmail.com>
   */
  class Curl {
    public $curl;
    public $get;
    public $put;
    private $token;
    private $baseURL;
    private $initCurl;
    private $restartCurl;
    private $closeCurl;
    private $setOpt;
    private $setURLData;
    private $urlPath;
    private $callAPI;
    private $exec;
    private $data;
		public $counter;
		public $nullCounter;
		public $skippedCounter;

    /**
     * Contructor function
     *
     * @param $base_url
     */
    public function __construct($token, $domain) {
			$this->counter = 0;
			$this->nullCounter = 0;
			$this->skippedCounter = 0;
      if(is_null($token)) {
        throw new \ErrorException('No admin token supplied.');
      }
      if(is_null($domain)) {
        throw new \ErrorException('No domain supplied.');
      }
      $this->token = $token;
      $this->domain = $domain;
      $this->initCurl();
    }

    /**
     * Initialize a cURL call
     */
    private function initCurl() {
      $this->curl = curl_init();
      $this->setOpt(CURLOPT_RETURNTRANSFER, true);
      $this->setOpt(CURLOPT_HEADER, true);
      $this->setOpt(CURLOPT_HTTPHEADER, array('Content-Type: application/json', $this->token));
      // Uncomment for non-HTTPS use (NOT RECOMMENDED)
//      $this->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
//      $this->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
    }

    /**
     * Restart cURL for multiple calls
     */
    private function restartCurl() {
      $this->closeCurl();
      $this->initCurl();
    }

    /**
     * Close cURL after all calls have been made
     */
    public function closeCurl() {
      curl_close($this->curl);
    }

    /**
     * Execute cURL function
     *
     * @return array
     */
    private function exec($url = NULL, $data = NULL) {
      if(!is_null($url)) {
        if($this->method == 'GET') {
          $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        }
        $this->setURLData($url, $data);
      }
      $results = curl_exec($this->curl);
			$this->counter++;
			$attempts = 1;
      $headerSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
      $header = substr($results, 0, $headerSize);
      $results = json_decode(substr($results, $headerSize));
			while(substr($header, 0, 12) == 'HTTP/1.1 504' && $attempts < 5) {
        $results = curl_exec($this->curl);
  			$this->nullCounter++;
				$attempts++;
        $headerSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $header = substr($results, 0, $headerSize);
        $results = json_decode(substr($results, $headerSize));
			}
			if(substr($header, 0, 12) == 'HTTP/1.1 504' || $attempts == 5) {
				$this->skippedCounter++;
				return false;
			}
      $this->restartCurl();
      return array($header, $results);
    }

    /**
     * Calls exec() for each page of the API results
     *
     * @return array
     */
    private function callAPI() {
      $currRegex = '/\bpage=\K(\d+\b)(?=[^>]*>; rel="current")/';
      $lastRegex = '/\bpage=\K(\d+\b)(?=[^>]*>; rel="last")/';
      $results = array();
      $call = $this->exec();
      if(isset($call[1]->errors)) {
        return $call[1];
      }
      if(substr($call[0], 0, 12) != 'HTTP/1.1 302' && substr($call[0], 0, 12) != 'HTTP/1.1 404') {
        if(is_array($call[1])) {
          foreach($call[1] as $result) {
            array_push($results, $result);
          }
        } else {
          array_push($results, $call[1]);
        }
        preg_match($currRegex, $call[0], $current);
        preg_match($lastRegex, $call[0], $last);
      }
      if(isset($current) && sizeof($current) !== 0) {
        while($current[0] != $last[0]) {
          $call = $this->exec($this->urlPath, array('page' => (++$current[0])));
          if(substr($call[0], 0, 12) != 'HTTP/1.1 302' && substr($call[0], 0, 12) != 'HTTP/1.1 404') {
            if(is_array($call[1])) {
              foreach($call[1] as $result) {
                array_push($results, $result);
              }
            } else {
              array_push($results, $call[1]);
            }
          }
        }
      }
      return $results;
    }

    /**
     * POST function
     *
     * @param $url, $data
     *
     * @return array
     */
    public function post($url, $data = NULL) {
      unset($this->data);
      unset($this->method);
      if(is_null($data)) {
        throw new \ErrorException('No data supplied.');
      }
      $this->setURLData($url, json_encode($data));
      $this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
      return $this->callAPI();
    }

    /**
     * PUT function
     *
     * @param $url, $data
     *
     * @return array
     */
    public function put($url, $data = NULL) {
      unset($this->data);
      unset($this->method);
      if(is_null($data)) {
        throw new \ErrorException('No data supplied.');
      }
      $this->setURLData($url, json_encode($data));
      $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
      return $this->callAPI();
    }

    /**
     * GET function
     *
     * @param $url, $data
     *
     * @return array
     */
    public function get($url, $data = NULL) {
      $this->data = array();
      $this->method = 'GET';
      if(!is_null($data)) {
        $this->data = $data;
      }
      $this->setURLData($url);
      $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
      return $this->callAPI();
    }

    /**
     * Set the target URL and supplied data function
     *
     * @param $url
     * @param $data
     */
    private function setURLData($url, $data = NULL) {
      if(is_null($url)) {
        throw new \ErrorException('No target URL supplied.');
      }
      $this->urlPath = $url;

      if(isset($this->method) && $this->method == 'GET') {
        if(!isset($this->data['per_page'])) {
          $this->data['per_page'] = 100;
        }
        if(!is_null($data)) {
          foreach($data as $key => $value) {
            $this->data[$key] = $value;
          }
        }
        $this->setOpt(CURLOPT_URL, ((preg_match('/http(s)?:\/\//i', $this->urlPath)) ? '' : 'https://' . $this->domain . '/api/v1') . $this->urlPath . ((strpos($url, '?') !== false) ? '&' : '?') . http_build_query($this->data));
      } else {
        $this->setOpt(CURLOPT_URL, ((preg_match('/http(s)?:\/\//i', $this->urlPath)) ? '' : 'https://' . $this->domain . '/api/v1') . $this->urlPath . ((strpos($url, '?') !== false) ? '&' : '?') . 'per_page=100');
      }
      if(!is_null($data) && $this->method != 'GET') {
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
      }
    }

    /**
     * Set cURL Options function
     *
     * @param $option
     * @param $value
     */
    private function setOpt($options, $value = null) {
      if(is_array($options)) {
        foreach($options as $option => $value) {
          curl_setopt($this->curl, $option, $value);
        }
      } else {
        curl_setopt($this->curl, $options, $value);
      }
    }
  }
?>