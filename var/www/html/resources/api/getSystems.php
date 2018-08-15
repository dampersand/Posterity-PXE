<?php

//
//getSystems.php
//
//This php call should do nothing more than return the array of system payloads currently on the server.  It expects no input.
//

require_once "definitions.php";
require_once _SECUREINCLUDE_ . "zend-xmlrpc/zend.php";

//define retval
$retval = array(
  'success' => True,
  'code' => 0,
  'text' => 'Success!',
  'data' => array()
);


try {
  $res = $cobbler->call('get_systems');
} catch(Exception $err) {
  $retval['success'] = False;
  $retval['code'] = 1;
  $retval['text'] = 'Unhandled error when querying database';
  echo json_encode($retval);
  header('HTTP/1.1 500 Internal Server Error');
  exit;
}

//translate sytem payloads into the way js expects to see it
$systems = array();

foreach($res as $key => $value) {
  $newSystem = array();
  $newSystem['ks_meta'] = array();
  $newSystem['interfaces'] = $res[$key]['interfaces'];
  $newSystem['netboot_enabled'] = $res[$key]['netboot_enabled'];
  $newSystem['ks_meta']['netmask'] = isset($res[$key]['ks_meta']['ipinfo']['subnet']) ? $res[$key]['ks_meta']['ipinfo']['subnet'] : '';
  $newSystem['ks_meta']['gateway'] = isset($res[$key]['ks_meta']['ipinfo']['gateway']) ? $res[$key]['ks_meta']['ipinfo']['gateway'] : '';
  $newSystem['ks_meta']['ip'] = isset($res[$key]['ks_meta']['serverinfo']['ip']) ? $res[$key]['ks_meta']['serverinfo']['ip'] : '';
  $newSystem['ks_meta']['device'] = isset($res[$key]['ks_meta']['device']) ? $res[$key]['ks_meta']['device'] : '';
  $newSystem['ks_meta']['install_option'] = isset($res[$key]['ks_meta']['misc']['install_option']) ? $res[$key]['ks_meta']['misc']['install_option'] : False;
  $newSystem['ks_meta']['swraid'] = isset($res[$key]['ks_meta']['misc']['swraid']) ? $res[$key]['ks_meta']['misc']['swraid'] : False;
  $newSystem['ks_meta']['gpt'] = isset($res[$key]['ks_meta']['misc']['gpt']) ? $res[$key]['ks_meta']['misc']['gpt'] : False;
  $newSystem['profile'] = $res[$key]['profile'];
  $newSystem['name'] = $res[$key]['name'];
  array_push($systems, $newSystem);
}


$retval['data'] = $systems;
echo json_encode($retval);


?>
