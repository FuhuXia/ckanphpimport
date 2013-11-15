<?php
//create a new dataset, all fields are mapped from old dataset.
function ckan_map($server, $map, $dataset, $unique_name=0) {
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
      case 'ckan:tags':
        $new_dataset[$key] = array();
        $tags = str_replace(array(";", "\r\n", "\r", "\n", "\t"), ',', $dataset[$value[0]]);
        $tags = explode(',', $tags);
        foreach ($tags as $tag) {
          $clean_tag = preg_replace('/[^a-zA-Z0-9_-]+/', ' ', $tag);
          $clean_tag = trim($clean_tag);
          if (strlen($clean_tag) > 1) {
            $new_dataset[$key][] = array('name' => $clean_tag);
          }
        }

        break;

      case 'json:temporal':
      case 'ckan:temporal_begin':
      case 'ckan:temporal_end':
        $new_date = get_new_date($dataset[$value[0]]);
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
        // catalog.ckan has raw json data as publisher
        // let us decode it.
        $publisher_value = trim($dataset[$value[0]]);
        $publisher_decoded = json_decode($publisher_value, true);
        if ($publisher_decoded && isset($publisher_decoded[0]['name'])) {
          $new_dataset[$key] = $publisher_decoded[0]['name'];
        }
        else {
          $new_dataset[$key] = trim($dataset[$value[0]]);
        }
        break;

      default:
        $new_dataset[$key] = trim($dataset[$value[0]]);

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
  }
  $new_dataset['name'] = trim($new_dataset['name'], '-');
  if ($unique_name) {
    $new_dataset['name'] = substr($new_dataset['name'], 0, 89); //leave room for timestemp
    $new_dataset['name'] = $new_dataset['name'] . '-' . time();
  }
  // 2. owner_org
  $new_dataset['owner_org'] = $server['org'];
  // 3. temporal for  ckan
  if ($type == 'ckan') {
    if (!empty($new_dataset['temporal_begin'])) {
      $new_dataset['temporal'] = $new_dataset['temporal_begin'] . '/' . $new_dataset['temporal_end'];
    }
    unset($new_dataset['temporal_begin'], $new_dataset['temporal_end']);
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
        if (isset($new_dataset[$key])) {
          $new_dataset['extras'][] = array(
            'key' => $key,
            'value' => $new_dataset[$key],
          );
        }
        break;

      case 'accrual_periodicity':
      case 'category':
      case 'language':
        if (isset($new_dataset[$key])) {
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

//helper function to find extras values
function _find_extras_value($dataset, $name) {
  $extras = $dataset['extras'];
  foreach ($extras as $extra) {
    if ($name == $extra['key']) {
      return $extra['value'];
    }
  }
}




