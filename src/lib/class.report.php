<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  /**
   * CanvasTools Generic Report Class
   *
   * This class was built to generic a generic report for output in CanvasTools.
   *
   * PHP version >= 5.2.0
   *
   * @author Christopher Esbrandt <chris.esbrandt@gmail.com>
   */

  class Report {
    public $XLSX;
    public static $HTML;
    private $columnConverter;
    private $getCurDate;

    public function XLSX($data) {
      error_reporting(E_ALL);
      ini_set('display_errors', TRUE);
      ini_set('display_startup_errors', TRUE);
      ini_set('date.timezone', 'US/Eastern');

      if(PHP_SAPI == 'cli') {
        die('This example should only be run from a Web Browser');
      }

      require_once 'plugins/plugin.PHPExcel.php';

      $xlsx = new PHPExcel();
			$rt = new PHPExcel_Helper_HTML;
			$color = new PHPExcel_Style_Color;
			
			$headerStyle = array(
				'fill' => array(
				  'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array(
					  'rgb' => 'f2f2f2'
					)
				),
				'borders' => array(
				  'outline' => array(
					  'style' => PHPExcel_Style_Border::BORDER_THIN,
						'color' => array(
	  				  'rgb' => '7f7f7f'
  					)
					)
				)
			);
			$tableStyle = array(
				'borders' => array(
				  'outline' => array(
					  'style' => PHPExcel_Style_Border::BORDER_THIN,
						'color' => array(
	  				  'rgb' => '95b3d7'
  					)
					),
				  'horizontal' => array(
					  'style' => PHPExcel_Style_Border::BORDER_THIN,
						'color' => array(
	  				  'rgb' => '95b3d7'
  					)
					)
				)
			);
			$titleStyle = array(
        'font' => array(
          'bold' => true,
          'color' => array(
            'rgb' => 'ffffff'
          )
        ),
				'fill' => array(
				  'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array(
					  'rgb' => '4f81bd'
					)
				),
				'alignment' => array(
				  'horizontal' => 'center'
				)
			);
			$oddStyle = array(
				'fill' => array(
				  'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array(
					  'rgb' => 'dce6f1'
					)
				)
			);
			$evenStyle = array(
				'fill' => array(
				  'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array(
					  'rgb' => 'ffffff'
					)
				)
			);
			$centerStyle = array(
				'alignment' => array(
				  'horizontal' => 'center'
				)
			);

      $sheet = $xlsx->setActiveSheetIndex(0);
      $properties = $xlsx->getProperties();
      if(isset($data->title) && is_string($data->title)) {
        $xlsx->getActiveSheet()->setTitle($data->title);
        $properties->setTitle($data->title)->setSubject($data->title);
      }
			if(isset($data->heading) && is_string($data->heading) && $data->heading != '') {
				$start = 1;
        $properties->setDescription($data->heading);
				$sheet->getStyle('A' . $start . ':' . $this->columnConverter(sizeof($data->columns)) . '1')->applyFromArray($headerStyle);
				$sheet->mergeCells('A' . $start . ':' . $this->columnConverter(sizeof($data->columns)) . '1');
  			$sheet->setCellValue('A' . $start, $rt->toRichTextObject('<font color="#fa7d00">' . $data->heading . '</font>'));
			} else {
				$start = 0;
			}
			$sheet->getStyle('A' . (1 + $start) . ':' . $this->columnConverter(sizeof($data->columns)) . (sizeof($data->results) + 1 + $start))->applyFromArray($tableStyle);
      for($i = 0; $i < sizeof($data->columns); $i++) {
        $col = $this->columnConverter($i);
				$sheet->getStyle($col . (1 + $start) . ':' . $this->columnConverter(sizeof($data->columns)) . (1 + $start))->applyFromArray($titleStyle);
        $sheet->setCellValue($col . (1 + $start), $data->columns[$i]->title);
				for($j = 0; $j < sizeof($data->results); $j++) {
				  $sheet->getStyle($col . ($j + (2 + $start)))->applyFromArray(($j % 2 === 0) ? $oddStyle : $evenStyle);
					$sheet->setCellValue($col . ($j + (2 + $start)), $data->results[$j]->{$data->columns[$i]->name});
					if(isset($data->columns[$i]->class) && is_string($data->columns[$i]->class) && $data->columns[$i]->class != '') {
						$sheet->getStyle($col . ($j + (2 + $start)))->applyFromArray($centerStyle);
					}
					if($i == (sizeof($data->columns) - 1)) {
  				  $sheet->getStyle($this->columnConverter($i + 1) . ($j + (2 + $start)))->applyFromArray(($j % 2 === 0) ? $oddStyle : $evenStyle);
  					$sheet->setCellValue($this->columnConverter($i + 1) . ($j + (2 + $start)), $data->results[$j]->url);
					}
				}
      }
			$sheet->setAutoFilter('A' . (1 + $start) . ':' . $this->columnConverter(sizeof($data->columns)) . (sizeof($data->results) + 1 + $start));
      $sheet->setCellValue($this->columnConverter($i) . (1 + $start), 'URL');
			foreach(range('A', $this->columnConverter($i)) as $col) {
				$sheet->getColumnDimension($col)->setAutoSize(true);
			}
      $xlsx->setActiveSheetIndex(0);
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment;filename="Report.xlsx"');
      header('Cache-Control: max-age=0');
      header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
      header('Last-Modified: '. $this->getCurDate()->format('D, d M Y H:i:s') . ' GMT'); // always modified
      header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
      header('Pragma: public'); // HTTP/1.0
      $file = PHPExcel_IOFactory::createWriter($xlsx, 'Excel2007');
      $file->save('php://output');
      exit;
      echo '<textarea>'; var_dump($data); echo '</textarea>';
    }

    public static function HTML($data) {
      if(!isset($data)) {
        $error = 'There was no data supplied.';
      } else if(!isset($data['columns']) || !isset($data['results'])) {
        $error = 'No ' . (!isset($data['columns']) ? 'column' . (!isset($data['results']) ? ' and result' : '') : 'result') . ' data supplied.';
      } else if(!is_array($data) || !is_array($data['results'])) {
        $error = 'The data supplied is invalid.';
      }/* else if(!(sizeof($data) >= 2 && sizeof($data) <= 4) || sizeof($data['results']) < 1) {
        $error = 'The data supplied is not properly formatted.';
      }*/
      if(isset($error)) {
        return '<p class="error">' . $error . ' Please contact the system administrator.</p>';
      }
      $report = '<div class="section">' . ((isset($data['heading']) && is_string($data['heading']) && $data['heading'] != '') ? '<p class="section-heading">' . $data['heading'] . '</p>' : '') . '<table id="searchResults"><thead><tr>';
      foreach($data['columns'] as $col) {
        $report .= '<th>' . $col['title'] . '</th>';
      }
      $report .= '</tr></thead><tbody>';
      foreach($data['results'] as $result) {
        $row = '';
        foreach($data['columns'] as $col) {
          $row .= '<td' . ((isset($col['class']) && is_string($col['class']) && $col['class'] != '') ? ' class="' . $col['class'] . '"' : '') . '>' . $result[$col['name']] . '</td>';
        }
        $report .= '<tr' . ((isset($result['url']) && is_string($result['url']) && $result['url'] != '') ? ' data-link="' . $result['url'] . '" class="pointer"' : '') . '>' . $row . '</tr>';
      }
      return $report . '</tbody></table><form><input type="hidden" name="action" id="action" value="download" /><input type="hidden" name="results" id="results" value="' . htmlspecialchars(json_encode($data)) . '" /><button class="section-footer" formaction="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING'] . '" formmethod="post" formtarget="_blank">Export to XLSX</button></form></div><script type="text/javascript">window.onload = (function() { var rows = document.getElementById(\'searchResults\').getElementsByTagName(\'tbody\')[0].getElementsByTagName(\'tr\'); for(var i = 0; i < rows.length; i++) { rows[i].addEventListener(\'click\', function() {  window.open(this.getAttribute(\'data-link\')); }); }})();</script>';
    }

    private function columnConverter($i) {
      $rem = $i % 26;
      $col = chr(65 + $rem);
      $i = intval($i / 26);
      if($i > 0) {
        return $this->columnConverter($i - 1) . $col;
      }
      return $col;
    }

    private function getCurDate() {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      $time = curl_exec($ch);
      $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      preg_match('/(?<=Date:\s)[^\r\n]*/', substr($time, 0, $header_size), $time);
      curl_close($ch);
      return DateTime::createFromFormat('D, d M Y G:i:s e', $time[0]);
    }
  }
?>