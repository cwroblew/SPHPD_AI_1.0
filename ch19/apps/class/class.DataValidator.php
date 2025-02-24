<?php

   class DataValidator {

     function DataValidator()
     {

     }

     function validate($value = null, $type = null, $size = null, $validator = null)   //**** added new parameter to get validator
     {
        // Make the validator method name with validate_(VALIDATOR)
        // Example: validate_name()
        
        $validator_method = 'validate_' . $validator;       //**** used $validator instead of $type
        
        
        // If both size and validate_TYPE() methods return TRUE
        // then the fielddata is valid.
     	return ($this->validate_size($value, $size, $type) &&
     	        $this->$validator_method($value)) ? TRUE : FALSE;

     }

     // Validation object methods

     function validate_size($str, $size, $type)
     {
     	// See if string obays size restrictions specified in $size
        // Size restrctions can be in 4 formats
        // $size = min-max of $type (text=characters, number=numeric)
        // $size = exact_size of $type
        // $size = n1-n2[KB|MB|b] (n[12] = int, KB = 1024Bytes, MB = 1024KB, b = 1 char
        // $size = any (no restrictions)
     	
        
        // If size is set to 'any' return TRUE
        
        //return TRUE if ($size is 'any');
   
        $size = substr($size, 5);     // Removing "size=" string from the given size parameter.
       
        if (!strcmp($size, 'any') )
        {
           return TRUE;
        }
       
        $sizeArr = $this->get_size($size);
                
        if (!strcmp($type,'text'))
        {
           return $this->validate_string_size($str, $sizeArr['max'], $sizeArr['min']);      //****  added return for returning the value of validate_string_size()

        } else if (!strcmp($type,'number')) {

           return $this->validate_number_range($str, $sizeArr['max'], $sizeArr['min']);      //****  added return for returning the value of validate_number_range()
        }
     }


     function get_size($size)
     {  
        $size_array = array();
        // Find size limits
        //if ($size contains /-/)
        if( strrchr($size,'-') )
        {
           
           list($min, $max) = explode('-', $size);
           
           $size_array['min'] = $min;
           $size_array['max'] = $max;
           

           //If KB, MB in the $max part than we also to calucate size
           //if ($max contains /KB/ ){
           
           if(preg_match("/KB/i",$max))
           {
              $size_array['min'] = $min * 1024;
              $size_array['max'] = $max * 1024;              
           }

           if (preg_match("/MB/i", $max)  )
           {
              $size_array['min'] = $min * 1024 * 1024;
              $size_array['max'] = $max * 1024 * 1024;
           }
        
        } else if( is_numeric($size)) { 
           // size is an exact number so max and min are same
           $size_array['max'] = $size;
           $size_array['min'] = $size;
        }
        
        return $size_array;
     }


     function validate_number_range($num = null, $max = null, $min = null)
     {

        // Verify that given number is within the specified numeric range.
        return ($num >= $min && $num <= $max) ? TRUE : FALSE;

     }

     function validate_string_size($str = null, $max = null, $min = null)
     {

        // Verify that given string is within the specified min/max size limits
        
        $len = strlen($str);        

        return ($len >= $min && $len <= $max) ? TRUE : FALSE;
     }


     function validate_name($str = null)
     {
        // if $str consists of [a-zA-Z] only return TRUE else FALSE
        return (preg_match("/[a-zA-Z']/", $str)) ? TRUE : FALSE;     //name might contain '.

     }

     function validate_org_name ($str = null)
     {
        // return ($str consists of [a-zA-Z0-9,.-] ? TRUE : FALSE;
        return (preg_match("/[a-zA-Z0-9,.-]/", $str)) ? TRUE : FALSE;
     }


     function validate_number($num = null)
     {
        return (preg_match('/[0-9]+/', $num))  ? TRUE : FALSE;
     }

     function validate_any_string($str = null)
     {
        return TRUE;
     }

     function validate_email ($str = null)
     {
        //RE taken from example in http://www.php.net/manual/en/function.preg-match.php
        return(preg_match("/^[-_.[:alnum:]]+@((([[:alnum:]]|[[:alnum:]][[:alnum:]-]*[[:alnum:]])\.)+(ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|in|info|int|io|iq|ir|is|it|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i",$str));

     }

     function validate_url ($str = null)
     {
        // return if valid url in the form  protocol//server[:port][/path]
        // Example 1: http://www.evoknow.com OK
        // Example 2: https://www.foobar.com OK
        // Example 3: http://www.evoknow.com:80 OK
        // Example 4: http://www.evoknow.com:8080/foo/bar.php?a=b&c=d&e=f OK
        // Exmaple 5: ftp://www.evooknow.com:21/pub/software  OK

        $schema = array('http', 'https', 'ftp');

        $urlComponents = parse_url($str);

        // if current URL schema is not in our schema list
        if (!in_array($urlComponents['scheme'], $schema))
        {
            return FALSE;
        }

        // We are not checking anything else right now.
        return TRUE;
     }
     
     
     function validate_file_size($size, $fileSize )
     {
        // See if string obays size restrictions specified in $size
        // Size restrctions can be in 4 formats
        // $size = min-max of file
        // $size = exact_size of $type
        // $size = n1-n2[KB|MB|b] (n[12] = int, KB = 1024Bytes, MB = 1024KB, b = 1 char
        // $size = any (no restrictions)


        // If size is set to 'any' return TRUE
        
        //return TRUE if ($size is 'any');
   
        $size = substr($size, 5);     // Removing "size=" string from the given size parameter.
       
        if (!strcmp($size, 'any') )
        {
           return TRUE;
        }

        $sizeArr = $this->get_size($size);
        
        return ($fileSize >= $sizeArr['min'] && $fileSize <= $sizeArr['max']) ? TRUE : FALSE;  
     }


   }//class


?>
