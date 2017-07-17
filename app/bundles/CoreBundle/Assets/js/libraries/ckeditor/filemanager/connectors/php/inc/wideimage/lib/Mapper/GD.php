<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * Mapper class for GD files.
     */
    class WideImage_Mapper_GD
    {
        public function load($uri)
        {
            return @imagecreatefromgd($uri);
        }

        public function save($handle, $uri = null)
        {
            if ($uri == null) {
                return imagegd($handle);
            } else {
                return imagegd($handle, $uri);
            }
        }
    }
