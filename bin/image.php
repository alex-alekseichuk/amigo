<?php

define("AMIGO_CORE_PATH", "../include/core");

include AMIGO_CORE_PATH . "/core_cmd.php";
include AMIGO_CORE_PATH . "/core/image.php";


$img = new Image();

if ($img->load("../img/1.jpg"))
{
    
    $img->save("../img/2.jpg");

    $img->free();
}



