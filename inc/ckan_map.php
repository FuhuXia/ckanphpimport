<?php
//create a new dataset, all fields are mapped from old dataset.
function ckan_map($server, $map, $dataset) {
  $type = $server['source_type'];

  //an empty new dataset
  $new_dataset = array(
    'extras' => array(),
  );

  foreach ($map as $key => $value) {
    //skip resources fields
    if ($value[1] == 2) {
      continue;
    }

    //dataset from some sources (e.g. ckan) has some fields in extras.
    //flatten them to the root level.
    //hopefully no key collisions.
    if (isset($value[2]) && $value[2]) {
      $dataset[$value[0]] = _find_extras_value($dataset, $value[0]);
    }

    switch ("$type:$key") {

      case 'json:tags':
      case 'datajson:tags':
      case 'ckan:tags':
        $new_dataset[$key] = array();
        $tags = $dataset[$value[0]];
        if (is_string($tags)) {
          $tags = str_replace(array(";", "\r\n", "\r", "\n", "\t"), ',', $dataset[$value[0]]);
          $tags = explode(',', $tags);
        }
        foreach ($tags as $tag) {
          $clean_tag = preg_replace('/[^a-zA-Z0-9_-]+/', ' ', $tag);
          $clean_tag = trim($clean_tag);
          $max_length = 100;
          if (strlen($clean_tag) > $max_length) {
            //cut at last space before 100th char.
            $clean_tag = preg_replace('/\s+?(\S+)?$/', '', substr($clean_tag, 0, $max_length));
          }
          if (strlen($clean_tag) > 1) {
            $new_dataset[$key][] = array('name' => $clean_tag);
          }
        }

        break;

      case 'datajson:bureau_code':
      case 'datajson:program_code':
      case 'datajson:category':
        $codes = $dataset[$value[0]];
        $new_dataset[$key] = implode(", ", $codes);
        break;

      case 'json:temporal':
        $new_date = get_new_date($dataset[$value[0]]);
        $new_dataset[$key] = $new_date?$new_date:"0000";
        break;

      case 'ckan:temporal':
        $new_date = "0000";
        //do our best to get date out of the string, ideally into startdate/enddate
        $date_value = trim($dataset[$value[0]]);
        $ret = guess_new_date($date_value);
        if ($ret) {
          $new_date = $ret;
        }
        else {
          //it is string that php does not understand.
          //let us try to split it to see if it is two date in the format of "start to end".
          $split = str_replace(array('to', 'through'), ",", $date_value);
          $split = explode(',', $split);
          if ($split && count($split) == 2) {
            $start_date = guess_new_date(trim($split[0]));
            $end_date = guess_new_date(trim($split[1]));
            if ($start_date && $end_date) {
              $new_date = $start_date . '/' . $end_date;
            }
          }
          else {
            //try again with splitting "-"
            $split = explode('-', $date_value);
            if ($split && count($split) == 2) {
              $start_date = guess_new_date(trim($split[0]));
              $end_date = guess_new_date(trim($split[1]));
              if ($start_date && $end_date) {
                $new_date = $start_date . '/' . $end_date;
              }
            }
          }
        }
        $new_dataset[$key] = $new_date;
        break;

      case 'json:release_date':
        $new_dataset[$key] = $new_date;
        if (empty($dataset[$value[0]])) {
          $new_dataset[$key] = '0000';
        }
        break;

      case 'json:homepage_url':
      case 'ckan:homepage_url':
      case 'json:system_of_records':
        // must be a valid url or nothing
        $url_value = trim($dataset[$value[0]]);
        if (filter_var($url_value, FILTER_VALIDATE_URL) !== FALSE) {
          $new_dataset[$key] = $url_value;
        }
        break;

      case 'json:data_dictionary':
      case 'ckan:data_dictionary':
        // must be a valid url
        $url_value = trim($dataset[$value[0]]);
        if (filter_var($url_value, FILTER_VALIDATE_URL) !== FALSE) {
          $new_dataset[$key] = $url_value;
        }
        else {
          $new_dataset[$key] = "http://localdomain.local/";
        }
        break;

      case 'json:data_quality':
        // cant left empty
        $quality_value = strtolower(trim($dataset[$value[0]]));
        if (in_array($quality_value, array('off', 'false', 'no', '0'))) {
          $quality_value = false;
        }
        else {
          $quality_value = true;
        }
        $new_dataset[$key] = $quality_value;
        break;

      case 'ckan:publisher':
        // TODO for now hard-coded to $dataset.organization.title
        $new_dataset[$key] = trim($dataset['organization']['title']);
        break;

      case 'ckan:public_access_level':
        // use default if missing value
        $new_dataset[$key] = trim($dataset[$value[0]])?trim($dataset[$value[0]]):'public';
        break;

      default:
        $new_dataset[$key] = $dataset[$value[0]];
        if (is_string($new_dataset[$key])) {
          $new_dataset[$key] = trim($new_dataset[$key]);
        }

    }
  }


  //create necessary new fields:
  // 1. name
  if ($type == 'ckan') {
    $new_dataset['name'] = $dataset['name'];
  }
  else {
    //replace anything weird with "-"
    $new_dataset['name'] = preg_replace('/[\s\W]+/', '-', strtolower($new_dataset['title']));
    $new_dataset['name'] = substr($new_dataset['name'], 0, 100);
    $new_dataset['name'] = trim($new_dataset['name'], '-');
  }
  // 2. owner_org
  if ($type == 'ckan' && $server['ckan_use_src_org'] && isset($dataset['organization']['name'])) {
    //use src org
    $org_name = $dataset['organization']['name'];
    if (isset($server['ckan_src_org_map'][$org_name]) && strlen($server['ckan_src_org_map'][$org_name]) !== 0) {
      $org_name = $server['ckan_src_org_map'][$org_name];
    }
    $new_dataset['owner_org'] = $org_name;
  }
  else {
    //use default org supplied by command argument
    $new_dataset['owner_org'] = $server['org'];
  }

  //clean up work.
  $new_dataset['publisher'] = strlen($new_dataset['publisher'])?$new_dataset['publisher']:"N/A";
  $new_dataset['publisher'] = substr($new_dataset['publisher'], 0, 300);
  $new_dataset['contact_name'] = strlen($new_dataset['contact_name'])?$new_dataset['contact_name']:"N/A";
  $new_dataset['contact_email'] = (strpos($new_dataset['contact_email'], "@")!==false)?$new_dataset['contact_email']:"NA@localdomain.local";

  //validator can goes here
  //

  //not sure what is with this ckan
  //but we have to replicate some keys in both root and extras
  foreach ($map as $key => $value) {

    switch ($key) {

      case 'publisher':
      case 'contact_email':
      case 'contact_name':
      case 'homepage_url':
      case 'system_of_records':
      case 'related_documents':
      case 'data_dictionary':
        if (isset($new_dataset[$key]) && !empty($new_dataset[$key])) {
          $new_dataset['extras'][] = array(
            'key' => $key,
            'value' => $new_dataset[$key],
          );
        }
        break;

      case 'accrual_periodicity':
      case 'category':
      case 'language':
        if (isset($new_dataset[$key]) && !empty($new_dataset[$key])) {
          $new_dataset['extras'][] = array(
            'key' => $key,
            'value' => $new_dataset[$key],
          );
        }
        //something additional to do. remove not used keys
        unset($new_dataset[$key]);
        break;

      default:
        //
    }
  }

  return $new_dataset;
}


