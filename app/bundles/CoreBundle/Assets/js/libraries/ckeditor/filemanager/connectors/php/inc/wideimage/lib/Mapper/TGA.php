<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/
    include_once WideImage::path().'/vendor/de77/TGA.php';

    /**
     * Mapper support for TGA.
     */
    class WideImage_Mapper_TGA
    {
        public function load($uri)
        {
            return WideImage_vendor_de77_TGA::imagecreatefromtga($uri);
        }

        public function loadFromString($data)
        {
            return WideImage_vendor_de77_TGA::imagecreatefromstring($data);
        }

        public function save($handle, $uri = null)
        {
            throw new WideImage_Exception("Saving to TGA isn't supported.");
        }
    }
