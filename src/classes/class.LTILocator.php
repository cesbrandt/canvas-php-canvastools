<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  /**
   * CanvasTools LTI Locator
   *
   * This script will take designated account ID and LTI ID/launch URL and search
   * the Canvas API for the all assignments and module items linking to the that
   * LTI.
   *
   * PHP version >= 5.2.0
   *
   * @author Christopher Esbrandt <chris.esbrandt@gmail.com>
   */
  class LTILocator {
    public static $config;
    public $generatePage;
    private $query;
    private $search;
    private $externalTool;
    private $matches;
    private $error;
    private $listConfigurations;
    private $listAssignments;
    private $matchAssignmentExternalToolID;
    private $listModuleTools;
    private $retrieveLTIHome;

    public function __construct() {
      global $query;

      $this->query = $query;
    }

    public static function config() {
      return array('title' => 'External Tool Locator', 'description' => 'This will locate where an external tool is being linked and where it is being linked from.');
    }

    public function generatePage() {
      $form = '<p>This tool will search all <strong>Assignments</strong> and <strong>Module Items</strong> for the specified LTI (if an external tool ID is provided) <strong><em><span class="underline">OR</span></em></strong> search for all LTIs by partial URL (if anything else if provided).</p><p><strong>How-To Use</strong></p><ol><li>Provide either an <strong>External Tool ID</strong> or <strong>External Tool URL</strong> to search.</li><li>Provide either an <strong>Account ID</strong> or <strong>Course ID</strong> to search.<ul><li>Providing both will result in the <strong>Account ID</strong> being searched and the <strong>Course ID</strong> will be ignored.</li></ul></li><li>Select whether to search for <strong>Configurations</strong>, <strong>Links</strong>, or <strong>Both</strong>.<ul><li><strong>Note</strong>: Default is <strong>Both</strong>.</li></ul></li></ol><form><p class="input"><label for="accountID">Account ID</label><input id="accountID" name="accountID" type="number" /></p><p class="input"><label for="courseID">Course ID</label><input id="courseID" name="courseID" type="number" /></p><p class="input"><label for="External Tool">External Tool</label><input id="searchString" name="externalTool" type="text" /></p><p class="radio"><label for="searchFor">Search For</label><span><label><input id="searchFor1" name="searchFor" type="radio" value="config" /><span>Configurations</span></label><label><input id="searchFor2" name="searchFor" type="radio" value="links" /><span>Links</span></label><label><input id="searchFor3" name="searchFor" type="radio" value="both" checked="checked" /><span>Both</span></label></span></p><p><button type="submit" formaction="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING'] . '" formmethod="post" formtarget="_self">Search</button></p></form>';
      if(sizeof($_POST) !== 0 && ($_POST['accountID'] !== '' || $_POST['courseID'] !== '') && $_POST['externalTool'] !== '') {
        $this->search($_POST['accountID'], $_POST['courseID'], $_POST['externalTool']);
        if(sizeof($this->error) !== 0) {
          $error = '';
          foreach($this->error as $error) {
            $error .= '<p class="error">' . $error . '</p>';
          }
          return $error;
        } else {
          $data = array(
					  'title' => 'LTI Locator',
            'heading' => ('Search Results for <strong>' . ($_POST['searchFor'] != 'links' ? 'Configurations' : ($_POST['searchFor'] != 'config' ? 'Configurations and Links' : 'Links')) . '</strong> of the External Tool "<strong>' . $this->externalTool . '</strong>" in ' . ($_POST['accountID'] != '' ? 'account' : 'course') . ' "<strong>' . ($_POST['accountID'] != '' ? $_POST['accountID'] : $_POST['courseID']) . '</strong>"'),
            'columns' => array(
              array(
                'title' => 'External Tool ID',
                'name' => 'externalToolID',
								'class' => 'center'
              ),
              array(
                'title' => 'External Tool Location',
                'name' => 'externalToolLoc',
								'class' => 'center'
              ),
              array(
                'title' => 'Course ID',
                'name' => 'courseID',
								'class' => 'center'
              ),
              array(
                'title' => 'Activity Type',
                'name' => 'type',
								'class' => 'center'
              ),
              array(
                'title' => 'Activity Name',
                'name' => 'name'
              )
            ),
            'results' => array()
          );
          foreach($this->matches as $match) {
            array_push($data['results'], array(
              'url' => ('https://' . $GLOBALS['site'] . '/' . (($match['type'] == 'configuration') ? ((substr($match['location'], 0, 7) == 'Account') ? 'accounts' : 'courses') . '/' . preg_replace('/[^\d]/', '', $match['location']) . '/settings/configurations' : 'courses/' . $match['course'] . '/' . (($match['type'] == 'external_tool') ? 'modules/items' : $match['type']) . '/' . $match['result']->id)),
              'externalToolID' => $match['lti'],
              'externalToolLoc' => $match['location'],
              'courseID' => $match['course'],
              'type' => $match['type'],
              'name' => (($match['type'] != 'configuration') ? (($match['type'] == 'assignments') ? $match['result']->name : $match['result']->title) : '')
            ));
          }
          return Report::HTML($data) . $form;
        }
      } else {
        return $form;
      }
      return false;
    }

    private function search($accountID, $courseID, $externalTool) {
      $this->externalTool = $externalTool;
      $this->matches = array();
      $this->error = array();
      if($accountID !== '') {
        if($_POST['searchFor'] != 'links') {
          $this->listConfigurations($accountID, 'account');
        }

        $courses = $this->query->retrieve('GET', 'account', 'Courses', array('account_id' => $accountID));
        if(isset($courses->errors)) {
          array_push($this->error, 'Error: The specified account (<strong>' . $accountID . '</strong>) does not exist!');
          return false;
        }
        $courseIDs = array();
        foreach($courses as $course) {
          array_push($courseIDs, $course->id);
        }
      } else {
        $course = $this->query->retrieve('GET', 'course', 'Info', array('course_id' => $courseID));
        if(isset($course->errors)) {
          array_push($this->error, 'Error: The specified course (<strong>' . $courseID . '</strong>) does not exist!');
          return false;
        }
        $courseIDs = array(intval($courseID));
      }

      if(isset($courseIDs)) {
        foreach($courseIDs as $courseID) {
          if($_POST['searchFor'] != 'links') {
            $this->listConfigurations($courseID, 'course');
          }
          if($_POST['searchFor'] != 'config') {
            $matches = array();
            $matches = $this->listAssignments($courseID, $matches);
            $matches = $this->listModuleTools($courseID, $matches);
            foreach($matches as $match) {
               $this->retrieveLTIHome($match);
            }
          }
        }
      }
    }

    private function listConfigurations($id, $type) {
      if($type == 'account') {
        $apps = $this->query->retrieve('GET', 'account', 'Apps', array('account_id' => $id));
        if(isset($apps->errors)) {
          array_push($this->error, $apps->errors[0]->message);
          return false;
        }
        foreach($apps as $app) {
          $stored = false;
          foreach($this->matches as $match) {
            if($match['lti'] === $app->app_id) {
              $stored = true;
            }
          }
          if(!$stored) {
            $config = $this->query->retrieve('GET', 'account', 'ExternalToolInfo', array('account_id' => $app->context_id, 'external_tool_id' => $app->app_id))[0];
            if(isset($config->errors)) {
              array_push($this->error, $config->errors[0]->message);
              return false;
            }
            if((!is_numeric($this->externalTool) && stripos($config->url, $this->externalTool) !== false) || $this->externalTool == $config->id) {
              $add = true;
              foreach($this->matches as $match) {
                $add = ($match['lti'] == $config->id && $match['type'] == 'configuration') ? false : $add;
              }
              if($add) {
                array_push($this->matches, array('course' => NULL, 'lti' => $config->id, 'location' => $app->context . ': ' . $app->context_id, 'type' => 'configuration', 'result' => NULL));
              }
            }
          }
        }
        $subAccounts = $this->query->retrieve('GET', 'account', 'SubAccounts', array('account_id' => $id));
        if(isset($subAccounts->errors)) {
          array_push($this->error, $subAccounts->errors[0]->message);
          return false;
        }
        foreach($subAccounts as $subAccount) {
          $this->listConfigurations($subAccount->id, 'account');
        }
      } else {
        $apps = $this->query->retrieve('GET', 'course', 'Apps', array('course_id' => $id));
        if(isset($apps->errors)) {
          array_push($this->error, $apps->errors[0]->message);
          return false;
        }
        foreach($apps as $app) {
          $config = ($app->context == 'Account') ? $this->query->retrieve('GET', 'account', 'ExternalToolInfo', array('account_id' => $app->context_id, 'external_tool_id' => $app->app_id))[0] : $this->query->retrieve('GET', 'course', 'ExternalToolInfo', array('course_id' => $app->context_id, 'external_tool_id' => $app->app_id))[0];
          if(isset($config->errors)) {
            array_push($this->error, $config->errors[0]->message);
            return false;
          }
          if((!is_numeric($this->externalTool) && stripos($config->url, $this->externalTool) !== false) || $this->externalTool == $config->id) {
            $add = true;
            foreach($this->matches as $match) {
              $add = ($match['lti'] == $config->id && $match['type'] == 'configuration') ? false : $add;
            }
            if($add) {
              array_push($this->matches, array('course' => NULL, 'lti' => $config->id, 'location' => $app->context . ': ' . $app->context_id, 'type' => 'configuration', 'result' => NULL));
            }
          }
        }
      }
    }

    private function listAssignments($courseID, $matches = array()) {
      $assignments = $this->query->retrieve('GET', 'course', 'Assignments', array('course_id' => $courseID));
      if(isset($assignments->errors)) {
        array_push($this->error, $assignments->errors[0]->message);
        return false;
      }
      foreach($assignments as $assignment) {
        if(in_array('external_tool', $assignment->submission_types)) {
          if(!is_numeric($this->externalTool) && stripos($assignment->external_tool_tag_attributes->url, $this->externalTool) !== false) {
            array_push($matches, array('course' => $courseID, 'lti' => $this->matchAssignmentExternalToolID($assignment), 'type' => 'assignments', 'result' => $assignment));
          } else {
            if($this->matchAssignmentExternalToolID($assignment) == $this->externalTool) {
              array_push($matches, array('course' => $courseID, 'lti' => $this->externalTool, 'type' => 'assignments', 'result' => $assignment));
            }
          }
        }
      }
      return $matches;
    }

    private function matchAssignmentExternalToolID($assignment) {
      global $cURL;

      $data = $cURL->get($assignment->url);
      if(sizeof($data) !== 0) {
        return $data[0]->id;
      }
      return false;
    }

    private function listModuleTools($courseID, $matches = array()) {
      $modules = $this->query->retrieve('GET', 'course', 'Modules', array('course_id' => $courseID));
      if(isset($modules->errors)) {
        array_push($this->error, $modules->errors[0]->message);
        return false;
      }
      foreach($modules as $module) {
        $items = $this->query->retrieve('GET', 'course', 'ModuleItems', array('course_id' => $courseID, 'module_id' => $module->id));
        if(isset($items->errors)) {
          array_push($this->error, $items->errors[0]->message);
          return false;
        }
        foreach($items as $item) {
          if($item->type == 'ExternalTool' && ((!is_numeric($this->externalTool) && stripos($item->external_url, $this->externalTool) !== false) || $item->content_id == $this->externalTool)) {
            array_push($matches, array('course' => $courseID, 'lti' => $item->content_id, 'type' => 'external_tool', 'result' => $item));
          }
        }
      }
      return $matches;
    }

    private function retrieveLTIHome($lti) {
      $apps = $this->query->retrieve('GET', 'course', 'Apps', array('course_id' => $lti['course']));
      if(isset($apps->errors)) {
        array_push($this->error, $apps->errors[0]->message);
        return false;
      }
      foreach($apps as $app) {
        if($app->app_id == $lti['lti']) {
          array_push($this->matches, array('course' => $lti['course'], 'lti' => $lti['lti'], 'location' => $app->context . ': ' . $app->context_id, 'type' => $lti['type'], 'result' => $lti['result']));
        }
      }
    }
  }
?>