#!/usr/bin/php
<?php
ini_set("display_errors", "on");
error_reporting("E_ALL ~& E_NOTICE");

require "Mail/mimeDecode.php";
require "/full/path/to/class.php-prowl.php";

// Take input of the email directly from stdin
$fd = fopen("php://stdin", "r");
$email = "";
while (!feof($fd))
{
	$email .= fread($fd, 1024);
}
fclose($fd);

// Now process this email notification
$params['include_bodies'] = true;
$params['decode_bodies'] = true;
$params['decode_headers'] = true;
$params['input'] = $email;
$structure = Mail_mimeDecode::decode($params);

// Only process emails that are of the 'Notify' variety, this weeds out the
// 'Owner' notifications that will end up in duplicate alerts
if (preg_match("/notify/is",$structure->headers["subject"])) {
	// Get the timestamp this email was sent at
	$timestamp = strtotime($structure->headers["date"]);
	
	// Regex the message body to get the data we need from it
	preg_match("/For ([^:]+): ([^()]+) \(([^()]+)\).+Location: (.+?)\n([^()]+) \(.+Log Date: (.+?\d) ?\n(.+?)Visit.+(GL.+?)\n.+Profile for ([^:]+):.+(PR.+?) ?\n/si", $structure->body, $matches);
	
	$id = $matches[1];			// GeoCache ID
	$title = $matches[2]; 		// Title of this cache
	$type = $matches[3];   		// Type of cache
	$location = $matches[4];  	// Location of this cache
	$distance = $matches[5];  	// Distance from your home co-ordinates
	$date = $matches[6];   		// Date this log was created
	$log = trim($matches[7]);  	// The log message
	$username = $matches[9];  	// Username of the Geocaching.com User
	$link_log = $matches[8];  	// ID for Link to the log message
	$link_user = $matches[10];  // ID for Link to user profile
	
	// Strip through the subject to get the 'action' of this message, such as found,
	// did not find, archived, published etc
	$subject = str_replace("[GEO] Notify: ", "", $structure->headers["subject"]);
	$action = str_replace($title, "", $subject);
	$action = str_replace("(".$type.")", "", $action);
	$action = trim(str_replace($username, "", $action));
	
	// Send a notification out via Prowl
	$prowl = new Prowl();
	$prowl->setApiKey("3d6d41d6a5fb532a51ed40b3fR45tdeb97b6e86d");
	
	$application = "Geocaching.com";
	$event = "$username $action $title ($id)";
	$text = $log;
	$url = "http://coord.info/".$link_log;
	$priority = 0;
	
	$message = $prowl->push($application, $event, $text, $url, $priority);
}
?>