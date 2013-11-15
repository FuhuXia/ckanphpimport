<?php
function add_dataset($server, $map, $dataset) {
  $type = $server['source_type'];
  //mapping fields
  $new_ds = ckan_map($server, $map, $dataset);
  $json_query = json_encode($new_ds);
  $result = curl_http_request($server, $json_query);
  if (empty($result)) {
    die("Problem connecting to " . $server['url_dest'] . ".\n");
  }

  if(!check_unique_name($result)) {
    //get new name until unique
    // TODO ensure unique name before use
    while (1==1) {
      $unique_name = true;
      $new_ds = ckan_map($server, $map, $dataset, $unique_name);
      $json_query = json_encode($new_ds);
      $result = curl_http_request($server, $json_query);
      if (empty($result)) {
        die($server['url_dest']);
      }
      if (check_unique_name($result)) {
        break;
      }
    }
  }

  if (isset($result['__type']) && $result['__type'] == 'Validation Error') {
    die(print_r($result, true) . "\n");
  }
  elseif (isset($result['__type'])) {
    die($result['message'] . "\n");
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
      curl_http_request($server, $json_query, 'resource');
    }

  }

  return isset($result['name'])?$result['name']:$result;
}