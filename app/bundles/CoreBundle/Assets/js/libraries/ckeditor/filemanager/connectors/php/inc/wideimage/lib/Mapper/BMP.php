<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/
    include_once WideImage::path().'/vendor/de77/BMP.php';

    /**
     * Mapper support for BMP.
     */
    class WideImage_Mapper_BMP
    {
        public function load($uri)
        {
            return WideImage_vendor_de77_BMP::imagecreatefrombmp($uri);
        }

        public function loadFromString($data)
        {
            return WideImage_vendor_de77_BMP::imagecreatefromstring($data);
        }

        public function save($handle, $uri = null)
        {
            if ($uri == null) {
                return WideImage_vendor_de77_BMP::imagebmp($handle);
            } else {
                return WideImage_vendor_de77_BMP::imagebmp($handle, $uri);
            }
        }
    }
