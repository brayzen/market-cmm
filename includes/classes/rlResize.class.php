<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLRESIZE.CLASS.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2019 | All copyrights reserved.
 *  
 *  http://www.flynax.com/
 ******************************************************************************/

use Flynax\Classes\ListingPictureUpload;
use Flynax\Utils\ListingMedia;

class rlResize
{
    public $strOriginalImagePath;
    public $strResizedImagePath;
    public $arrOriginalDetails;
    public $arrResizedDetails;
    public $resOriginalImage;
    public $resResizedImage;
    public $boolProtect = true;

    /**
     * @var $gdVersion - gd version
     **/
    public $gdVersion;

    /**
     * @var $returnRes - return resu;t
     **/
    public $returnRes = false;

    /**
     * @var $driftX - displacement watermark by x
     **/
    public $driftX = 5;

    /**
     * @var $driftY - displacement watermark by y
     **/
    public $driftY = 5;

    /**
     * @var $watermark - allow watermark with resize oteration
     **/
    public $rlWatermark;

    /**
     * Change file name when pictures refreshed
     * @var   boolean
     * @since 4.6.0
     */
    public $refreshChangeFileName = false;

    public function __construct()
    {
        $_gd_info = gd_info();
        if (!$_gd_info) {
            return false;
        }

        preg_match('/(\d)\.(\d)/', $_gd_info['GD Version'], $_match);

        $this->gdVersion = $_match[1];
    }

    /*
     *
     *   @Method:        rlResize
     *   @Parameters:    5
     *   @Param-1:       strPath - String - The path to the image
     *   @Param-2:       strSavePath - String - The path to save the new image to
     *   @Param-3:       strType - String - The type of resize you want to perform
     *   @Param-4:       value - Number/Array - The resize dimensions
     *   @Param-5:       boolProect - Boolen - Protects the image so that it doesnt resize an image if its already smaller
     *   @Description:   Calls the RVJ_Pagination method so its php 4 compatible
     *
     */
    public function resize($strPath, $strSavePath, $strType = 'W', $value = '150', $boolProtect = true, $watermark = true)
    {
        //save the image/path details
        $this->strOriginalImagePath = $strPath;
        $this->strResizedImagePath = $strSavePath;
        $this->boolProtect = $boolProtect;
        $this->rlWatermark = $watermark;

        //get the image dimensions
        $this->arrOriginalDetails = getimagesize($this->strOriginalImagePath);
        $this->arrResizedDetails = $this->arrOriginalDetails;

        //create an image resouce to work with
        $this->resOriginalImage = $this->createImage($this->strOriginalImagePath);

        //select the image resize type
        switch (strtoupper($strType)) {
            case 'P':
                $this->resizeToPercent($value);
                break;
            case 'H':
                $this->resizeToHeight($value);
                break;
            case 'C':
                $this->resizeToCustom($value);
                break;
            case 'W':
            default:
                $this->resizeToWidth($value);
                break;
        }
    }

    /*
     *
     *   @Method:        findResourceDetails
     *   @Parameters:    1
     *   @Param-1:       resImage - Resource - The image resource you want details on
     *   @Description:   Returns an array of details about the resource identifier that you pass it
     *
     */
    public function findResourceDetails($resImage)
    {
        //check to see what image is being requested
        if ($resImage == $this->resResizedImage) {
            //return new image details
            return $this->arrResizedDetails;
        } else {
            //return original image details
            return $this->arrOriginalDetails;
        }
    }

    /*
     *
     *   @Method:        updateNewDetails
     *   @Parameters:    0
     *   @Description:   Updates the width and height values of the resized details array
     *
     */
    public function updateNewDetails()
    {
        $this->arrResizedDetails[0] = imagesx($this->resResizedImage);
        $this->arrResizedDetails[1] = imagesy($this->resResizedImage);
    }

    /*
     *
     *   @Method:        createImage
     *   @Parameters:    1
     *   @Param-1:       strImagePath - String - The path to the image
     *   @Description:   Created an image resource of the image path passed to it
     *
     */
    public function createImage($strImagePath)
    {
        //get the image details
        $arrDetails = $this->findResourceDetails($strImagePath);

        //choose the correct function for the image type
        switch ($arrDetails['mime']) {
            case 'image/jpeg':
                return imagecreatefromjpeg($strImagePath);
                break;
            case 'image/png':
                return imagecreatefrompng($strImagePath);
                break;
            case 'image/gif':
                return imagecreatefromgif($strImagePath);
                break;
        }
    }

