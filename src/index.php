<?php
  /**
   * CanvasTools
   *
   * PHP version >= 5.2.0
   *
   * @category    canvas-php-canvastools
   * @package     canvas-php-canvastools
   * @author      Christopher Esbrandt <chris.esbrandt@gmail.com>
   * @copyright   2016 Christopher Esbrandt
   * @license     https://github.com/cesbrandt/canvas-php-canvastools/blob/master/LICENSE
   * @link        https://github.com/cesbrandt/canvas-php-canvastools
   */

  // Declare that the file is a CanvasTools file
  define('IN_CANVASTOOLS', true);
	define('IN_DEBUG', false);

  // Call the system configuration
  require_once 'config.php';
  require_once 'lib/functions.php';

	// Call generic table reporting class
	require_once 'lib/class.report.php';

  $time_start = microtime_float();

  ini_set('max_execution_time', 14400); // 4-hour timeout

  // Validate the page URL
  if(isset($_GET['class']) && !in_array($_GET['class'], $menu)) {
    header('Location: ./');
  } else if(isset($_POST['action'])) {
		if($_POST['action'] == 'download') {
			$report = new Report();
			$report->XLSX(json_decode(htmlspecialchars_decode($_POST['results'])));
		}
	} else {
    // Build the Menu
    require_once 'lib/class.menu.php';
    $genMenu = new Menu();
    $genMenu->addItem(NULL, NULL);
    $classes = scandir($pathToClasses);
    foreach($classes as $file) {
      foreach($menu as $class) {
        if(preg_match("/^class\.$class\.php$/i", $file)) {
          $genMenu->addItem($class, $pathToClasses . $file);
        }
      }
    }
    $nav = $genMenu->generateMenu();

    $validClass = (isset($_GET['class']) && $_GET['class'] !== 'Home');

    // Identify the Title
    $title = $validClass ? ' - ' . $_GET['class']::config()['title'] : '';

    // Call the cURL class
    require_once 'lib/class.curl.php';
    $cURL = new Curl($token, $site);

    // Call generic queries class
    require_once 'lib/class.basic.php';
    $query = new Basic();

    // Get page details
    if($validClass) {
      $func = new $_GET['class']();
      $body = $func->generatePage();
      $body = (!$body) ? "<p class=\"error\">There was an error! Please report this to the <a href=\"mailto:$admin?subject=Canvas%20Tools%20Error$title&body=There%20was%20an%20unspecified%20error%20when%20accessing%20this%20tool%20on%20" . date('Y-m-d \@ H:i:s \U\T\CO') . ".\">system administrator</a>.</p>" : $body;
    } else {
      $body = $genMenu->generateHomepage();
    }
    $time_end = microtime_float();
    $time = $time_end - $time_start;

    echo "<!DOCTYPE html>
<html lang=\"en\">
  <head>
    <meta charset=\"utf-8\" />
    <title>Canvas Tools$title</title>
    <meta name=\"description\" content=\"CanvasTools$title\" />
    <meta name=\"author\" content=\"Christopher Esbrandt\" />
    <link rel=\"stylesheet\" href=\"style.css\" />
    <!--[if lt IE 9]>
      <script src=\"http://html5shiv.googlecode.com/svn/trunk/html5.js\"></script>
    <![endif]-->
  </head>

  <body>
    <header>" . (($logo !== '') ? "<img src=\"$logo\" alt=\"$institute\" />" : '') . "<h1>Canvas Tools</h1></header>
    <nav>$nav</nav>
    <main>" . ((sizeof($_POST) > 0 && IN_DEBUG) ? "<p>Execution Time: $time seconds<br />Calls: " . $cURL->counter . "<br />Recalls: " . $cURL->nullCounter . "<br />Skipped: " . $cURL->skippedCounter . "</p>" : "") . "$body</main>
    <footer>Copyright &copy; 2016 Christopher Esbrandt<br /><br />Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the \"Software\"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:<br /><br />The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.<br /><br />THE SOFTWARE IS PROVIDED \"AS IS\", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.</footer>
  <script src=\"script.js\"></script>
  </body>
</html>";
  }
?>