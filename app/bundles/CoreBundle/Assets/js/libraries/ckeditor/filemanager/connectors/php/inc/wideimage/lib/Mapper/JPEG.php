<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * Mapper class for JPEG files.
     */
    class WideImage_Mapper_JPEG
    {
        public function load($uri)
        {
            return @imagecreatefromjpeg($uri);
        }

        public function save($handle, $uri = null, $quality = 100)
        {
            return imagejpeg($handle, $uri, $quality);
        }
    }
