#!/usr/bin/php 

<?php 
#php-opencloud installed 2 directories up
require '../../vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

#Trying to get file with credentials
$pw_file_name = $_SERVER['HOME'] . "/.rackspace_cloud_credentials";
$pw_file=file_get_contents($pw_file_name);
if ($pw_file === false)
{
	#No file found
	printf("Error opening your credentials file at: %s\n", $pw_file_name);
    die();
} else {
    #File founded, now checking for credentials
	 $rows = explode("\n", $pw_file);
	 foreach ($rows as $r){
		 $line = explode(":", $r);
		 if ($line[0] == "username") $username = $line[1];
		 if ($line[0] == "key") $key = $line[1];
		
	 }
} 

printf("Starting Rackspace connection as username: %s\n", $username);

$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
    'username' => $username,
    'apiKey'   => $key
));

#Hardcoding to DFW Datacenter
$compute = $client->computeService('cloudServersOpenStack', 'DFW');
#Hardcoding to Scientific Linux 
$server_image = $compute->image('bced783b-31d2-4637-b820-fa02522c518b');
#Hardcoding to 512Mb Cloud server
$server_flavor = $compute->flavor('2');


$server = $compute->server();


$callback = function($server) {
    if (!empty($server->error)) {
        var_dump($server->error);
        exit;
    } else {
        echo sprintf(
            "Waiting on %s/%-12s %4s%%\n",
            $server->name(),
            $server->status(),
            isset($server->progress) ? $server->progress : 0
        );
    }
};




try {
    $response = $server->create(array(
        'name'     => 'My SL Server',
        'image'    => $server_image,
        'flavor'   => $server_flavor,
        'networks' => array(
            $compute->network(Network::RAX_PUBLIC),
            $compute->network(Network::RAX_PRIVATE)
        )
    ));
	$server->waitFor(ServerState::ACTIVE, 600, $callback);
	
} catch (\Guzzle\Http\Exception\BadResponseException $e) {

    // No! Something failed. Let's find out:

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();
	echo sprintf('Status: %s\nBody: %s\nHeaders: %s', $statusCode, $responseBody, implode(', ', $headers));
}


printf("Your new server ID is %s\n", $server->id);
printf("Your new server root password is: %s\n",$server->adminPass);
printf("Your new server Public IP is: %s\n",$server->accessIPv4);



?>
