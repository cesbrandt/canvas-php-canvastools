<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  /**
   * CanvasTools Template
   *
   * Class Template
   *
   * PHP version >= 5.2.0
   *
   * @author Original Author <author e-mail>
   */
  class Template {
    public static $config;
    public $generatePage;
    private $query;

    public function __construct() {
      global $query;

      $this->query = $query;
    }

    public static function config() {
      return array('title' => 'Class Title', 'description' => 'Class Description');
    }

    public function generatePage() {
      // Insert code to generate page here
    }
  }
?>