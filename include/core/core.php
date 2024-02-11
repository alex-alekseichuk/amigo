<?php

$PHPVersion = explode(".",  phpversion());
if (($PHPVersion[0] < 5)) {
    echo "Sorry. This program requires PHP 5 and above to run. You may upgrade your php at http://www.php.net/downloads.php";
    exit;
}


// just parse some string template like "...{param}..." with specified hash like param=>value
function parseHash($str, $hash)
{
	$s = $str;
	foreach ($hash as $name => $value)
	{
		$s = str_replace("{" . $name . "}", $value, $s);
	}
	return $s;
}



// send email by regualr php method
// $to - to address
// $from - from address
// $subject
// $message
// $type - content-type of the body (text/html or text/plain); text/plain used by default
// $bHiPriority - true - add hi-priority flag
function send_email($to, $from, $fromName, $subject, $message, $type = "text/html", $bHiPriority = false)
{
	$headers = "";
	$headers .= "From: " . $from . "\n";
	if ($fromName != "")
		$headers .= "X-From: " . $fromName . " <" . $from . ">" . "\n";
	if ($type == "")
		$type = "text/plain";
	$headers .= "Content-type: " . $type . "\n";
	if ($bHiPriority)
		$headers .= "X-Priority: 2 (High)\n";

	$aTo = explode(";", $to);

	foreach ($aTo as $e)
	{
		@mail($e, $subject, $message, $headers);
	}
}



function to_anum($n)
{
	return $n;
//	$s = (string)$n;
//	$s2 = "";
//	while (strlen($s) > 0)
//	{
//		if ($s2 != "")
//			$s2 = "," . $s2;
//		if (strlen($s) > 3)
//		{
//			$s2 = substr($s, strlen($s)-3) . $s2;
//			$s = substr($s, 0, strlen($s)-3);
//		} else  {
//			$s2 = $s . $s2;
//			$s = "";
//		}
//	}
//	return $s2;
}


function recursive_remove_directory($directory, $empty=FALSE)
{
     // if the path has a slash at the end we remove it here
     if(substr($directory,-1) == '/')
     {
         $directory = substr($directory,0,-1);
     }
  
     // if the path is not valid or is not a directory ...
     if(!file_exists($directory) || !is_dir($directory))
     {
         // ... we return false and exit the function
         return FALSE;
  
     // ... if the path is not readable
     }elseif(!is_readable($directory))
     {
         // ... we return false and exit the function
         return FALSE;
  
     // ... else if the path is readable
     }else{
  
         // we open the directory
         $handle = opendir($directory);
  
         // and scan through the items inside
         while (FALSE !== ($item = readdir($handle)))
         {
             // if the filepointer is not the current directory
             // or the parent directory
             if($item != '.' && $item != '..')
             {
                 // we build the new path to delete
                 $path = $directory.'/'.$item;
  
                 // if the new path is a directory
                 if(is_dir($path)) 
                 {
                     // we call this function with the new path
                     recursive_remove_directory($path);
  
                 // if the new path is a file
                 }else{
                     // we remove the file
                     unlink($path);
                 }
             }
         }
         // close the directory
         closedir($handle);
  
         // if the option to empty is not set to true
         if($empty == FALSE)
         {
             // try to delete the now empty directory
             if(!rmdir($directory))
             {
                 // return false if not possible
                 return FALSE;
             }
         }
         // return success
         return TRUE;
     }
}

function parsePath($path, $slash = '/')
{
	$a = explode($slash, $path);
	$a2 = Array();
	foreach($a as $s)
	{
		if ($s !== "")
		{
			$a2[] = $s;
		}
	}
	return $a2;
}

function getRandomName()
{
	return getmypid() . "-" . substr(md5(uniqid(rand())), 0, 10);
}


?>