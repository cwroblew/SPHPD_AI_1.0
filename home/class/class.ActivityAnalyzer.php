<?php

   class ActivityAnalyzer
   {
      function ActivityAnalyzer($dbi = null)
      {
          global $ACTIVITY_TBL;
          $this->dbi = $dbi;

          $this->activity_tbl = $ACTIVITY_TBL;
      }
      
      function getDailyStartTS($start, $end, $uid)
      {
          $stmt = "SELECT MIN(ACTION_TS) AS START_TIME FROM $this->activity_tbl WHERE USER_ID = $uid "
                 ."AND ACTION_TS BETWEEN $start AND $end";
          //echo $stmt;
          $result = $this->dbi->query($stmt);
          if ($result->numRows() <= 0 )
          {
             return null;
          }
          $row = $result->fetchRow();
          return $row->START_TIME;
      }
      
      function getDailyEndTS($start, $end, $uid)
      {
          $stmt = "SELECT MAX(ACTION_TS) AS END_TIME FROM $this->activity_tbl WHERE USER_ID = $uid "
                 ."AND ACTION_TYPE = 2 AND ACTION_TS BETWEEN $start AND $end";
          $result = $this->dbi->query($stmt);
          if ($result->numRows() <= 0 )
          {
             return null;
          }
          $row = $result->fetchRow();
          return $row->END_TIME;
      }
      
      function getDailyActivityInfo($start, $end, $uid)
      {
      	$stmt = "SELECT ACTION_TYPE, ACTION_TS FROM $this->activity_tbl WHERE USER_ID = $uid "
      	       ."AND ACTION_TS BETWEEN $start AND $end ORDER BY ACTION_TS";
      	       
          $result = $this->dbi->query($stmt);
          if ($result == NULL || $result->numRows() <= 0 )
          {
             return null;
          }
          $activityArr = array();
          while ($row = $result->fetchRow())
          {
             $activityArr[] = $row->ACTION_TYPE.'=>'.$row->ACTION_TS;
          }
          return $activityArr;
      }
      
      function logUserOut($uid, $time)
      {
          $stmt = "INSERT INTO $this->activity_tbl(USER_ID, ACTION_TYPE, ACTION_TS) VALUES($uid, 2, $time)";
          $result = $this->dbi->query($stmt);
          return ($result == DB_OK) ? true : false;
      }
      
      function logUserIn($uid, $time)
      {
          $stmt = "INSERT INTO $this->activity_tbl(USER_ID, ACTION_TYPE, ACTION_TS) VALUES($uid, 1, $time)";
          $result = $this->dbi->query($stmt);
          return ($result == DB_OK) ? true : false;
      }

      function analyzeDailyActivity($params)
      {
          $activityArr = $this->getDailyActivityInfo($params['DAY_START'],
                                                     $params['DAY_END'],
                                                     $params['USER_ID']
                                                    );
          $totalOffice = $totalExtra = 0;
          if (empty($activityArr))
          {
             return null;
          }


          foreach($activityArr as $value)
          {
              list($type, $ts) = explode('=>', $value);
          	if ($type == 1 && empty($startcount))
          	{
          		$startcount = $ts;
          	}
          	else if ($type == 2 && !empty($startcount))
          	{
          		$breakdown = $this->getOfficeAndExtraBreakdown($params, $ts, $startcount);

          		$totalOffice += $breakdown['OFFICE'];
          		
          		$totalExtra  += $breakdown['EXTRA'];
          		
          		$startcount = null;
          	}
          }

          $analysis = array(
          				  'EXTRA' => $totalExtra,
          				  'OFFICE' => $totalOffice

                           );
          return $analysis;

      }
      
      function getDailyLog($params)
      {
      	  $activityArr = $this->getDailyActivityInfo($params['DAY_START'],
                                                     $params['DAY_END'],
                                                     $params['USER_ID']
                                                    );
                                                    
          if (empty($activityArr))
          {
             return null;
          }
          
          foreach($activityArr as $value)
          {
                list($type, $ts) = explode('=>', $value);
          	if ($type == 1 && empty($startcount))
          	{
          		$startcount = $ts;
          	}
          	else if ($type == 2 && !empty($startcount))
          	{
          		$breakdown[] = $this->getLogs($params, $ts, $startcount);
          		$startcount = null;

          	}
          }
          return isset($breakdown) ? $breakdown : NULL;
          
      }
      
      function getLogs($params, $end, $start)
      {
          $retArr = array();
          $retArr['login'] = date("h:i a",$start);
          $retArr['logout'] = date("h:i a",$end);
          
          
          global $WEEKEND;
	  $office = $extra = 0;
	  
          if (in_array(date('D', $start) , $WEEKEND))
          {
            $office = 0;
            $extra = $end - $start;
          }
          else
          {
             if ($start < $params['OFFICE_START'] && $end < $params['OFFICE_START'])
             {
             	 $extra += ($end - $start);
             }
             if ($start < $params['OFFICE_START'] && $end > $params['OFFICE_START'])   // Extra hours before office hours
             {
                 $extra += ($params['OFFICE_START'] - $start);
                 $start = $params['OFFICE_START'];
             }
             if ($start<$params['OFFICE_END'] && $end > $params['OFFICE_END'])  // Extra hours after office hours
             {
                 $extra += ($end - $params['OFFICE_END']);
                 $end = $params['OFFICE_END'];
             }
             if ($start > $params['LUNCH_START'] && $start < $params['LUNCH_END'])  //removing lunch hour from count
             {
                 $start = $params['LUNCH_END'];
             }
             if ($end > $params['LUNCH_START'] && $end < $params['LUNCH_END'])  //removing lunch hour from count
             {
             	$end = $params['LUNCH_START'];
             }
             if ($end > $params['LUNCH_END'] && $start < $params['LUNCH_START'])  //removing lunch hour from count
             {
                 
                 $office = ($end - $start) - ($params['LUNCH_END'] - $params['LUNCH_START']);
                 
             }
             else
             {
                 //If the whole timespan of the work is within lunch hour it won't be counted as office hour, not even extra hour
                 if ($start > $params['LUNCH_START'] && $start < $params['LUNCH_END'] && $end > $params['LUNCH_START'] && $end < $params['LUNCH_END'])
                 {
                    $office = 0;
                 }
                 else if ($start > $params['OFFICE_END'] || $end < $params['OFFICE_START'])
                 {
                    $office = 0;	
                 }
                 else if ($start > $params['LUNCH_START'] && $end< $params['LUNCH_END'])
                 {
                    $office = 0;	
                 }
                 else
                 {  
                    $office = $end - $start;
                 }
             }
             if ($start > $params['OFFICE_END'])
             {
                 $extra = $end - $start;	
             }
             
          }
          $retArr['office'] = $office;
          $retArr['extra'] = $extra;
          
          return $retArr;
      }

      function getOfficeAndExtraBreakdown($params, $end, $start)
      {
          
          /*echo 'Work: '.date("h:i a",$start).':';
          echo date("h:i a",$end).':';
          echo $end-$start.' Real: ';*/
          global $WEEKEND;
          
	  $office = $extra = 0;
	  
          
          if (in_array(date('D', $start) , $WEEKEND))
          {
            $office = 0;
            $extra = $end - $start;
          }
          else
          {
             if ($start < $params['OFFICE_START'] && $end < $params['OFFICE_START'])
             {
             	 $extra += ($end - $start);
             }
             if ($start < $params['OFFICE_START'] && $end > $params['OFFICE_START'])   // Extra hours before office hours
             {
                 $extra += ($params['OFFICE_START'] - $start);
                 $start = $params['OFFICE_START'];
             }
             if ($start<$params['OFFICE_END'] && $end > $params['OFFICE_END'])  // Extra hours after office hours
             {
                 $extra += ($end - $params['OFFICE_END']);
                 $end = $params['OFFICE_END'];
             }
             if ($start > $params['LUNCH_START'] && $start < $params['LUNCH_END'])  //removing lunch hour from count
             {
                 $start = $params['LUNCH_END'];
             }
             if ($end > $params['LUNCH_START'] && $end < $params['LUNCH_END'])  //removing lunch hour from count
             {
             	$end = $params['LUNCH_START'];
             }
             
             if ($end > $params['LUNCH_END'] && $start < $params['LUNCH_START'])  //removing lunch hour from count
             {
                 
                 $office = ($end - $start) - ($params['LUNCH_END'] - $params['LUNCH_START']);
             }
             else
             {
                 //If the whole timespan of the work is within lunch hour it won't be counted as office hour, not even extra hour
                 if ($start > $params['LUNCH_START'] && $start < $params['LUNCH_END'] && $end > $params['LUNCH_START'] && $end < $params['LUNCH_END'])
                 {
                    $office = 0;
                 }
                 else if ($start > $params['OFFICE_END'] || $end < $params['OFFICE_START'])
                 {
                    $office = 0;	
                 }
                 else if ($start > $params['LUNCH_START'] && $end < $params['LUNCH_END'])
                 {
                    $office = 0;		
                 }
                 else
                 {  
                    $office = $end - $start;
                 }
             }
             if ($start > $params['OFFICE_END'])
             {
                 $extra = $end - $start;	
             }
             
          }
          
          //echo 'office: '.$office.' Extra '.$extra.'<br>';
          
          $arr = array(
          				'EXTRA'   =>  $extra,
          				'OFFICE'  =>  $office
                      );
          return $arr;
      }
      
      
   }
?>
