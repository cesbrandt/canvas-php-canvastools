<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  // List of classes, by name, that are to be loaded
  $menu = array(
    'AccountTree',
    'ContentSearch',
		'FileSearch',
    'LTILocator',
    'CourseDates'
  );

  /* BEGIN BRANDING */
  // Update to reflect the token of the admin user that is to be used
  $token = 'Authorization: Bearer 1234~XvdnP48QTQMnFERdrvFuFJrAjpM2YdXguNmRFbxxtzQqRnNp3xykvGH7CnbysrvN';
  // Update to reflect the address to your institute
  $site = 'ein.instructure.com';
  // Update to reflect the POC for if it breaks
  $admin = 'sysadmin@ein.edu';
  // Update to reflect the name of your institute
  $institute = 'Educational Institute Name';
  // Update to reflect the path to your logo (Optional)
  $logo = '';
  /* END BRANDING */

  // DO NOT MODIFY BELOW THIS LINE
  $pathToClasses = './classes/';
?>