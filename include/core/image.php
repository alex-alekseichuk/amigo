<?php

class Image {

    var $_fileName = null;
    var $_img      = null;
    var $_original = null;
    var $_type     = null;

    function load($img)
    {
        $this->free();

        if (is_string($img)) {
            // guess this is file name
            if (!is_file($img)) {
                //trace("Image: Not a file resourse");
                return false;
            }
            $fileName = $img;
            $img      = false;
            $imagetypes = imagetypes();
            $type = $this->getImageType($fileName);
            switch ($type) {
                case 1  : // gif
                    if ($imagetypes & IMG_GIF) {
                        $img = imagecreatefromgif($fileName);
                    }
                    break;
                case 2  : // jpeg
                    if ($imagetypes & IMG_JPG) {
                        $img = imagecreatefromjpeg($fileName);
                    }
                    break;
                case 3  : // png
                    if ($imagetypes & IMG_PNG) {
                        $img = imagecreatefrompng($fileName);
                    }
                    break;
                case 15 : // wbmp
                    if ($imagetypes & IMG_WBMP) {
                        $img = imagecreatefromwbmp($fileName);
                    }
                    break;
                default :
                    break;
            }
            if ($img) {
                $this->_type     = $type;
                $this->_fileName = $fileName;
            }
        }

        if ('gd' != @get_resource_type($img)) {
            //trace("Image: [".$fileName."] is not a GD resourse");
            return false;
        }

        $this->_original = $img;
        $this->_img      = $img;

        return true;
    }

    /**
     * Return true if image is loaded
     *
     * @return bool
     */
    function hasImage()
    {
        return is_resource($this->_original);
    }

    /**
     * Get image type for file
     *
     * @static
     * @param  string $fileName
     * @return int
     */
    function getImageType($fileName)
    {
        $type = false;
        if (!is_file($fileName)) {
            return false;
        }
        if (function_exists('exif_imagetype')) {
            $type = exif_imagetype($fileName);
        } else {
            list(,,$type) = getimagesize($fileName);
        }
        return $type;
    }

    function getExtensionByType($image_type)
    {
        static $t = array(
             1 => 'gif',
             2 => 'jpg',
             3 => 'png',
            15 => 'wbmp',
        );
        return $t[$image_type];
    }

    function revert()
    {
        if ($this->_img != $this->_original) {
            imagedestroy($this->_img);
            $this->_img = $this->_original;
        }
    }

    function free()
    {
        @imagedestroy($this->_img);
        @imagedestroy($this->_original);

        $this->_fileName = null;
        $this->_img      = null;
        $this->_type     = null;
    }

    function save($fileName = null, $type = null, $addExtension = false, $attr = null)
    {
        if (!strlen($fileName)) {
            if (strlen($this->_fileName)) {
                $fileName = $this->_fileName;
            } else {
                //trace("Image: output file name is not specified");
                return false;
            }
        }
        if (!$type) {
            if ($this->_type) {
                $type = $this->_type;
            } else {
                //trace("Image: image type is not specified");
                return false;
            }
        }

        if ($addExtension) {
            $path = pathinfo($fileName);
            $fileName = $path['dirname'] . DIRECTORY_SEPARATOR . $path['basename'] . '.' . $this->getExtensionByType($type);
        }

        if (!is_array($attr)) {
            $attr = array();
        }

        $imagetypes = imagetypes();
        $result = false;
        switch ($type) {
            case 1  : // gif
                if ($imagetypes & IMG_GIF) {
                    $result = imagegif($this->_img, $fileName);
                }
                break;
            case 2  : // jpeg
                if ($imagetypes & IMG_JPG) {
                    $q = 85;
                    if (isset($attr['quality']))
                        $q = (int)$attr['quality'];
                    if ($q < 1 || $q > 100) {
                        $q = 85;
                    }
                    $result = imagejpeg($this->_img, $fileName, $q);
                }
                break;
            case 3  : // png
                if ($imagetypes & IMG_PNG) {
                    $result = imagepng($this->_img, $fileName);
                }
                break;
            case 15 : // wbmp
                if ($imagetypes & IMG_WBMP) {
                    $result = imagewbmp($this->_img, $fileName);
                }
                break;
            default :
                return false;
                break;
        }
        if ($result) {
            $umask = umask();
            chmod($fileName, 0666);
            umask($umask);
        }
        return $result;
    }

    function send($type = null, $attr = null)
    {
        if (!$type) {
            if ($this->_type) {
                $type = $this->_type;
            } else {
                trigger_error("Image: image type is not specified", E_USER_ERROR);
                return false;
            }
        }

        $mime_type = image_type_to_mime_type($type);

        if (!strlen($mime_type)) {
            return false;
        }

        if (!is_array($attr)) {
            $attr = array();
        }

        $imagetypes = imagetypes();

        $header = "Content-type: " . $mime_type;
        switch ($type) {
            case 1  : // gif
                if ($imagetypes & IMG_GIF) {
                    header($header);
                    return imagegif($this->_img);
                }
                break;
            case 2  : // jpeg
                if ($imagetypes & IMG_JPG) {
                    header($header);
                    $q = (int)$attr['quality'];
                    if ($q < 1 || $q > 100) {
                        $q = 85;
                    }
                    return imagejpeg($this->_img, null, $q);
                }
                break;
            case 3  : // png
                if ($imagetypes & IMG_PNG) {
                    header($header);
                    return imagepng($this->_img);
                }
                break;
            case 15 : // wbmp
                if ($imagetypes & IMG_WBMP) {
                    header($header);
                    return imagewbmp($this->_img);
                }
                break;
            default :
                return false;
                break;
        }
    }

