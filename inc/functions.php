<?php

//pad 0 to date/time 
function pad_zero($str) {
  return(substr("00$str", -2));
}

//return true if api result does not complain duplicated name.
function check_unique_name($result) {
  if (
    isset($result['__type'])
    &&
    $result['__type'] == 'Validation Error'
    &&
    isset($result['name'][0])
    &&
    $result['name'][0] == 'That URL is already in use.'
  ) {
    $ret = false;
  }
  else {
    $ret = true;
  }
  
  return $ret;
}

//try to convert english phrase to date in the format of 2001 | 2001-02-27 | 2001-02-27 15:02:48.
//return 0000 if all failed.
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

  return $output?$output:"0000";
}

