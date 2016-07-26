<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  /**
   * CanvasTools Content Search Class
   *
   * This class will take a designated account or course ID and search string and
   * search the Canvas API for the assignments, discussions, quizzes, and content
   * pages that have a case-insensitive match to the search string.
   *
   * PHP version >= 5.2.0
   *
   * @author Christopher Esbrandt <chris.esbrandt@gmail.com>
   */
  class ContentSearch {
    public static $config;
    public $generatePage;
    private $query;
    private $search;
    private $searchString;
    private $matches;
    private $error;
    private $listAssignments;
    private $listDiscussions;
    private $listQuizzes;
    private $listContentPages;
    private $listExternalURLs;

    public function __construct() {
      global $query, $report;

      $this->query = $query;
    }

    public static function config() {
      return array('title' => 'Content Searcher', 'description' => 'This will allow the searching of the main content area of assignments, discussions, quizzes, and pages. The search term is compared against hyperlinks, allowing it to be used as an URL Locators, also.');
    }

    public function generatePage() {
      $form = ((sizeof($_POST) !== 0 && $_POST['accountID'] === '' && $_POST['courseID'] === '') ? '<p class="warning">You <strong>MUST</strong> provide an account <strong>OR</strong> course ID to search!</p>' : '') . ((sizeof($_POST) !== 0 && $_POST['searchString'] === '') ? '<p class="warning">You <strong>MUST</strong> provide an string to search for!</p>' : '') . '<p>This tool will search all <strong>Discussions</strong>, <strong>Assignments</strong>, <strong>Quizzes</strong>, <strong>Content Pages</strong>, and <strong>External Links</strong> (Modules-only) for the specified string.</p><p><strong>Note</strong>: Due to the limitations of the Canvas API, <strong>Quiz Questions</strong> are not searched.</p><p><strong>How-To Use</strong></p><ol><li>Provide either an <strong>Account ID</strong> or <strong>Course ID</strong> to search.<ul><li>Providing both will result in the <strong>Account ID</strong> being searched and the <strong>Course ID</strong> will be ignored.</li></ul></li><li><strong>Optional</strong>: If searching an account, a <strong>Course Name Filter</strong> can be applied.<ul><li>The filter will only make case-insensitive exact matches, so multiple searches will be required for filter variations.</li></ul></li><li>Provide your search string.<ul><li>The search will only make case-insensitive exact matches, so multiple searches will be required for search variations.</li></ul></li></ol><form><p class="input"><label for="accountID">Account ID</label><input id="accountID" name="accountID" type="number" /></p><p class="input optional"><label for="courseName">Course Name Filter</label><input id="courseName" name="courseName" type="text" /></p><p class="input"><label for="courseID">Course ID</label><input id="courseID" name="courseID" type="number" /></p><p class="input"><label for="searchString">Search For</label><input id="searchString" name="searchString" type="text" /></p><p><button type="submit" formaction="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING'] . '" formmethod="post" formtarget="_self">Search</button></p></form>';
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
					  'title' => 'Content Search',
            'heading' => ('Search Results for the string "<strong>' . $this->searchString . '</strong>" in ' . ($_POST['accountID'] != '' ? 'account' : 'course') . ' "<strong>' . ($_POST['accountID'] != '' ? $_POST['accountID'] : $_POST['courseID']) . '</strong>"' . ($_POST['courseName'] != '' ? ' with course name filter "<strong>' . $_POST['courseName'] . '</strong>"' : '')),
            'columns' => array(
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
              'url' => ('https://' . $GLOBALS['site'] . '/courses/' . $match['course'] . '/' . (($match['type'] == 'external_url') ? 'modules/items' : $match['type']) . '/' . (($match['type'] == 'pages') ? $match['result']->url : $match['result']->id)),
              'courseID' => $match['course'],
              'type' => $match['type'],
              'name' => (($match['type'] == 'assignments') ? $match['result']->name : $match['result']->title)
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
        $this->listAssignments($courseID);
        $this->listDiscussions($courseID);
        $this->listQuizzes($courseID);
        $this->listContentPages($courseID);
        $this->listExternalURLs($courseID);
      }
    }

    private function listAssignments($courseID) {
      $assignments = $this->query->retrieve('GET', 'course', 'Assignments', array('course_id' => $courseID));
      if(isset($assignments->errors)) {
        array_push($this->error, $assignments->errors[0]->message);
        return false;
      }
      foreach($assignments as $assignment) {
        if(stripos(strip_tags($assignment->description), $this->searchString) !== false || stripos($assignment->description, $this->searchString) !== false) {
          array_push($this->matches, array('course' => $courseID, 'type' => 'assignments', 'result' => $assignment));
        }
      }
    }

    private function listDiscussions($courseID) {
      $discussions = $this->query->retrieve('GET', 'course', 'Discussions', array('course_id' => $courseID));
      if(isset($discussions->errors)) {
        array_push($this->error, $discussions->errors[0]->message);
        return false;
      }
      foreach($discussions as $discussion) {
        if(stripos(strip_tags($discussion->message), $this->searchString) !== false || stripos($discussion->message, $this->searchString) !== false) {
          array_push($this->matches, array('course' => $courseID, 'type' => 'discussion_topics', 'result' => $discussion));
        }
      }
    }

    private function listQuizzes($courseID) {
      $quizzes = $this->query->retrieve('GET', 'course', 'Quizzes', array('course_id' => $courseID));
      if(isset($quizzes->errors)) {
        array_push($this->error, $quizzes->errors[0]->message);
        return false;
      }
      foreach($quizzes as $quiz) {
        if(stripos(strip_tags($quiz->description), $this->searchString) !== false || stripos($quiz->description, $this->searchString) !== false) {
          array_push($this->matches, array('course' => $courseID, 'type' => 'quizzes', 'result' => $quiz));
        }
/* NOTE: Revisit when the long awaited new quiz system is released */
/* Unable to search groups due to the Canvas API not supporting group identification for a quiz */
//        $groups = callAPI($GLOBALS['site'] . '/courses/' . $courseID . '/quizzes/' . $quiz->id . '/groups');
//        foreach($questions as $question) {
//        }
/* Question searching dropped to maintain uniformity of results (all questions get searched, or none) */
//        $questions = $GLOBALS['cURL']->get('/courses/' . $courseID . '/quizzes/' . $quiz->id . '/questions');
//        foreach($questions as $question) {
//          if(stripos(strip_tags($question->question_text), $GLOBALS['url']) !== false) {
//            array_push($GLOBALS['matches'], array($courseID, $quiz->id, $courseName, $quiz->title, $quiz->html_url, 'Question #' . $question->id . ' Description'));
//          }
//          $answers = $question->answers;
//          foreach($answers as $answer) {
//            if($question->question_type != 'calculated_question' && stripos(strip_tags($answer->text), $GLOBALS['url']) !== false) {
//              array_push($GLOBALS['matches'], array($courseID, $quiz->id, $courseName, $quiz->title, $quiz->html_url, 'Question #' . $question->id . ' Answer #' . $answer->id));
//            }
//          }
//        }
      }
    }

    private function listContentPages($courseID) {
      $contentPages = $this->query->retrieve('GET', 'course', 'Pages', array('course_id' => $courseID));
      if(isset($contentPages->errors)) {
        array_push($this->error, $contentPages->errors[0]->message);
        return false;
      }
      foreach($contentPages as $contentPage) {
				$page = $this->query->retrieve('GET', 'course', 'PageContent', array('course_id' => $courseID, 'url' => $contentPage->url))[0];
        if(isset($page->message)) {
          array_push($this->error, $page->message);
          return false;
        }
        if(stripos(strip_tags($page->body), $this->searchString) !== false || stripos($page->body, $this->searchString) !== false) {
          array_push($this->matches, array('course' => $courseID, 'type' => 'pages', 'result' => $page));
        }
      }
    }

    private function listExternalURLs($courseID) {
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
          if($item->type == 'ExternalUrl' && (stripos(strip_tags($item->external_url), $this->searchString) !== false || stripos($item->external_url, $this->searchString) !== false)) {
            array_push($this->matches, array('course' => $courseID, 'type' => 'external_url', 'result' => $item));
          }
        }
      }
    }
  }
?>