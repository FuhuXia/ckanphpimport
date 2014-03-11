<?php

function curl_http_request($server, $json_query="", $action='package') {

  //use $json_query as indicator whether it is a GET request to fetch date
  //or a POST request to write data.
  $b_fetch_not_post = empty($json_query);
  $b_latest = false;

  if ($b_fetch_not_post) { // fetch data
    $src_url = $server["url_src"];
    if ($server['source_type'] == 'ckan') {
      $src_url .= "&rows={$server['pagination_rows']}&start={$server['pagination_start']}";
    }
    $ch = curl_init($src_url);
  }
  elseif (is_array($json_query) && $json_query['purpose'] == 'latest') { // check lastest on destination server
    //todo: $json_query has been re-purposed.
    $b_latest = true;
    $ch = curl_init($server["url_dest"] . "api/action/package_search?q=organization:" . $json_query['data']['owner_org'] . "&sort=metadata_modified%20desc&rows=1");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'X-CKAN-API-Key: ' . $server['api'],
    ));
  }
  else { // Post data
    $ch = curl_init($server["url_dest"] . "api/action/" . $action . "_create");

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_query);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Length: ' . strlen($json_query),
    'Content-Type: application/json;charset=utf-8',
    'X-CKAN-API-Key: ' . $server['api'],
    ));
  }

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  if (!empty($server['auth']) && !empty($server['auth']['user'])) {
    curl_setopt($ch, CURLOPT_USERPWD, $server['auth']['user'] . ":" . $server['auth']['password']); 
  }

  $response = curl_exec($ch);

  curl_close($ch);
  
  if ($b_fetch_not_post || $b_latest) {
    //remove weird chars
    $response = utf8_encode($response);
    $ret = json_decode($response, true);
  }
  else{
    $ret = json_decode($response, true);

    if (empty($ret)) {
      $ret = $response;
    }
    elseif (empty($ret['success'])) {
      $ret = $ret['error'];
    }
    else {
      $ret = $ret['result'];
    }
  }

  return $ret;
}