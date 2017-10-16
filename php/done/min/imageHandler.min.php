<?php
    require_once 'logger.php';

    class imageHandler {

        function compress ($src, $target, $quality = 85) {
            $fio = finfo_open(FILEINFO_MIME_TYPE);
            $info = finfo_file($fio, $src);
            if ($info == 'image/jpeg')
            { $image = imagecreatefromjpeg($src); }
            else if ($info == 'image/gif')
            { $image = imagecreatefromgif($src); }
            else if ($info == 'image/png')
            { $image = imagecreatefrompng($src); }
            finfo_close($fio);
            if ($image) {
                imagejpeg($image, $target, $quality);
                self::evaluateAndClean($src, $target, $info);
                return true;
            }
            return false;
        }

        private function evaluateAndClean ($src, $new, $mime) {
            $oldSize = filesize($src);
            $newSize = filesize($new);
            $percent = $newSize / $oldSize;
            $eval;
            if ($percent < 60)
            { $eval = 'Outstanding!'; }
            else if ($percent > 61 && $percent < 75)
            { $eval = 'Very good!'; }
            else if ($percent > 76 && $percent < 85)
            { $eval = 'Good.'; }
            else if ($percent > 86 && $percent < 95)
            { $eval = 'Ok.'; }
            else if ($percent > 86 && $percent < 95)
            { $eval = 'Bad.'; }
            $logger = new logger('imageSocket_evaluationLog');
            $msg = 'Compressed image of type '.$mime.', result; '.$eval.'';
            self::$logger->log($msg);
            if (!$percent > 100)
            { unlink($src); }
        }

    }
?>