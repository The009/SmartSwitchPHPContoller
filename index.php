<?php
/*
*   SmartSwitchPHPController Copyright (C) 2018 The009
*
*   This program is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
*   Please send queries to the009@gmail.com
*/


//Options For This File

//Enable Debug Ouput (Works better in CLI mode))
 define('debug', false);

//Enable Command Line Interface
 define('cli', false);

//No Further Options
if(cli){
		$ip = $argv[1];
		$port = $argv[2];
		$action = $argv[3];
		$deviceType = $argv[4];
		if(count($argv) == 6){
			$rawCommand = $argv[5];
		}
		else{
			$rawCommand = "";
		}
}
else{
	$ip = isset($_GET['ip']) ? $_GET['ip'] : '';
	$port = isset($_GET['port']) ? $_GET['port'] : '';
	$action = isset($_GET['action']) ? $_GET['action'] : '';
	$deviceType = isset($_GET['deviceType']) ? $_GET['deviceType'] : '';
}

if(debug){
	echo("IP: " . $ip ."\n");
	echo("Port: " . $port . "\n");
	echo("Action: " . $action . "\n");
	echo("DevTyp: " . $deviceType . "\n" );
	echo("RawCMDType: " . $deviceType . "\n" );
}


if($ip)if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)){} else { die("".$ip." is not a valid IP address");}
if($port)if(($port >= 1) && ($port <= 65535)){} else { die("$port is not a valid port");}
if($action)if(preg_match("/^[a-zA-Z]+$/", $action) == 1){} else { die("$action is not a valid action"); }
if($deviceType)if(preg_match("/^[a-zA-Z0-9]+$/", $deviceType) == 1){} else { die("$deviceType is not a valid DeviceType"); }
if(cli)if($action == "raw"){if(json_decode($rawCommand) != null ){} else { die("Your Raw Command dose not appear to be valid JSON!");}}

if(!cli){ $rawCommand = "";}

if( $ip && $port && $action && $deviceType != ""){
	send($action, $deviceType, $ip, $port, $rawCommand);
}

if(!cli){
?>
<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="A PHP Script and Web Interface for Smart Switches">
    <meta name="author" content="The009">

    <title>Smart Switch PHP Controller</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/heroic-features.css" rel="stylesheet">

	<!-- Custom Icon for this template -->
	<link rel="icon" type="image/png" href="vendor/icon/48px-System-shutdown.svg.png" />
  </head>

  <body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <a class="navbar-brand" href="?">TP-Link Switch Control</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <div class="container">

     <!-- Page Features -->
      <div class="row text-center my-3">
<?php }

function getDevices()
{
	if(file_exists("devices.csv")){

	$csv = array_map('str_getcsv', file("devices.csv"));
	array_walk($csv, function(&$a) use ($csv) {
	$a = array_combine($csv[0], $a);
	});
    array_shift($csv);

	}
	else{
		die("No Confing CSV Found");
	}

	foreach($csv as $i => $item) {
echo <<<EOD
	        <div class='col-lg-3 col-md-6 mb-4'>
	          <div class='card'>
		            <div class='card-body'>
		              <h4 class='card-title'>{$item["deviceName"]}</h4>
		              <p class='card-text'></p>
		            </div>
		            <div class='card-footer'>
		              <a href='?ip={$item["deviceIP"]}&amp;port={$item["devicePort"]}&amp;action=On&amp;deviceType={$item["deviceType"]}' class='btn btn-primary'>On</a>  -
					  <a href='?ip={$item["deviceIP"]}&amp;port={$item["devicePort"]}&amp;action=Off&amp;deviceType={$item["deviceType"]}' class='btn btn-primary'>Off</a>
		            </div>
		          </div>
		        </div>
EOD;
	}
}

function send($command , $plugType, $ip, $port, $rawCommand = NULL)
{

	switch(strtolower($command)) {
		case "on": $payload = '{"system":{"set_relay_state":{"state":1}}}';
		break;
		case "off": $payload = '{"system":{"set_relay_state":{"state":0}}}';
		break;
		case "sysinfo": $payload = '{"system":{"get_sysinfo":null}}';
		break;
		case "ledoff": $payload = '{"system":{"set_led_off":{"off":1}}}';
		break;
		case "ledon": $payload = '{"system":{"set_led_off":{"off":0}}}';
		break;
		case "raw": $payload = $rawCommand;
		break;
		case "" : die("No Command");
		break;
	}


	if($plugType == "HS105"){

		if(debug){echo("Using HS105 Encryption \n");}

		$key = 171;
		$message = "\0\0\0" . chr(strlen($payload));
		foreach (str_split($payload) as $cnt1) {
			$a = $key ^ ord($cnt1);
			$key = $a;
			$message .= chr($a);
		}
	}
	else{
    	if(debug){echo("Using HS100/HS110 Encryption \n");}
    	$key = 171;
		$message = "\0\0\0\0";
		foreach (str_split($payload) as $cnt1) {
			$a = $key ^ ord($cnt1);
			$key = $a;
		$message .= chr($a);
		}
	}

	if(debug){ 	echo("rawSentData: " . $message . "\n"); }

	if (!($sock = socket_create(AF_INET, SOCK_STREAM, 0))) {
		$errorCode = socket_last_error();
		$errMsg = socket_strerror($errorCode);
		die("Couldn't create socket: [{$errorCode}] {$errMsg} \n");
	}

	if (!socket_connect($sock, $ip, $port)) {
		$errorCode = socket_last_error();
		$errMsg = socket_strerror($errorCode);
		die("Could not connect: [{$errorCode}] {$errMsg} \n");
	}

	if (!socket_send($sock, $message, strlen($message), 0)) {
		$errorCode = socket_last_error();
		$errMsg = socket_strerror($errorCode);
		die("Could not send data: [{$errorCode}] {$errMsg} \n");
	}

	$buff = 'Buffer STRING';

	if (false !== ($bytes = socket_recv($sock, $buff, 1024, 0))) {
		if(debug){echo "Read {$bytes} bytes of socket_recv(). close socket ...";}
	}
	else{
		if(debug){echo "socket_recv() error; Reason: " . socket_strerror(socket_last_error($socket)) . "\n";}
	}

	socket_close($sock);

	if(debug || ($rawCommand != "")){echo(json_decode(json_encode(decode($buff))));}
}

function decode($encodedMsg)
{
	$string = substr($encodedMsg, 4);
	$key = 171;
	$message = "";
	foreach (str_split($encodedMsg) as $cnt2) {
		$a = $key ^ ord($cnt2);
		$key = ord($cnt2);
		$message .= chr($a);
	}
	return $message;
}




	if(!cli){
		getDevices();
?>

      </div>
      <!-- /.row -->

    </div>
    <!-- /.container -->

    <!-- Footer -->
    <footer class="py-5 bg-dark">
      <div class="container">
        <p class="m-0 text-center text-white">Copyright &copy; The009 <a href="http://www.the009.net" target="_blank">http://www.the009.net</a> 2018</p>
	<p class="m-0 text-center text-white"><a href="http://programs.the009.net/SmartSwitchPHPController/" target="_blank">Version 1.0.0.0</a><br />
	This work is licensed under <a href="https://www.gnu.org/licenses/gpl-3.0.en.html">The GNU General Public License v3.0</a>.</p>
      </div>
      <!-- /.container -->
    </footer>

    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  </body>

</html>
<?php } //Fun Note.. I used Notepad to write this. Sorry if the spacing sucks.?>