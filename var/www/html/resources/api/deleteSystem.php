<?php

//
//deleteSystem.php
//
//This php call should accept ['name':$name] and then attempt to delete a system/payload with that name.
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
  $retval['text'] = 'No payload selected to delete!';
  echo json_encode($retval);
  header('HTTP/1.1 500 Internal Server Error');
}


try {
  $res = $cobbler->call('remove_system', [$inputJSON['name'], $token]);
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
