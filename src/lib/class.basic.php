<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  /**
   * CanvasTools Basic Class
   *
   * This class was built to readily provide common GET cURLs. These cURLs
   * relate specifically to Accounts, Courses, and Users and are named to
   * provide context for where they belong.
   *
   * PHP version >= 5.2.0
   *
   * @author Christopher Esbrandt <chris.esbrandt@gmail.com>
   */
  class Basic {
    public $cURL;
    public $retrieve;
    private $validTree;
    private $messages;
    private $message;
    private $fullescape;

    public function __construct() {
      global $cURL;

      $this->cURL = $cURL;

      $this->validTree = array(
        'account' => array(
          'Info' => array(
            'GET' => array(
              'path' => '/accounts/:account_id'
            )
          ),
          'Admins' => array(
            'GET' => array(
              'path' => '/accounts/:account_id/admins'
            )
          ),
          'Courses' => array(
            'GET' => array(
              'path' => '/accounts/:account_id/courses'
            )
          ),
          'ExternalTools' => array(
            'GET' => array(
              'path' => '/accounts/:account_id/external_tools'
            )
          ),
          'ExternalToolInfo' => array(
            'GET' => array(
              'path' => '/accounts/:account_id/external_tools/:external_tool_id'
            )
          ),
          'Apps' => array(
            'GET' => array(
              'path' => '/accounts/:account_id/lti_apps'
            )
          ),
          'Reports' => array(
            'GET' => array(
              'path' => '/accounts/:account_id/reports'
            )
          ),
          'SubAccounts' => array(
            'GET' => array(
              'path' => '/accounts/:account_id/sub_accounts'
            )
          )
        ),
        'course' => array(
          'Info' => array(
            'GET' => array(
              'path' => '/courses/:course_id'
            )
          ),
          'Assignments' => array(
            'GET' => array(
              'path' => '/courses/:course_id/assignments'
            )
          ),
          'AssignmentInfo' => array(
            'GET' => array(
              'path' => '/courses/:course_id/assignments/:id'
            )
          ),
          'Discussions' => array(
            'GET' => array(
              'path' => '/courses/:course_id/discussion_topics'
            )
          ),
          'DiscussionInfo' => array(
            'GET' => array(
              'path' => '/courses/:course_id/discussion_topics/:topic_id'
            )
          ),
          'DiscussionThread' => array(
            'GET' => array(
              'path' => '/courses/:course_id/discussion_topics/:topic_id/view'
            )
          ),
          'ExternalTools' => array(
            'GET' => array(
              'path' => '/courses/:course_id/external_tools'
            )
          ),
          'ExternalToolInfo' => array(
            'GET' => array(
              'path' => '/courses/:course_id/external_tools/:external_tool_id'
            )
          ),
          'Files' => array(
            'GET' => array(
              'path' => '/courses/:course_id/files'
            )
          ),
          'FileFolders' => array(
            'GET' => array(
              'path' => '/courses/:course_id/folders'
            )
          ),
          'Apps' => array(
            'GET' => array(
              'path' => '/courses/:course_id/lti_apps'
            )
          ),
          'Modules' => array(
            'GET' => array(
              'path' => '/courses/:course_id/modules'
            )
          ),
          'ModuleInfo' => array(
            'GET' => array(
              'path' => '/courses/:course_id/modules/:id'
            )
          ),
          'ModuleItems' => array(
            'GET' => array(
              'path' => '/courses/:course_id/modules/:module_id/items'
            )
          ),
          'ModuleItemInfo' => array(
            'GET' => array(
              'path' => '/courses/:course_id/modules/:module_id/items/:id'
            )
          ),
          'Pages' => array(
            'GET' => array(
              'path' => '/courses/:course_id/pages'
            )
          ),
          'PageContent' => array(
            'GET' => array(
              'path' => '/courses/:course_id/pages/:url'
            )
          ),
          'Quizzes' => array(
            'GET' => array(
              'path' => '/courses/:course_id/quizzes'
            )
          ),
          'Sections' => array(
            'GET' => array(
              'path' => '/courses/:course_id/sections'
            )
          ),
          'SectionInfo' => array(
            'GET' => array(
              'path' => '/courses/:course_id/sections/:id'
            )
          ),
          'Settings' => array(
            'GET' => array(
              'path' => '/courses/:course_id/settings'
            )
          ),
          'Users' => array(
            'GET' => array(
              'path' => '/courses/:course_id/users'
            )
          )
        ),
        'user' => array(
				  'List' => array(
  				  'GET' => array(
	  				  'path' => '/accounts/:account_id/users'
		  			)
					),
					'Info' => array(
					  'GET' => array(
						  'path' => '/users/:id'
						)
					),
					'Avatar' => array(
					  'GET' => array(
						  'path' => '/users/:user_id/avatars'
						)
					),
					'PageViews' => array(
					  'GET' => array(
						  'path' => '/users/:user_id/page_views'
						)
					),
					'Profile' => array(
					  'GET' => array(
						  'path' => '/users/:user_id/profile'
						)
					),
					'Settings' => array(
					  'GET' => array(
						  'path' => '/users/:id/settings'
						)
					)
        )
      );
    }

    private function message($type, $param) {
      $error = '{PARAM1} for the {PARAM2}.';
      $contact = ' Please contact the <a href="mailto:' . $GLOBALS['admin'] . '?subject=Canvas%20Tools' . fullescape($GLOBALS['title']) . '%20Error&body=There%20was%20a{PARAM3}%20error%20when%20accessing%20the%20%22' . fullescape($_GET['class']::config()['title']) . '%22%20tool%20on%20' . fullescape(date('Y-m-d \@ H:i:s \U\T\CO')) . '.">system administrator</a>.';
      $var = new stdClass();
      $var->errors = array();
      $var->errors[0] = new stdClass();
      switch($type) {
        case 'missingVar':
          $string = (sizeof($param['variables']) > 1 ? 's' : '') . ' ';
          for($i = 0; $i < sizeof($param['variables']); $i++) {
            $string .= (($i == sizeof($param['variables']) - 1 && sizeof($param['variables']) !== 1) ? 'and ' : '') . '<strong>' . $param['variables'][$i] . '</strong>' . (($i != sizeof($param['variables']) - 1) ? ', ' : '');
          }
          $var->errors[0]->message = str_replace('{PARAM1}', ('Variable' . (sizeof($param['variables']) > 1 ? 's' : '') . ' ' . $string . ' ' . (sizeof($param['variables']) > 1 ? 'are' : 'is') . ' missing'), str_replace('{PARAM2}', '<strong>' . $param['func'] . '</strong> function', $error)) . str_replace('{PARAM3}', fullescape('n invalid type'), $contact);
          break;
        case 'invalidContext':
          $var->errors[0]->message = '<strong>' . $param['context'] . '</strong> is not a valid context.' . str_replace('{PARAM3}', fullescape('n invalid context'), $contact);
          break;
        case 'invalidParameters':
          $var->errors[0]->message = str_replace('{PARAM1}', 'Invalid parameters were passed', str_replace('{PARAM2}', '<strong>' . $param['func'] . '</strong> function in the <strong>' . $param['context'] . '</strong> context', $error)) . str_replace('{PARAM3}', fullescape('n invalid parameters'), $contact);
          break;
        case 'missingParameter':
          $string = (sizeof($param['variables']) > 1 ? 's' : '') . ' ';
          for($i = 0; $i < sizeof($param['variables']); $i++) {
            $string .= (($i == sizeof($param['variables']) - 1 && sizeof($param['variables']) !== 1) ? 'and ' : '') . '<strong>' . $param['variables'][$i] . '</strong>' . (($i != sizeof($param['variables']) - 1 && sizeof($param['variables']) > 2) ? ',' : '') . ' ';
          }
          $var->errors[0]->message = str_replace('{PARAM1}', 'Missing parameter' . $string . 'in call', str_replace('{PARAM2}', '<strong>' . $param['func'] . '</strong> function in the <strong>' . $param['context'] . '</strong> context', $error)) . str_replace('{PARAM3}', fullescape(' missing parameter'), $contact);
          break;
        case 'invalidFunction':
          $var->errors[0]->message = str_replace('{PARAM1}', '<strong>' . $param['func'] . '</strong> is not a valid function', str_replace('{PARAM2}', '<strong>' . $param['context'] . '</strong> context', $error)) . str_replace('{PARAM3}', fullescape('n invalid function'), $contact);
          break;
        case 'invalidType':
          $var->errors[0]->message = str_replace('{PARAM1}', '<strong>' . $param['type'] . '</strong> is not valid', str_replace('{PARAM2}', '<strong>' . $param['func'] . '</strong> function in the <strong>' . $param['context'] . '</strong> context', $error)) . str_replace('{PARAM3}', fullescape('n invalid type'), $contact);
          break;
        default:
          $var->errors[0]->message = 'There was an error!' . str_replace('{PARAM3}', fullescape('n unspecified'), $contact);
      }
      return $var;
    }

    public function retrieve($type, $context, $func, $url = array(), $data = NULL) {
      $invalid = array('context' => $context);

      $valid = FALSE;
      foreach($this->validTree as $contexts => $functions) {
        if($context == $contexts) {
          // Validate $func
          $invalid['func'] = $func;
          foreach($this->validTree[$context] as $functions => $types) {
            if($func == $functions) {
              // Validate $type
              $invalid['type'] = $type;
              foreach($this->validTree[$context][$func] as $types => $values) {
                if($type == $types) {
                  // Validate $url
                  $invalid['variables'] = array();
                  preg_match_all('/:\w*/', $this->validTree[$context][$func][$type]['path'], $urls);
                  if(sizeof($urls[0]) !== 0) {
                    foreach($urls[0] as $id) {
                      $id = str_replace(':', '', $id);
                      if(!array_key_exists($id, $url)) {
                        array_push($invalid['variables'], $id);
                      }
                    }
                    if(sizeof($invalid['variables']) == sizeof($urls[0]) && sizeof($invalid['variables']) > 0) {
                      $valid = 'invalidParameters';
                    } else if(sizeof($invalid['variables']) > 0 && sizeof($invalid['variables']) < sizeof($urls[0])) {
                      $valid = 'missingParameter';
                    } else {
                      $valid = TRUE;
                    }
                  } else {
                    $valid = (sizeof($urls[0]) === 0) ? TRUE : 'invalidParameters';
                  }
                  break;
                }
              }
              if(!$valid) {
                $valid = 'invalidType';
                break;
              }
            }
          }
          if(!$valid) {
            $valid = 'invalidFunction';
            break;
          }
        }
      }
      if(!$valid) {
        $valid = 'invalidContext';
      }

      if($valid !== TRUE) {
        return $this->message($valid, $invalid);
      }

      /* TODO: Add $data Validation */

      // Build cURL URL
      $tar = $this->validTree[$context][$func][$type]['path'];
      foreach($url as $id => $value) {
        $tar = str_replace(':' . $id, $value, $tar);
      }

      // Execute appropriate cURL method
      switch($type) {
        case 'GET':
          if($context == 'course' && $func == 'Assignments') {
            $assignments = $this->cURL->get($tar, $data);
            if(!isset($assignments->errors)) {
              $results = array();
              foreach($assignments as $assignment) {
                if(in_array('none', $assignment->submission_types) || in_array('not_graded', $assignment->submission_types) || in_array('online_upload', $assignment->submission_types) || in_array('on_paper', $assignment->submission_types) || in_array('external_tool', $assignment->submission_types)) {
                  array_push($results, $assignment);
                }
              }
              return $results;
            }
            return $assignments;
          }
          return $this->cURL->get($tar, $data);
        case 'PUT':
          return $this->cURL->put($tar);
        case 'POST':
          return $this->cURL->post($tar);
      }
    }
  }
?>