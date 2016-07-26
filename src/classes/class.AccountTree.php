<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  /**
   * CanvasTools Account Tree Class
   *
   * This class will build a multidimensional unordered list of all Canvas
   * accounts beginning with the defined account ID. The function that retrieves
   * the list and builds a multidimensional array is publically available for use
   * outside of the generic functionality of this class. There is also a
   * publically available function for building the options for a HTML SELECT
   * menu, for use in a visual form capacity (i.e., a dropdown for another
   * plugin).
   *
   * PHP version >= 5.2.0
   *
   * @author Christopher Esbrandt <chris.esbrandt@gmail.com>
   */
  class AccountTree {
    public static $config;
    public $generatePage;
    public $buildTreeArray;
    public $buildOptions;
    private $query;
    private $buildList;

    public function __construct() {
      global $query;

      $this->query = $query;
    }

    public static function config() {
      return array('title' => 'Generate Account Tree', 'description' => 'This will generate a multidimensional list of the account structure of a designated account ID.');
    }

    public function generatePage() {
      $form = ((sizeof($_POST) !== 0 && $_POST['accountID'] === '') ? '<p class="warning">You <strong>MUST</strong> provide an account ID to build the tree off of!</p>' : '') . '<form><p class="input"><label for="accountID">Account ID</label><input id="accountID" name="accountID" type="number" /></p><p><button type="submit" formaction="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING'] . '" formmethod="post" formtarget="_self">Print Tree</button></p></form>';
      if(sizeof($_POST) !== 0 && $_POST['accountID'] !== '') {
        $tree = $this->buildTreeArray($_POST['accountID']);
        return (isset($tree['error']) ? ('<p class="error">Error: ' . $tree['error'] . '</p>') : ('<ul>' . $this->buildList($tree) . '</ul>')) . $form;
      } else {
        return $form;
      }
      return false;
    }

    public function buildTreeArray($accountID, $root = true) {
      $subAccounts = array();

      if($root) {
        $root = $this->query->retrieve('GET', 'account', 'Info', array('account_id' => $accountID));
        if(isset($root->errors)) {
          return array('error' => $root->errors[0]->message);
        }
        $accounts = $this->buildTreeArray($accountID, false);
        if(isset($accounts['error'])) {
          return array('error' => $accounts['error']);
        }
        array_push($subAccounts, array('id' => $root[0]->id, 'name' => $root[0]->name, 'subAccounts' => $accounts));
      } else {
        $accounts = $this->query->retrieve('GET', 'account', 'SubAccounts', array('account_id' => $accountID));
        if(isset($accounts->errors)) {
          return array('error' => $accounts->errors[0]->message);
        }
        foreach($accounts as $account) {
          array_push($subAccounts, array('id' => $account->id, 'name' => $account->name, 'subAccounts' => $this->buildTreeArray($account->id, false)));
        }
      }
      return $subAccounts;
    }

    public function buildOptions($accounts, $level = 0) {
      $options = '';
      foreach($accounts as $account) {
        $options .= '<option value="' . $account['id'] . '">' . str_repeat('&nbsp;&nbsp;&nbsp;', $level) . $account['name'] . '</option>' . "\n" . $this->buildOptions($account['subAccounts'], $level + 1);
      }
      return $options;
    }

    private function buildList($accounts, $level = 0, $tree = array()) {
      $options = '';
      foreach($accounts as $account) {
        $options .= '<li>(' . $account['id'] . ') ' . $account['name'] . '<ul>' . $this->buildList($account['subAccounts'], $level + 1) . '</ul>';
      }
      return $options;
    }
  }
?>