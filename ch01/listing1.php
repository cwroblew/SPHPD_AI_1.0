<?php

error_reporting(E_ALL);

$name = (! empty($_REQUEST['name'])) ? $_REQUEST['name'] : null;

print <<<HTML

<html>
<head><title>Bad Script</title></head>
<body>
<table border=0 cellpadding=3 cellspacing=0>

<tr>
<td> Your name is </td>
<td> $name </td>
</tr>

</table>
</body>
</html>




HTML;


?>