function ckan_map2($server, $dataset, $new_dataset) {

  $extras = array();

  $map = array(
    'publisher'                 => 'publisher',
    'public_access_level'       => 'accessLevel',
    'system_of_records'         => 'systemOfRecords',
    'temporal'                  => 'temporal',
    'accrual_periodicity'       => 'accrualPeriodicity',
    'release_date'              => 'issued',
    'access_level_comment'      => 'accessLevelComment',
    'primary_it_investment_uii' => 'PrimaryITInvestmentUII',
    'data_dictionary'           => 'dataDictionary',
    'homepage_url'              => 'landingPage',
    'contact_email'             => 'mbox',
    'unique_id'                 => 'identifier',
    'spatial'                   => 'spatial',
    'contact_name'              => 'contactPoint',
    'data_quality'              => 'dataQuality',
  );

  $map_array = array(
    'category'           => 'theme',
    'language'           => 'language',
    'program_code'       => 'programCode',
    'bureau_code'        => 'bureauCode',
    'related_documents'  => 'references',
  );

  foreach ($map as $new => $old) {
    if (isset($dataset[$old])) {
      $extras[] = array(
        'key' => $new,
        'value' => $dataset[$old],
      );
    }
  }

  foreach ($map_array as $new => $old) {
    if (isset($dataset[$old])) {
      $extras[] = array(
        'key' => $new,
        'value' => implode(", ", $dataset[$old]),
      );
    }
  }

  // temporarily hold the modified field.
  $extras[] = array(
    'key' => 'modified',
    'value' => $dataset['modified'],
  );

  // add one unique element just in case some weird ckan bug.
  $extras[] = array(
    'key' => 'ckanphpimport',
    'value' => uniqid(),
  );

  $new_dataset['extras'] = $extras;

  return $new_dataset;
}

//helper function to find extras values
function _find_extras_value($dataset, $name) {
  $extras = $dataset['extras'];
  foreach ($extras as $extra) {
    if ($name == $extra['key']) {
      return $extra['value'];
    }
  }
}




