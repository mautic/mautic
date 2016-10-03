<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * Mapper class for GD2 files.
     */
    class WideImage_Mapper_GD2
    {
        public function load($uri)
        {
            return @imagecreatefromgd2($uri);
        }

        public function save($handle, $uri = null, $chunk_size = null, $type = null)
        {
            return imagegd2($handle, $uri, $chunk_size, $type);
        }
    }
