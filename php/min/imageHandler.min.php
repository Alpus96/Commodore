<?php

    require_once 'Logger.php';

    class ImageHandler {

        function compress ($src, $target, $quality = 75) {
            if (!file_exists($src) || $src === $target || $quality > 100 || $quality < 1)
            { return false; }
            $fio = finfo_open(FILEINFO_MIME_TYPE);
            $info = finfo_file($fio, $src);
            if ($info == 'image/jpeg') { $image = imagecreatefromjpeg($src); }
            else if ($info == 'image/gif') { $image = imagecreatefromgif($src); }
            else if ($info == 'image/png') { $image = imagecreatefrompng($src); }
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
            $percent = round(($newSize/$oldSize)*100, 1);
            $eval;
            if ($percent < 60) { $eval = 'Outstanding!'; }
            else if ($percent > 61 && $percent < 75) { $eval = 'Good!'; }
            else if ($percent > 76 && $percent < 85) { $eval = 'Ok.'; }
            else if ($percent > 86 && $percent < 95) { $eval = 'Meh.'; }
            else if ($percent > 96 && $percent <= 100) { $eval = 'Bad.'; }
            else if ($percent > 100) { $eval = 'Awful!'; }
            $logger = new Logger('imageSocket_evaluationLog');
            $msg = 'Compressed image ('.$mime.'), result; '.$eval.'('.$percent.'% of original)';
            $logger->log($msg);
            if ($percent < 100) { unlink($src); }
        }

    }
?>