    /*
     *
     *   @Method:        saveImage
     *   @Parameters:    1
     *   @Param-1:       numQuality - Number - The quality to save the image at
     *   @Description:   Saves the resize image
     *
     */
    public function saveImage()
    {
        global $config;

        $numQuality = $config['img_quality'];

        // watermark action
        if ($config['watermark_using'] && $this->rlWatermark) {
            if ($config['watermark_type'] == 'image') {
                $w_source = $config['watermark_image_url'];
                $watermark = imagecreatefrompng($w_source);
                imagealphablending($watermark, false);
                imagesavealpha($watermark, true);

                if ($watermark) {
                    $watermark_width = imagesx($watermark);
                    $watermark_height = imagesy($watermark);

                    $dest_x = $this->arrResizedDetails[0] - $watermark_width - $this->driftX;
                    $dest_y = $this->arrResizedDetails[1] - $watermark_height - $this->driftY;

                    // creating a cut resource
                    $cut = imagecreatetruecolor($watermark_width, $watermark_height);

                    // copying relevant section from background to the cut resource
                    imagecopy($cut, $this->resResizedImage, 0, 0, $dest_x, $dest_y, $watermark_width, $watermark_height);

                    // copying relevant section from watermark to the cut resource
                    imagecopy($cut, $watermark, 0, 0, 0, 0, $watermark_width, $watermark_height);

                    // insert cut resource to destination image
                    imagecopymerge($this->resResizedImage, $cut, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height, 100);

                    // clear memory
                    imagedestroy($watermark);
                }
            } else {
                $w_text = $config['watermark_text'];

                if (empty($w_text)) {
                    $w_text = $GLOBALS['rlValid']->getDomain(RL_URL_HOME);
                }

                $w_blank = round(strlen($w_text) * 10);

                $watermark = imagecreatetruecolor($w_blank, 19);
                $bgc = imagecolortransparent($watermark, 0);
                $tc = imagecolorallocate($watermark, 255, 255, 255);
                imagefilledrectangle($watermark, 0, 0, $w_blank, 18, $bgc);

                imagestring($watermark, 5, 5, 4, $w_text, $tc);

                $watermark_width = imagesx($watermark);
                $watermark_height = imagesy($watermark);

                $dest_x = $this->arrResizedDetails[0] - $watermark_width - $this->driftX;
                $dest_y = $this->arrResizedDetails[1] - $watermark_height - $this->driftY;

                imagecopymerge($this->resResizedImage, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height, 100);

                /* clear memory */
                imagedestroy($watermark);
                imagedestroy($bgc);
                imagedestroy($tc);
            }
        }

        switch ($this->arrResizedDetails['mime']) {
            case 'image/jpeg':
                $this->returnRes = imagejpeg($this->resResizedImage, $this->strResizedImagePath, $numQuality);
                break;
            case 'image/png':
                $this->returnRes = imagepng($this->resResizedImage, $this->strResizedImagePath);
                break;
            case 'image/gif':
                $this->returnRes = imagegif($this->resResizedImage, $this->strResizedImagePath);
                break;
        }
    }

    /*
     *
     *   @Method:        showImage
     *   @Parameters:    1
     *   @Param-1:       resImage - Resource - The resource of the image you want to display
     *   @Description:   Displays the image resouce on the screen
     *
     */
    public function showImage($resImage)
    {
        //get the image details
        $arrDetails = $this->findResourceDetails($resImage);

        //set the correct header for the image we are displaying
        header("Content-type: " . $arrDetails['mime']);

        switch ($arrDetails['mime']) {
            case 'image/jpeg':
                return imagejpeg($resImage);
                break;
            case 'image/png':
                return imagepng($resImage);
                break;
            case 'image/gif':
                return imagegif($resImage);
                break;
        }
    }

    /*
     *
     *   @Method:        destroyImage
     *   @Parameters:    1
     *   @Param-1:       resImage - Resource - The image resource you want to destroy
     *   @Description:   Destroys the image resource and so cleans things up
     *
     */
    public function destroyImage()
    {
        imagedestroy($this->resResizedImage);
        imagedestroy($this->resOriginalImage);

        unset($this->resResizedImage);
        unset($this->strResizedImagePath);
        unset($this->resOriginalImage);
        unset($this->strOriginalImagePath);
    }

    /*
     *
     *   @Method:        _resize
     *   @Parameters:    2
     *   @Param-1:       numWidth - Number - The width of the image in pixels
     *   @Param-2:       numHeight - Number - The height of the image in pixes
     *   @Description:   Resizes the image by creatin a new canvas and copying the image over onto it. DONT CALL THIS METHOD DIRECTLY - USE THE METHODS BELOW
     *
     */
    public function _resize($numWidth, $numHeight)
    {
        global $config;

        switch ($this->arrOriginalDetails['mime']) {
            case 'image/gif':
                //GIF image
                $this->resResizedImage = imagecreate($numWidth, $numHeight);
                break;

            case 'image/png':
                //PNG image
                $this->resResizedImage = imagecreatetruecolor($numWidth, $numHeight);
                imagealphablending($this->resResizedImage, false);
                imagesavealpha($this->resResizedImage, true);
                break;

            default:
                //JPG image
                $this->resResizedImage = imagecreatetruecolor($numWidth, $numHeight);

                break;
        }

        //update the image size details
        $this->updateNewDetails();

        $resize_method = function_exists('imagecopyresampled') ? 'imagecopyresampled' : 'imagecopyresized';
        $resize_method($this->resResizedImage, $this->resOriginalImage, 0, 0, 0, 0, $numWidth, $numHeight, $this->arrOriginalDetails[0], $this->arrOriginalDetails[1]);

        $this->saveImage();

        $this->destroyImage();
    }

