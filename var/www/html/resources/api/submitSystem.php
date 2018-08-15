<?php

//
//submitSystem.php
//
//This php call should receive a ton of inputJSON variables and translate them to cobbler.
//Rather than structure the variables in javascript, I've elected to structure them in php, as php should be
//the layer that understands cobbler.  This means the logic here is a bit uglier, but the responsibilities
//of each individual part of the system are more defined.
//

//dependencies
require_once "definitions.php";
require_once _SECUREINCLUDE_ . "zend-xmlrpc/zend.php";

//settings
$automated = ['centos7'];

//define retval
$retval = array(
  'success' => True,
  'code' => 0,
  'text' => 'Success!',
  'data' => array()
);

//get input variables
$inputJSON = $_POST;

//check for correct input
if (!isset($inputJSON['name'])) {
  $retval['success'] = False;
  $retval['code'] = 2;
  $retval['text'] = 'Insufficient Input';
  echo json_encode($retval);
  header('HTTP/1.1 500 Internal Server Error');
  exit;
}

if ($inputJSON['name'] == '') {
  $retval['success'] = False;
  $retval['code'] = 3;
  $retval['text'] = 'Need a payload name to create/edit!';
  echo json_encode($retval);
  header('HTTP/1.1 500 Internal Server Error');
}

//build all input for use, using blank/false as default if nothing is there.
//also parse booleans correctly because apparently we can't post booleans?
$inputJSON['ip'] = isset($inputJSON['ip']) ? $inputJSON['ip'] : '';
$inputJSON['gateway'] = isset($inputJSON['gateway']) ? $inputJSON['gateway'] : '';
$inputJSON['netmask'] = isset($inputJSON['netmask']) ? $inputJSON['netmask'] : '';
$inputJSON['profile'] = isset($inputJSON['profile']) ? $inputJSON['profile'] : '';
$inputJSON['mac'] = isset($inputJSON['mac']) ? $inputJSON['mac'] : '11:22:33:44:55:66';
$inputJSON['interface'] = isset($inputJSON['interface']) ? $inputJSON['interface'] : '';
$inputJSON['install_option'] = isset($inputJSON['install_option']) ? $inputJSON['install_option'] : 'full_install';
$inputJSON['swraid'] = isset($inputJSON['swraid']) ? filter_var($inputJSON['swraid'], FILTER_VALIDATE_BOOLEAN) : False;
$inputJSON['gpt'] = isset($inputJSON['gpt']) ? filter_var($inputJSON['gpt'], FILTER_VALIDATE_BOOLEAN) : False;
$inputJSON['netboot-enabled'] = isset($inputJSON['netboot-enabled']) ? filter_var($inputJSON['netboot-enabled'], FILTER_VALIDATE_BOOLEAN) : False;

//this section now obsolete, kept in case we need it 
//assemble input in the way cobbler wants it
//$kopts = array();
//$kopts['ip'] = $inputJSON['ip'] . '::' . $inputJSON['gateway'] . ':' . $inputJSON['netmask'] . ':' . $inputJSON['name'] . '.inmotionhosting.com:' . $inputJSON['interface'] . ':none';
//$kopts['nameserver'] = '8.8.8.8';

$ksmeta = array();
$ksmeta['serverinfo'] = array();
$ksmeta['ipinfo'] = array();
$ksmeta['misc'] = array();
$ksmeta['serverinfo']['server'] = $inputJSON['name'];
$ksmeta['serverinfo']['ip'] = $inputJSON['ip'];
$ksmeta['ipinfo']['gateway'] = $inputJSON['gateway'];
$ksmeta['ipinfo']['subnet'] = $inputJSON['netmask'];
$ksmeta['device'] = $inputJSON['interface'];
$ksmeta['misc']['kickencrypted'] = $encRoot;
$ksmeta['misc']['t3key'] = $T3Key;
$ksmeta['misc']['install_option'] = $inputJSON['install_option'];
$ksmeta['misc']['swraid'] = $inputJSON['swraid'];
$ksmeta['misc']['gpt'] = $inputJSON['gpt'];

$modify_interface = array();
$modify_interface['macaddress-eth1'] = $inputJSON['mac'];

try {
  $id = $cobbler->call('new_system', $token);
  $cobbler->call('modify_system', [$id, 'name', $inputJSON['name'], $token]);
  $cobbler->call('modify_system', [$id, 'profile', $inputJSON['profile'], $token]);
  $cobbler->call('modify_system', [$id, 'modify_interface', $modify_interface, $token]);
  $cobbler->call('modify_system', [$id, 'netboot_enabled', $inputJSON['netboot-enabled'], $token]);
  if (in_array($inputJSON['profile'], $automated)) {
//    $cobbler->call('modify_system', [$id, 'kernel_options', $kopts, $token]); //obsolete
    $cobbler->call('modify_system', [$id, 'ksmeta', $ksmeta, $token]);
  }
  $res = $cobbler->call('save_system', [$id, $token]);
  $cobbler->call('sync', $token);

} catch(Exception $err) {
  $retval['success'] = False;
  $retval['code'] = 1;
  $retval['text'] = 'Unhandled error when querying database';
  echo json_encode($retval);
  header('HTTP/1.1 500 Internal Server Error');
  exit;
}

$retval['data'] = $res;
echo json_encode($retval);
?>