    // ***********************************************************************
    // Image methods:
    // -----------------------------------------------------------------------

    function getGDImage()
    {
        return $this->_img;
    }

    function getSize()
    {
        return array(imagesx($this->_img), imagesy($this->_img));
    }

    function checkSize($maxWidth = null, $maxHeight = null, $minWidth = null, $minHeight = null)
    {
        list($w,$h) = $this->getSize();
        if (
               $maxWidth > 0 && $w > $maxWidth
            || $maxHeight > 0 && $h > $maxHeight
            || $minWidth > 0 && $w < $mixWidth
            || $minHeight > 0 && $h < $minHeight
        ) {
            return false;
        }
        return true;
    }

    function crop($width, $height, $method = 5)
    {
        list($w,$h) = $this->getSize();

        if ($w <= $width && $h <= $height) {
            return false;
        }

        if ($w > $width) {
            switch ($method) {
                case 1 :
                case 4 :
                case 7 :
                   $sx = 0;
                   break;
                case 2 :
                case 5 :
                case 8 :
                   $sx = ceil(($w - $width) / 2);
                   break;
                case 3 :
                case 6 :
                case 9 :
                   $sx = $w - $width;
                   break;
                default:
                   return false;
            }
        } else {
            $sx    = 0;
            $width = $w;
        }

        if ($h > $height) {
            switch ($method) {
                case 1 :
                case 2 :
                case 3 :
                   $sy = 0;
                   break;
                case 4 :
                case 5 :
                case 6 :
                   $sy = ceil(($h - $height) / 2);
                   break;
                case 7 :
                case 8 :
                case 9 :
                   $sy = $h - $height;
                   break;
                default:
                   return false;
            }
        } else {
           $sy     = 0;
           $height = $h;
        }

        $res = imagecreatetruecolor($width,$height);
        imagecopy($res,$this->_img,0,0,$sx,$sy,$width,$height);

        if ($this->_img != $this->_original) {
            imagedestroy($this->_img);
        }
        $this->_img = $res;
    }

    // 1 - scale in bounds, 2 - scale out bounds, 3 - resize
    function resample($width, $height, $method = 1)
    {
        list($w,$h) = $this->getSize();
        $ow = $w;
        $oh = $h;
        if ($method == 1 || $method == 2) {
            $k = imagesx($this->_img) / imagesy($this->_img);

            if($width > 0 && $w > $width) {
                $w = $width;
                $h = round($w / $k);
            }

            if($height > 0 && $h > $height) {
                $h = $height;
                $w = round($h * $k);
            }

            if($method == 2 && $w < $width) {
                $w = $width;
                $h = round($w / $k);
            }

            if($method == 2 && $h < $height) {
                $h = $height;
                $w = round($h * $k);
            }

        } elseif ($method == 3) {
            $ow = $width;
            $oh = $height;
        }

        if ($w != $ow || $h != $oh) {
            $res = imagecreatetruecolor($w,$h);
            imagecopyresampled($res,$this->_img,0,0,0,0,$w,$h,$ow,$oh);

            if ($this->_img != $this->_original) {
                imagedestroy($this->_img);
            }
            $this->_img = $res;
        }
    }

    function watermark($fileName, $pos)
    {
        if (is_string($fileName) && is_file($fileName)) {
            $watermark = imagecreatefrompng($fileName);
        } elseif ('gd' == @get_resource_type($fileName)) {
            $watermark = $fileName;
        } else {
            return false;
        }
        $width = imagesx($this->_img);
        $height = imagesy($this->_img);
        $wm_width = imagesx($watermark);
        $wm_height = imagesy($watermark);

        switch ($pos) {
            case 1 :
            case 4 :
            case 7 :
                $x = 0;
                break;
            case 2 :
            case 5 :
            case 8 :
                $x = floor(($width - $wm_width) / 2);
                break;
            case 3 :
            case 6 :
            case 9 :
                $x = $width - $wm_width;
                break;
        }

        switch ($pos) {
            case 1 :
            case 2 :
            case 3 :
                $y = 0;
                break;
            case 4 :
            case 5 :
            case 6 :
                $y = floor(($height - $wm_height) / 2);
                break;
            case 7 :
            case 8 :
            case 9 :
                $y = $height - $wm_height;
                break;
        }

        $res = imagecreatetruecolor($width, $height);
        imagecopy($res, $this->_img,0,0,0,0,$width,$height);
        imagecopy($res, $watermark, $x, $y, 0, 0, $wm_width, $wm_height);
        if (is_string($fileName)) {
            imagedestroy($watermark);
        }
        if ($this->_img != $this->_original) {
            imagedestroy($this->_img);
        }
        $this->_img = $res;
        return true;
    }

}

?>