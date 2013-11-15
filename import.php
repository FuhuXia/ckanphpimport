<?php
require_once('inc/add_dataset.php');
require_once('inc/ckan_map.php');
require_once('inc/curl_http_request.php');
require_once('inc/get_map.php');
require_once('inc/functions.php');

//make sure all args are provided
if (count($argv) < 3) {
  die("Usage: php import.php (environment:)dev|staging|prod (organization:)org-id\n");
}
$env = $argv[1];
$org = $argv[2];

//parse ini file and put all info into $server array
$ini_array = parse_ini_file("ini/server.ini", true);
if (!isset($ini_array[$env])) {
  die("Could not find server info for $env.\n");
}
$server = array(
  "source_type" => $ini_array['source_type'],
  "url_src" => $ini_array['url_src'],
  "url_dest" => $ini_array[$env]['url_dest'],
  "api"    => $ini_array[$env]['api'],
  "auth"   => array(
    'user' => $ini_array[$env]['auth_user'],
    'password' => $ini_array[$env]['auth_pass'],
  ),
  "org" => $org,
  "pagination_rows" => $ini_array['pagination_rows'],
  "pagination_start" => 0,
);

//get mapping for source type
$map = get_map($server['source_type']);
if (empty($map)) {
  die("Could not find map for {$server['source_type']}.\n");
}

// get source data in json
$ret = curl_http_request($server);
if (empty($ret)) {
  die("No datasets fetched.");
}

//post datasets into desination server 
switch ($server['source_type']) {

  case 'json':
    $datasets = $ret;
    $j = count($datasets);
    for ($i=0; $i < $j; $i++) { 
      $dataset_name = add_dataset($server, $map, $datasets[$i]);
      $count = $i + 1;
      echo ('Added ' . $count . '/' . $j . ': '.  $dataset_name . ".\n");
    }
    break;

  case 'ckan':
    //ckan package_search might be paginated.
    $total = $ret['result']['count'];
    $datasets = $ret['result']['results'];
    if ($total == 0 || empty($datasets)) {
     die("No datasets fetched.");
    }

    $finishe_count = 0;
    $batch = 1;
    do {
      if (!$datasets) {
        $server['pagination_start'] = ($batch - 1) * $server['pagination_rows'];
        $ret = curl_http_request($server);
        $datasets = $ret['result']['results'];
      }

      foreach ($datasets as $dataset) {
        $dataset_name = add_dataset($server, $map, $dataset);
        if (strlen($dataset_name)) {
          $finishe_count++;
          echo ('Added ' . $finishe_count . '/' . $total . ': '.  $dataset_name . ".\n");
        }
      }
      $datasets = array();
      $batch++;
    } while ( (($batch - 1) * $server['pagination_rows']) < $total );

    break;

  default:
    //

}


