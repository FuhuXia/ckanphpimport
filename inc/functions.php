<?php

//pad 0 to date/time 
function pad_zero($str) {
  return(substr("00$str", -2));
}

//try to convert english phrase to date in the format of 2001 | 2001-02-27 | 2001-02-27 15:02:48.
//return "" if all failed.
function get_new_date($date_string) {
    
  $date = date_parse($date_string);

  $output = '';

  while (1==1) {
    if ($date['year']) {
      $output .= $date['year'];
    }
    else {
      break;
    }

    if ($date['month'] && $date['day']) {
      $output .= '-' . pad_zero($date['month']) . '-' . pad_zero($date['day']);
    }
    else{
      break;
    }

    if ($date['hour']!==false && $date['minute']!==false && $date['second']!==false) {
      $output .= ' ' . pad_zero($date['hour']) . ':' . pad_zero($date['minute']) . ':' .pad_zero($date['second']);
    }
    else {
      break;
    }

    break;
  }

  return $output;
}

//similiar to get_new_date but more strict.
//return "" if fail to guess.
function guess_new_date($date_string) {
  //set timezone so that strtotime() wont complain.
  date_default_timezone_set('America/New_York');

  $output = '';
  if (preg_match('/^[0-9]{4}$/' , $date_string)) {
    //it is 4-digit year. take as it is.
    $output = $date_string;
  }
  elseif (strtotime($date_string)) {
    //if strtotime can read it, we parse it.
    $output = get_new_date($date_string);
  }
  return $output;
}