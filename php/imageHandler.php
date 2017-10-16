<?php
    /**
    *   This class handels optimising image
    *   files to reduce required bandwidth.
    *
    *   @uses           logger
    *
    *   @category       Filehadling
    *   @package        dataHandlers
    *   @subpackage     imagefile
    *   @version        1.0
    *   @since          1.0
    *   @deprecated     ---
    * */

    //  Require logger class.
    require_once 'logger.php';

    /**
    *
    * */
    class imageHandler {

        /**
        *   @method     Reduces the size of an image file if possible.
        *
        *   @param      string  The path to the image file to compress.
        *   @param      string  The destimation path for the compressed image.
        *   @param      integer 1-100, percent quality compared to oroginal image.
        *
        *   @return     boolean Representation of the success status.
        * */
        function compress ($src, $target, $quality = 85) {
            //  Get mime type of image.
            $fio = finfo_open(FILEINFO_MIME_TYPE);
            $info = finfo_file($fio, $src);
            //  Read the image file.
            if ($info == 'image/jpeg') {
                $image = imagecreatefromjpeg($src);
            } else if ($info == 'image/gif') {
                $image = imagecreatefromgif($src);
            } else if ($info == 'image/png') {
                $image = imagecreatefrompng($src);
            }
            finfo_close($fio);

            //  Confirm the image file was read.
            if ($image) {
                //  Compress and write the read image file to target path.
                imagejpeg($image, $target, $quality);
                //  Evaluate compression
                self::evaluateAndClean($src, $target, $info);
                return true;
            }
            return false;
        }

        /**
        *   @method     Calculates and logs an evaluation of the compression.
        *               As well as cleans out the old file.
        *
        *   @param      string  Path to old file.
        *   @param      string  Path to new file.
        *   @param      string  Mime type of old file.
        * */
        private function evaluateAndClean ($src, $new, $mime) {
            //  read the file sizes.
            $oldSize = filesize($src);
            $newSize = filesize($new);

            //  Calculate and evaluate quota.
            $percent = $newSize / $oldSize;
            $eval;
            if ($percent < 60) {
                $eval = 'Outstanding!';
            } else if ($percent > 61 && $percent < 75) {
                $eval = 'Very good!';
            } else if ($percent > 76 && $percent < 85) {
                $eval = 'Good.';
            } else if ($percent > 86 && $percent < 95) {
                $eval = 'Ok.';
            } else if ($percent > 86 && $percent < 95) {
                $eval = 'Bad.';
            }

            //  Instanse the logger.
            $logger = new logger('imageSocket_evaluationLog');
            //  Write evaluation log.
            $msg = 'Compressed image of type '.$mime.', result; '.$eval.'';
            self::$logger->log($msg);

            //  Delete old file. (Unless the
            //  compression had reverse effect)
            if (!$percent > 100) {
                unlink($src);
            }
        }

    }
?>