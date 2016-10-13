<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * Mapper class for PNG files.
     */
    class WideImage_Mapper_PNG
    {
        public function load($uri)
        {
            return @imagecreatefrompng($uri);
        }

        public function save($handle, $uri = null, $compression = 9, $filters = PNG_ALL_FILTERS)
        {
            return imagepng($handle, $uri, $compression, $filters);
        }
    }
