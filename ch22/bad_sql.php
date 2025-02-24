<?php

  $where = $_REQUEST['where'];

  $stmt = "SELECT * FROM YOUR_TBL WHERE USER_ID > $where";

  echo $stmt;
?>
