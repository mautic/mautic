<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * Crop operation class.
     */
    class WideImage_Operation_Crop
    {
        /**
         * Returns a cropped image.
         *
         * @param WideImage_Image  $img
         * @param smart_coordinate $left
         * @param smart_coordinate $top
         * @param smart_coordinate $width
         * @param smart_coordinate $height
         *
         * @return WideImage_Image
         */
        public function execute($img, $left, $top, $width, $height)
        {
            $width  = WideImage_Coordinate::fix($width, $img->getWidth(), $width);
            $height = WideImage_Coordinate::fix($height, $img->getHeight(), $height);
            $left   = WideImage_Coordinate::fix($left, $img->getWidth(), $width);
            $top    = WideImage_Coordinate::fix($top, $img->getHeight(), $height);
            if ($left < 0) {
                $width = $left + $width;
                $left  = 0;
            }

            if ($width > $img->getWidth() - $left) {
                $width = $img->getWidth() - $left;
            }

            if ($top < 0) {
                $height = $top + $height;
                $top    = 0;
            }

            if ($height > $img->getHeight() - $top) {
                $height = $img->getHeight() - $top;
            }

            if ($width <= 0 || $height <= 0) {
                throw new WideImage_Exception("Can't crop outside of an image.");
            }

            $new = $img->doCreate($width, $height);

            if ($img->isTransparent() || $img instanceof WideImage_PaletteImage) {
                $new->copyTransparencyFrom($img);
                if (!imagecopyresized($new->getHandle(), $img->getHandle(), 0, 0, $left, $top, $width, $height, $width, $height)) {
                    throw new WideImage_GDFunctionResultException('imagecopyresized() returned false');
                }
            } else {
                $new->alphaBlending(false);
                $new->saveAlpha(true);
                if (!imagecopyresampled($new->getHandle(), $img->getHandle(), 0, 0, $left, $top, $width, $height, $width, $height)) {
                    throw new WideImage_GDFunctionResultException('imagecopyresampled() returned false');
                }
            }

            return $new;
        }
    }
