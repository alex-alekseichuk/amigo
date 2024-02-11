<?php

include_once(AMIGO_CORE_PATH . "/core.php");

// common methods

function to_html($Value)
{
	return nl2br(htmlspecialchars($Value));
}
function to_url($Value)
{
	return urlencode($Value);
}
function get_session($parameter_name)
{
    return isset($_SESSION[$parameter_name]) ? $_SESSION[$parameter_name] : "";
}
function set_session($param_name, $param_value)
{
    $_SESSION[$param_name] = $param_value;
}
function get_cookie($parameter_name)
{
    return isset($_COOKIE[$parameter_name]) ? $_COOKIE[$parameter_name] : "";
}
function set_cookie($parameter_name, $param_value, $expired = -1)
{
  if ($expired == -1)
    $expired = time() + 3600 * 24 * 366;
  elseif ($expired && $expired < time())
    $expired = time() + $expired;
  setcookie ($parameter_name, $param_value, $expired);  
}

// used in get_param()
function strip($value)
{
  if(get_magic_quotes_gpc() != 0)
  {
    if(is_array($value))  
      foreach($value as $key=>$val)
        $value[$key] = stripslashes($val);
    else
      $value = stripslashes($value);
  }
  return $value;
}

// get param from HTTP
function get_param($parameter_name, $default_value = "")
{
    $parameter_value = "";
    if(isset($_POST[$parameter_name]))
        $parameter_value = strip($_POST[$parameter_name]);
    else if(isset($_GET[$parameter_name]))
        $parameter_value = strip($_GET[$parameter_name]);
    else
        $parameter_value = $default_value;
    return $parameter_value;
}

// returns the array of HTTP values of the same $parameter_name paramter
// TODO do we need this function?
function get_param_array($parameter_name)
{
	$arr = Array();
	
    if(isset($_POST[$parameter_name]))
	{
		if (is_array($_POST[$parameter_name]))
		{
			$arr = $_POST[$parameter_name];
		}
		else
		{
			$arr = Array($_POST[$parameter_name]);
		}
	}
    else if(isset($_GET[$parameter_name]))
	{
		if (is_array($_GET[$parameter_name]))
		{
			$arr = $_GET[$parameter_name];
		}
		else
		{
			$arr = Array($_GET[$parameter_name]);
		}
	}
    return $arr;
}


// get from HTTP bit mask for by several values of $name parameter
function get_checks_param($name)
{
	$arr = get_param_array($name);
	$v = 0;
	foreach ($arr as $param)
	{
		$v |= (1 << ($param - 1));
	}
	return $v;
}




function HSelectOptions(&$hash, $value)
{
	$opts = "";
	foreach ($hash as $v => $title)
		$opts .= "<option value=\"" . $v . "\"" . (($v == $value) ? " selected" : "") . ">" . $title . "</options>\n";
	return $opts;
}

function DSelectOptions($storage, $value)
{
	$opts = "";
	foreach ($storage->getItems() as $item)
		$opts .= "<option value=\"" . $item["value"] . "\"" . (($item["value"] == $value) ? " selected" : "") . ">" . $item["title"] . "</options>\n";
	return $opts;
}

function NSelectOptions($min, $max, $value)
{
	$opts = "";
	for ($i = $min; $i <= $max; $i++)
		$opts .= "<option value=\"" . $i . "\"" . (($i == $value) ? " selected" : "") . ">" . $i . "</options>\n";
	return $opts;
}


// just to debug some value
function trace($s)
{
	echo "<hr>" . $s . "<hr>\n";
}


function redirect($url)
{
	header("Location: " . $url . "\n");
	exit;			
}

function curPageURL()
{
 $pageURL = 'http';
 if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