    /*
     *
     *   @Method:        _imageProtect
     *   @Parameters:    2
     *   @Param-1:       numWidth - Number - The width of the image in pixels
     *   @Param-2:       numHeight - Number - The height of the image in pixes
     *   @Description:   Checks to see if we should allow the resize to take place or not depending on the size the image will be resized to
     *
     */
    public function _imageProtect($numWidth, $numHeight)
    {
        if ($this->boolProtect and ($numWidth > $this->arrOriginalDetails[0] or $numHeight > $this->arrOriginalDetails[1])) {
            return 0;
        }

        return 1;
    }

    /*
     *
     *   @Method:        resizeToWidth
     *   @Parameters:    1
     *   @Param-1:       numWidth - Number - The width to resize to in pixels
     *   @Description:   Works out the height value to go with the width value passed, then calls the resize method.
     *
     */
    public function resizeToWidth($numWidth)
    {
        $numHeight = (int) (($numWidth * $this->arrOriginalDetails[1]) / $this->arrOriginalDetails[0]);
        $this->_resize($numWidth, $numHeight);
    }

    /*
     *
     *   @Method:        resizeToHeight
     *   @Parameters:    1
     *   @Param-1:       numHeight - Number - The height to resize to in pixels
     *   @Description:   Works out the width value to go with the height value passed, then calls the resize method.
     *
     */
    public function resizeToHeight($numHeight)
    {
        $numWidth = (int) (($numHeight * $this->arrOriginalDetails[0]) / $this->arrOriginalDetails[1]);
        $this->_resize($numWidth, $numHeight);
    }

    /*
     *
     *   @Method:        resizeToPercent
     *   @Parameters:    1
     *   @Param-1:       numPercent - Number - The percentage you want to resize to
     *   @Description:   Works out the width and height value to go with the percent value passed, then calls the resize method.
     *
     */
    public function resizeToPercent($numPercent)
    {
        $numWidth = (int) (($this->arrOriginalDetails[0] / 100) * $numPercent);
        $numHeight = (int) (($this->arrOriginalDetails[1] / 100) * $numPercent);
        $this->_resize($numWidth, $numHeight);
    }

    /*
     *
     *   @Method:        resizeToCustom
     *   @Parameters:    1
     *   @Param-1:       size - Number/Array - Either a number of array of numbers for the width and height in pixels
     *   @Description:   Checks to see if array was passed and calls the resize method with the correct values.
     *
     */
    public function resizeToCustom($size)
    {
        if (is_array($size)) {
            // current image params
            $_photo_width = $this->arrOriginalDetails[0];
            $_photo_height = $this->arrOriginalDetails[1];

            // new image params
            $img_width = (int) $size[0];
            $img_height = (int) $size[1];

            //$this->_resize($img_width, $img_height);

            // the following code does not creates white BG, the code should be set instead of 3 code iines above
            if (($_photo_width > $img_width && $img_width) || ($_photo_height > $img_height && $img_height)) {
                if ($_photo_width > $_photo_height) {
                    $_resized_photo_width = $img_width;
                    $_percent = round(100 * $img_width / $_photo_width);
                    $_resized_photo_height = round($_percent * $_photo_height / 100);
                } elseif ($_photo_width < $_photo_height) {
                    $_resized_photo_height = $img_height;
                    $_percent = round(100 * $img_height / $_photo_height);
                    $_resized_photo_width = round($_percent * $_photo_width / 100);
                } else {
                    if ($img_width > $img_height) {
                        $_resized_photo_width = $img_width;
                        $_percent = round(100 * $img_width / $_photo_width);
                        $_resized_photo_height = round($_percent * $_photo_height / 100);
                    } else {
                        $_resized_photo_height = $img_height;
                        $_percent = round(100 * $img_height / $_photo_height);
                        $_resized_photo_width = round($_percent * $_photo_width / 100);
                    }
                }
            } else {
                $_resized_photo_width = $_photo_width;
                $_resized_photo_height = $_photo_height;
            }

            $this->_resize($_resized_photo_width, $_resized_photo_height);
        } else {
            $this->resizeToWidth($size);
        }
    }

    /**
     * Refresh image
     *
     * @deprecated 4.7.1 - Use ListingMedia::updatePicture()
     *
     * @param  string  $file_name         - Name of file to proceed
     * @param  string  $mode              - File type (thumbnail, thumbnail_x2 or large)
     * @param  string  $mt                - random number used to generate file name
     * @param  string  $original          - Name of original image
     * @param  boolean $disable_watermark - Disable watermark
     * @param  int     $listing_id        - ID of listing
     * @return boolean
     */
    public function refreshImage($file_name, $mode = '', $mt = '', $original = '', $disable_watermark = false, $listing_id = 0)
    {}
}
