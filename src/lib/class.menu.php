<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  /**
   * CanvasTools Menu Generation Class
   *
   * This class was built to generate a menu of functions in CanvasTools.
   *
   * PHP version >= 5.2.0
   *
   * @author Christopher Esbrandt <chris.esbrandt@gmail.com>
   */
  class Menu {
    public $addItem;
    private $menuItems;

    public function __contruct() {
    }

    public function addItem($class, $file) {
      if(is_null($class) && is_null($file)) {
        $this->menuItems['Home'] = array('title' => 'Home', 'description' => '');
        if(!isset($_GET['class'])) {
          $_GET['class'] = 'Home';
        }
      } else {
        require_once $file;
        $this->menuItems[$class] = $class::config();
      }
    }

    public function generateMenu() {
      $menu = '
      <ul>';
      foreach($this->menuItems as $class => $config) {
        $menu .= "
        <li" . ((isset($_GET['class']) && $_GET['class'] == $class) ? " class=\"current\"" : "") . "><a href=\"?class=$class\">" . $config['title'] . "</a></li>";
      }
      return $menu . '
      </ul>';
    }

    public function generateHomepage() {
      $page = '';
      foreach($this->menuItems as $class => $config) {
        if($config['title'] != 'Home') {
          $page .= "
      <p><a href=\"?class=$class\">" . $config['title'] . "</a>" . (($config['description'] !== '') ? " - " . $config['description'] : "") . "</p>";
        }
      }
      return $page;
    }
  }
?>