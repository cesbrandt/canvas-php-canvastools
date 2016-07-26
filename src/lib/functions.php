<?php
  // Verify the configuration is being called by a CanvasTools file
  if(!defined('IN_CANVASTOOLS')) {
    exit;
  }

  function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
  }

  function fullescape($str) {
    $output = '';
    for($i = 0; $i < strlen($str); $i++) {
      $output .= (preg_match('/[a-zA-Z\d]/', $str[$i]) == true) ? $str[$i] : '%' . dechexX(ordX($str[$i]));
    }
    return $output;
  }
  
  function ordX($str) {
    $dec = '';
    for($i = 0; $i < strlen($str); $i++) {
      $char = mb_substr($str, $i, 1, 'utf-8');
      for($j = 0; $j < strlen($char); $j++) {
        $dec .= ord($char[$j]) . ' ';
      }
    }
    return trim($dec);
  }
  
  function dechexX($str) {
    $hex = '';
    $str = explode(' ', $str);
    foreach($str as $chr) {
      $hex .= dechex($chr) . ' ';
    }
    return strtoupper(trim($hex));
  }
?>