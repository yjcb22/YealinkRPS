<?php
require __DIR__ . '/vendor/autoload.php';

use Models\YealinkRps;


$options = getopt("a:s:m:f:hd");

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
        -f Full path to CSV file with list of MAC addresses
        -d WARNING!!!! DELETE the provided MACs. You cannot undo this operation\n";
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
$macsCmd = (isset($options["m"])) ? explode(",", $options["m"]) : null;
$fileMacs = (isset($options["f"])) ? $options["f"] : null;
$delete = (isset($options["d"])) ? array() : null;
// Delete warning
if (isset($options["d"])) {
    echo "DANGER!!! You attempt to delete the provided MACs from the Yealink RPS.\n" .
        "You CANNOT UNDO this action." . "If you want to proceed please type: yes\n";
    $answer = trim(fgets(STDIN));
    if (strcmp($answer, "yes") !== 0) exit(1);
}
//Call the main Class
$rps = new YealinkRps($accessKey, $secretKey);

//Fill an array with the macs passed via cmd or file
if (is_null($fileMacs) and !is_null($macsCmd)) { //Macs passed in-line
    $macs = $macsCmd;
} elseif (!is_null($fileMacs) and is_null($macsCmd)) { //macs passed on txt file    
    $macs = array();
    $file = fopen($fileMacs, 'r');
    while (!feof($file)) {
        $mac = rtrim(fgets($file));
        //A mac address has 12 hex characters.
        if (strlen($mac) == 12) {
            array_push($macs, $mac);
        }
    }
}
//Execute the actions
foreach ($macs as $mac) {
    if ($rps->checkDeviceExists($mac)) {
        echo "Exists? " . $mac . "  True \n";
        // sleep(1);
        $details = $rps->getDeviceDetail($mac);
        if(is_string($details)) continue;
        echo "Details = id: " . $details["data"]["data"][0]["id"] . ", Enterprise: " .
            $details["data"]["data"][0]["enterpriseName"] .
            ", Prov-URL: " . $details["data"]["data"][0]["serverUrl"] .
            ", IP: " . $details["data"]["data"][0]["ipAddress"] . "\n";
        //Add the MAC to an array for later delete
        if (isset($options["d"])) array_push($delete, $details["data"]["data"][0]["id"]);
    } else {
        echo "Exists? " . $mac . "  False \n";
    }
    // sleep(1);
}
if (isset($options["d"])) {
    //If there are not Ids to delete then do not trigger the HTTP request
    if (count($delete) != 0) {
        // sleep(1);
        $devDeleted = $rps->deleteDevices($delete);
        //echo json_encode($devDeleted, JSON_PRETTY_PRINT);
        if (is_null($devDeleted["data"])) {
            echo "Devices deleted in the transaction: 0\n";
        } else {
            echo "Devices deleted in the transaction: " . $devDeleted["data"] . "\n";
        }
    } else {
        echo "Devices deleted in the transaction: 0\n";
    }
}
