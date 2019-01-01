<?php

function buildError($text, $field = NULL) {
  $error = array();
  $error['text'] = $text;
  if (!is_null($field)) {
    $error['field'] = $field;
  }
  return $error;
}

function buildErrorWithContext($text, $context) {
  $error = array();
  $error['text'] = $text;
  if (!is_null($context)) {
    $error['context'] = $context;
  }
  return $error;
}

function displayDate($timestamp) {
  global $session;
  $ret = "";
  if (!is_null($timestamp)) {
    $ret = (new DateTime($timestamp))
              ->setTimezone(new DateTimeZone($session->getTimezone()))
              ->format('d/m/Y');
  }
  return $ret;
}

function displayTime($timestamp) {
  global $session;
  $ret = "";
  if (!is_null($timestamp)) {
    $ret = (new DateTime($timestamp))
              ->setTimezone(new DateTimeZone($session->getTimezone()))
              ->format('H:i');
  }
  return $ret;
}

function displayDateTime($timestamp) {
  global $session;
  $ret = "";
  if (!is_null($timestamp)) {
    $ret = (new DateTime($timestamp))
              ->setTimezone(new DateTimeZone($session->getTimezone()))
              ->format('d/m/Y H:i');
  }
  return $ret;
}

function sendmailbymailgun($to,$toname,$subject,$html){

  global $mg_key;
  global $mg_url;

  $array_data = array(
		'from'=> 'wopogo.uk <no-reply@wopogo.uk>',
		'to'=>$toname.'<'.$to.'>',
		'subject'=>$subject,
		'html'=>$html,
		'text'=>'',
		'o:tracking'=>'yes',
		'o:tracking-clicks'=>'yes',
		'o:tracking-opens'=>'yes',
		'h:Reply-To'=>'admin@wopogo.uk'
  );
  $session = curl_init($mg_url.'/messages');
  curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($session, CURLOPT_USERPWD, 'api:'.$mg_key);
  curl_setopt($session, CURLOPT_POST, true);
  curl_setopt($session, CURLOPT_POSTFIELDS, $array_data);
  curl_setopt($session, CURLOPT_HEADER, false);
  curl_setopt($session, CURLOPT_ENCODING, 'UTF-8');
  curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
  $response = curl_exec($session);
  curl_close($session);
  $results = json_decode($response, true);
  return $results;
}

?>
