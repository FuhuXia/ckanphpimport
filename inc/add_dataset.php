<?php
function add_dataset($server, $map, $dataset) {
  $type = $server['source_type'];
  //mapping fields
  $new_ds = ckan_map($server, $map, $dataset);
  $json_query = json_encode($new_ds);


  //add dataset to destination server
  //then check result
  //repeat if necessary
  while (1==1) {
    $result = curl_http_request($server, $json_query);
    $check = check_result($server, $result);

    if ($check['success']) { //all good. break the loop and move on.
      break;
    }

    //something wrong. retry or quit right away.
    if (!$check['retry']) {
      //workaround to the false server error message.
      $json_query = array(
        'purpose' => 'latest',
        'data' => $new_ds,
      );
      $result = curl_http_request($server, $json_query);
      if (!$result || !$result['result']['results'] || $result['result']['results'][0]['name'] != $new_ds['name']) {
        die($check['message'] . "\n");
      }
      else {
        $result = $result['result']['results'][0];
        break;
      }
    }

    if ($check['message'] == 'NAME_NOT_UNIQUE') {
      $new_ds['name'] = substr($new_ds['name'], 0, 89); //leave room for timestemp
      $new_ds['name'] = $new_ds['name'] . '-' . time();
      $json_query = json_encode($new_ds);
      continue;
    }
    elseif ($check['message'] == 'ORG_NOT_EXISTS') {
      //create new org
      $org_data = array(
        'name' => $new_ds['owner_org'],
        'title' => ($type == 'ckan' && isset($dataset['organization']['title']))? $dataset['organization']['title'] : $new_ds['owner_org'],
        'image_url' => ($type == 'ckan' && isset($dataset['organization']['image_url']))? $dataset['organization']['image_url'] : "",
        'description' => ($type == 'ckan' && isset($dataset['organization']['description']))? $dataset['organization']['description'] : "",
      );
      $json_query = json_encode($org_data);
      $ret = curl_http_request($server, $json_query, 'organization');
      if (isset($ret['type']) && $ret['type'] == 'organization') {
        $org_name = $ret['name'];
        $org_title = $ret['title'];
        echo ("--- New organization added ---\n");
        echo ("    Name: $org_name\n");
        echo ("    Title: $org_title\n");
      }
      else {
        die(print_r($ret, true) . "\n");
      }
      $new_ds['owner_org'] = $org_name;
      $json_query = json_encode($new_ds);
      continue;
    }
  }

  // need to create resource?
  $b_has_resource = false;
  if (
    ($type == 'json' && !empty($dataset['accessURL'])) 
    || 
    ($type == 'ckan' && !empty($dataset['resources']))
    ) {
    $b_has_resource = true;
  }

  if ($b_has_resource) {
    $resources = array();
    switch ($type) {
      case 'json':
        //somehow our json source has flattend resource structure, mistakenly.
        //let us do this until it is corrected.
        //todo
        $resources[] = $dataset;
        break;

      case 'ckan':
        $resources = $dataset['resources'];
        break;

      default:
        //
        break;
    }

    foreach ($resources as $resource) {
      $data = array(
        'package_id' => $result['id'],
      );

      foreach ($map as $key => $value) {
        if ($value[1] != 2) {
          continue;
        }
        $data[$key] = $resource[$value[0]];
      }

      $json_query = json_encode($data);
      $ret = curl_http_request($server, $json_query, 'resource');
      if (empty($ret['id'])) {
        die(print_r($ret, true) . "\n");
      }
    }

  }

  // all done. return dataset name to caller.
  return $result['name'];
}


function check_result($server, $result) {
  $ret = array(
    'success' => 0, //default to fail
    'retry' => 0, //default to die without try
    'message' => "",
  );

  if (!isset($result['__type']) && isset($result['name'])) {
    $ret['success'] = 1;
  }
  elseif (empty($result)) {
    //die with message
    $ret['message'] = "Problem connecting to " . $server['url_dest'];
  }
  elseif (isset($result['__type']) && $result['__type'] == 'Validation Error' && isset($result['name']) && $result['name'][0] == 'That URL is already in use.') {
    $ret['retry'] = 1;
    $ret['message'] = "NAME_NOT_UNIQUE";
  }
  elseif (isset($result['__type']) && $result['__type'] == 'Validation Error' && isset($result['owner_org']) && $result['owner_org'][0] == 'Organization does not exist') {
    $ret['retry'] = 1;
    $ret['message'] = "ORG_NOT_EXISTS";
  }
  else {
    //die with message 
    $ret['message'] = print_r($result, true);
  }

  return $ret;
}
