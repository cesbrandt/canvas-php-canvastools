<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  /**
   * CanvasTools Course Dates
   *
   * This script will take designated account or course ID and generate a report
   * of the start and end dates for all courses under the search scope.
   *
   * PHP version >= 5.2.0
   *
   * @author Christopher Esbrandt <chris.esbrandt@gmail.com>
   */
  class CourseDates {
    public static $config;
    public $generatePage;
    private $query;
    private $search;

    public function __construct() {
      global $query;

      $this->query = $query;
    }

    public static function config() {
      return array('title' => 'Course Date List', 'description' => 'This will list the <strong>Starts</strong> and <strong>Ends</strong> dates for the designated <strong>course</strong> or courses in the designated <strong>account</strong>.');
    }

    public function generatePage() {
      $form = ((sizeof($_POST) !== 0 && $_POST['accountID'] === '' && $_POST['courseID'] === '') ? '<p class="warning">You <strong>MUST</strong> provide an account <strong>OR</strong> course ID to search!</p>' : '') . '<p>This tool will list the <strong>Starts</strong> and <strong>Ends</strong> dates for the designated <strong>course</strong> or courses in the designated <strong>account</strong>.</p><p><strong>How-To Use</strong></p><ol><li>Provide either an <strong>Account ID</strong> or <strong>Course ID</strong> to search.<ul><li>Providing both will result in the <strong>Account ID</strong> being used and the <strong>Course ID</strong> will be ignored.</li></ul></li><li><strong>Optional</strong>: If searching an account, a <strong>Course Name Filter</strong> can be applied.<ul><li>The filter will only make case-insensitive exact matches, so multiple searches will be required for filter variations.</li></ul></li></ol><form><p class="input"><label for="accountID">Account ID</label><input id="accountID" name="accountID" type="number" /></p><p class="input optional"><label for="courseName">Course Name Filter</label><input id="courseName" name="courseName" type="text" /></p><p class="input"><label for="courseID">Course ID</label><input id="courseID" name="courseID" type="number" /></p><p><button type="submit" formaction="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING'] . '" formmethod="post" formtarget="_self">Search</button></p></form>';
      if(sizeof($_POST) !== 0 && ($_POST['accountID'] !== '' || $_POST['courseID'] !== '')) {
        $results = $this->search($_POST['accountID'], $_POST['courseID'], $_POST['courseName']);
        if(sizeof($this->error) !== 0) {
          $error = '';
          foreach($this->error as $error) {
            $error .= '<p class="error">' . $error . '</p>';
          }
          return $error;
        } else {
          $data = array(
					  'title' => 'LTI Locator',
            'heading' => ('Search Results for <strong>Starts</strong> and <strong>Ends</strong> dates in ' . ($_POST['accountID'] != '' ? 'account' : 'course') . ' "<strong>' . ($_POST['accountID'] != '' ? $_POST['accountID'] : $_POST['courseID']) . '</strong>"'),
            'columns' => array(
              array(
                'title' => 'Course ID',
                'name' => 'courseID',
								'class' => 'center'
              ),
              array(
                'title' => 'Start Date',
                'name' => 'startDate',
								'class' => 'center'
              ),
              array(
                'title' => 'EndDate',
                'name' => 'endDate',
								'class' => 'center'
              )
            ),
            'results' => array()
          );
          foreach($results as $result) {
						$startDate = new DateTime($result['startDate']);
						$endDate = new DateTime($result['endDate']);
						$timeZone = new DateTimeZone('America/New_York');
            array_push($data['results'], array(
              'url' => ('https://' . $GLOBALS['site'] . '/courses/' . $result['id'] . '/settings'),
              'courseID' => $result['id'],
              'startDate' => $startDate->setTimezone($timeZone)->format('D M j, Y g:ia'),
              'endDate' => $endDate->setTimezone($timeZone)->format('D M j, Y g:ia')
            ));
          }
          return Report::HTML($data) . $form;
        }
      } else {
        return $form;
      }
      return false;
    }

    private function search($accountID, $courseID, $courseName) {
      $this->error = array();
			$results = array();
      if($accountID != '') {
        $courses = $this->query->retrieve('GET', 'account', 'Courses', array('account_id' => $accountID));
        if(isset($courses->errors)) {
          array_push($this->error, 'Error: The specified account (<strong>' . $accountID . '</strong>) does not exist!');
          return false;
        }
        foreach($courses as $course) {
		  		if($courseName != '') {
						if(stripos($course->name, $_POST['courseName']) !== false) {
              array_push($results, array(
	  					  'id' => $course->id,
		  					'startDate' => $course->start_at,
			  				'endDate' => $course->end_at
				  		));
						}
  				} else {
            array_push($results, array(
	  				  'id' => $course->id,
		 					'startDate' => $course->start_at,
	    				'endDate' => $course->end_at
  		  		));
					}
        }
      } else {
        $course = $this->query->retrieve('GET', 'course', 'Info', array('course_id' => $courseID));
        if(isset($course->errors)) {
          array_push($this->error, 'Error: The specified course (<strong>' . $courseID . '</strong>) does not exist!');
          return false;
        }
        array_push($results, array(
				  'id' => $course[0]->id,
					'startDate' => $course[0]->start_at,
					'endDate' => $course[0]->end_at
				));
      }
      return $results;
    }
  }
?>