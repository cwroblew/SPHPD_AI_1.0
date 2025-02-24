<?php

// Set error reporting to all
error_reporting(E_ALL);


// Get domain name
$secureDomain  = (! empty($_REQUEST['domain'])) ?
           escapeshellcmd($_REQUEST['domain']) : null;

// The WHOIS binary path
$WHOIS = '/usr/bin/whois';

echo "Running whois for $secureDomain <br>";

// Execute WHOIS request
exec("$WHOIS $secureDomain", $output, $errors);

// Initialize output buffer
$buffer = null;

while (list(,$line)=each($output))
{
    if (! preg_match("/Whois Server Version/i", $line))
    {
        $buffer .= $line . '<br>';
    }
}

echo $buffer;

if (! empty($errors))
{
   echo "Error: $errors when trying to run $WHOIS<br>";
}


?>


