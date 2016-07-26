<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  /**
   * CanvasTools File Search Class
   *
   * This class will take a designated account or course ID and search string
   * and search the Canvas API for files that have a case-insensitive match to
   * the search string.
   *
   * PHP version >= 5.2.0
   *
   * @author Christopher Esbrandt <chris.esbrandt@gmail.com>
   */
  class FileSearch {
    public static $config;
    public $generatePage;
    private $query;
    private $search;
    private $searchString;
    private $matches;
    private $error;
    private $listFiles;

    public function __construct() {
      global $query, $report;

      $this->query = $query;
    }

    public static function config() {
      return array('title' => 'File Searcher', 'description' => 'This will allow the searching of the files of a course. The search term is compared against the file names.');
    }

    public function generatePage() {
      $form = ((sizeof($_POST) !== 0 && $_POST['accountID'] === '' && $_POST['courseID'] === '') ? '<p class="warning">You <strong>MUST</strong> provide an account <strong>OR</strong> course ID to search!</p>' : '') . ((sizeof($_POST) !== 0 && $_POST['searchString'] === '') ? '<p class="warning">You <strong>MUST</strong> provide an string to search for!</p>' : '') . '<p>This tool will search all file names for the specified string.</p><p><strong>How-To Use</strong></p><ol><li>Provide either an <strong>Account ID</strong> or <strong>Course ID</strong> to search.<ul><li>Providing both will result in the <strong>Account ID</strong> being searched and the <strong>Course ID</strong> will be ignored.</li></ul></li><li><strong>Optional</strong>: If searching an account, a <strong>Course Name Filter</strong> can be applied.<ul><li>The filter will only make case-insensitive exact matches, so multiple searches will be required for filter variations.</li></ul></li><li>Provide your search string.<ul><li>The search will only make case-insensitive exact matches, so multiple searches will be required for search variations.</li></ul></li></ol><form><p class="input"><label for="accountID">Account ID</label><input id="accountID" name="accountID" type="number" /></p><p class="input optional"><label for="courseName">Course Name Filter</label><input id="courseName" name="courseName" type="text" /></p><p class="input"><label for="courseID">Course ID</label><input id="courseID" name="courseID" type="number" /></p><p class="input"><label for="searchString">Search For</label><input id="searchString" name="searchString" type="text" /></p><p><button type="submit" formaction="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING'] . '" formmethod="post" formtarget="_self">Search</button></p></form>';
      if(sizeof($_POST) !== 0 && ($_POST['accountID'] !== '' || $_POST['courseID'] !== '') && $_POST['searchString'] !== '') {
        $this->search($_POST['accountID'], $_POST['courseID'], $_POST['searchString']);
        if(sizeof($this->error) !== 0) {
          $errorMsg = '';
          foreach($this->error as $error) {
            $errorMsg .= '<p class="error">' . $error . '</p>';
          }
          return $errorMsg . $form;
        } else {
          $data = array(
					  'title' => 'File Search',
            'heading' => ('Search Results for the string "<strong>' . $this->searchString . '</strong>" in ' . ($_POST['accountID'] != '' ? 'account' : 'course') . ' "<strong>' . ($_POST['accountID'] != '' ? $_POST['accountID'] : $_POST['courseID']) . '</strong>"' . ($_POST['courseName'] != '' ? ' with course name filter "<strong>' . $_POST['courseName'] . '</strong>"' : '')),
            'columns' => array(
              array(
                'title' => 'Course ID',
                'name' => 'courseID',
								'class' => 'center'
              ),
              array(
                'title' => 'File Name',
                'name' => 'name'
              )
            ),
            'results' => array()
          );
          foreach($this->matches as $match) {
            array_push($data['results'], array(
              'url' => ('https://' . $GLOBALS['site'] . '/courses/' . $match['course'] . '/files/' . $match['result']->id),
              'courseID' => $match['course'],
              'name' => $match['result']->display_name
            ));
          }
          return Report::HTML($data) . $form;
        }
      } else {
        return $form;
      }
      return false;
    }

    private function search($accountID, $courseID, $searchString) {
      $this->searchString = $searchString;
      $this->matches = array();
      $this->error = array();
      if($accountID !== '') {
        $courses = $this->query->retrieve('GET', 'account', 'Courses', array('account_id' => $accountID));
        if(isset($courses->errors)) {
          $this->error = array('error' => $courses->errors[0]->message);
          return false;
        }
        $courseIDs = array();
        foreach($courses as $course) {
          if($_POST['courseName'] !== '') {
            if(stripos($course->name, $_POST['courseName']) !== false) {
              array_push($courseIDs, $course->id);
            }
          } else {
            array_push($courseIDs, $course->id);
          }
        }
      } else {
        $course = $this->query->retrieve('GET', 'course', 'Info', array('course_id' => $courseID));
        if(isset($course->errors)) {
          $this->error = array('error' => $course->errors[0]->message);
          return false;
        }
        $courseIDs = array(intval($courseID));
      }
      foreach($courseIDs as $courseID) {
        $this->listlistFiles($courseID);
      }
    }

    private function listlistFiles($courseID) {
      $files = $this->query->retrieve('GET', 'course', 'Files', array('course_id' => $courseID), array('search_term' => $this->searchString));
      if(isset($files->errors)) {
        array_push($this->error, $files->errors[0]->message);
        return false;
      }
      foreach($files as $file) {
        array_push($this->matches, array('course' => $courseID, 'result' => $file));
      }
    }
  }
?>