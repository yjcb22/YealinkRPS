<?php
require __DIR__.'/vendor/autoload.php';
use Models\YealinkRps;


$options = getopt("a:s:m:f:h");

// Check that correct options are sent via CMD
if (sizeof($options) == 0) {
    echo "You need to pass at least 2 arguments: API access key and secret key.
    Correct usage:
    -a Required: Yealink API Access Key
    -s Required: Yealink API Secret Key\n";
    exit(1);
} elseif (isset($options["h"])) {
    echo "Correct usage:
        -a Required: Yealink API Access Key
        -s Required: Yealink API Secret Key
        -m MAC address/es (multiple splitted by comma with NO SPACE)
        -f path to CSV file with list of MAC addresses\n";
    exit(0);
} else {
    if (!isset($options["a"])) {
        echo "Missing API Access key. -h for help\n";
        exit(1);
    }
    if (!isset($options["s"])) {
        echo "Missing API Secret key. -h for help\n";
        exit(1);
    }
    if (!isset($options["m"]) and !isset($options["f"])) {
        echo "You need to pass either a csv with MACs or the MACs in the command line.
        Options -m or -f.
        Use -h for more help\n";
        exit(1);
    }
}

//Initialize variables
$accessKey = $options["a"];
$secretKey = $options["s"];
$macs = (isset($options["m"])) ? explode(",", $options["m"]) : null;
$csvMacs = (isset($options["f"])) ? $options["f"] : null;
//Call the main Class
$rps = new YealinkRps($accessKey, $secretKey);
//Macs passed in-line
if(is_null($csvMacs) and !is_null($macs)){
    foreach ($macs as $value) {
        $rps->checkDeviceExists($value);
    }
} elseif(!is_null($csvMacs) and is_null($macs)){ //CSV sent

}