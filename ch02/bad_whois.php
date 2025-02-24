<?php

// Set error reporting to all
error_reporting(E_ALL);

// Get domain name
$domain = (! empty($_REQUEST['domain'])) ? 
           $_REQUEST['domain'] : null;

// The WHOIS binary path
//$WHOIS = '/usr/bin/whois';
$WHOIS = 'g:/cygwin/bin/whois';

// Execute WHOIS request
exec("$WHOIS $domain", $output, $errors);

// Initialize output buffer
$buffer = null;

while (list(,$line)=each($output)) 
{
    $buffer .= $line . '<br>';
}

echo $buffer;

if (! empty($errors))
{
   echo "Error: $errors when trying to run $WHOIS<br>";
}

?>


