<?php

include_once(AMIGO_CORE_PATH . "/core.php");

// parse command line arguments into hash
// $hash = arguments($argv);
// print print_r(arguments($argv));
function arguments($argv)
{
	$_ARG = array();
	foreach ($argv as $arg) {
		if (ereg('--[a-zA-Z0-9]*=.*',$arg))
		{
			$str = split("=",$arg); $arg = '';
			$key = ereg_replace("--",'',$str[0]);
			for ( $i = 1; $i < count($str); $i++ )
			{
				$arg .= $str[$i];
			}
			$_ARG[$key] = $arg;
		}
		elseif(ereg('-[a-zA-Z0-9]',$arg))
		{
			$arg = ereg_replace("-",'',$arg);
			$_ARG[$arg] = 'true';
		}
	}
	return $_ARG;
}

// just to debug some value
function trace($s)
{
	echo "\n" . $s . "\n";
}


?>