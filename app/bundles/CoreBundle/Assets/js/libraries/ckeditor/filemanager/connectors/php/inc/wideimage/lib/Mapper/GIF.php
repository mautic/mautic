<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * Mapper class for GIF files.
     */
    class WideImage_Mapper_GIF
    {
        public function load($uri)
        {
            return @imagecreatefromgif($uri);
        }

        public function save($handle, $uri = null)
        {
            // This is a workaround for a bug, for which PHP devs claim it's not
            // really a bug. Well, it IS.
            // You can't pass null as the second parameter, because php is
            // then trying to save an image to a '' location (which results in an
            // error, of course). And the same thing works fine for imagepng() and
            // imagejpeg(). It's a bug! ;)
            if ($uri) {
                return imagegif($handle, $uri);
            } else {
                return imagegif($handle);
            }
        }
    }
