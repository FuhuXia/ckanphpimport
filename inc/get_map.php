<?php
function get_map($source) {
  // 'dest_key' => array('source_key', dest_position)
  // for dest_position, 0/1/2 denotes root/extras/resources.
  $map_json = array(
    'title' => array('title', 0),
    'notes' => array('description', 0),
    'publisher' => array('publisher', 1),
    'public_access_level' => array('accessLevel', 1),
    'contact_email' => array('mbox', 1),
    'contact_name' => array('person', 1),
    'unique_id' => array('identifier', 1),
    'tags' => array('keyword', 0),
    'data_dictionary' => array('dataDictionary', 1),
    'license_title' => array('license', 0),
    'category' => array('theme', 1),
    'spatial' => array('spatial', 1),
    'temporal' => array('temporal', 1),
    'release_date' => array('issued', 1),
    'accrual_periodicity' => array('accrualPeriodicity', 1),
    'related_documents' => array('references', 1),
    'language' => array('language', 1),
    'data_quality' => array('dataQuality', 1),
    'homepage_url' => array('landingPage',1),
    'system_of_records' => array('systemOfRecords', 1),
    'url' => array('accessURL',2),
    'format' => array('format',2),
  );

  // 'dest_key' => array('source_key', dest_position, source_position)
  // 0/1/2 denotes root/extras/resources.
  $map_ckan = array(
    'title' => array('title', 0, 0),
    'notes' => array('notes', 0, 0),
    // hard-code publisher to orgnization.title in ckan_map for now.
    // todo: change this map structure.
    'publisher' => array('place-holder', 1, 1),
    'public_access_level' => array('access-level', 1, 1),
    'contact_email' => array('contact-email', 1, 1),
    'contact_name' => array('person', 1, 1),
    'unique_id' => array('id', 1, 0),
    'tags' => array('tags', 0, 1),
    'data_dictionary' => array('data-dictiionary', 1, 1), // there is a typo.
    'license_title' => array('license_title', 0, 0),
    'spatial' => array('spatial-text', 1, 1),
    'temporal' => array('dataset-reference-date', 1, 1),
    'release_date' => array('issued', 1, 1),
    'accrual_periodicity' => array('frequency-of-update', 1, 1),
    'related_documents' => array('references', 1, 1),
    'language' => array('metadata-language', 1, 1),
    'homepage_url' => array('url',1, 0),
    'url' => array('url',2, 2),
    'name' => array('name',2, 2),
    'format' => array('format',2, 2),
  );

  $ret_map = "map_$source";
  return isset($$ret_map)?$$ret_map:null;
}
