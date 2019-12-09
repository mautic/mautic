<?php

    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * Rotate operation class.
     */
    class WideImage_Operation_Rotate
    {
        /**
         * Returns rotated image.
         *
         * @param WideImage_Image $image
         * @param numeric         $angle
         * @param int             $bgColor
         * @param bool            $ignoreTransparent
         *
         * @return WideImage_Image
         */
        public function execute($image, $angle, $bgColor, $ignoreTransparent)
        {
            $angle = -floatval($angle);
            if ($angle < 0) {
                $angle = 360 + $angle;
            }
            $angle = $angle % 360;

            if (0 == $angle) {
                return $image->copy();
            }

            $image = $image->asTrueColor();

            if (null === $bgColor) {
                $bgColor = $image->getTransparentColor();
                if (-1 == $bgColor) {
                    $bgColor = $image->allocateColorAlpha(255, 255, 255, 127);
                    imagecolortransparent($image->getHandle(), $bgColor);
                }
            }

            return new WideImage_TrueColorImage(imagerotate($image->getHandle(), $angle, $bgColor, $ignoreTransparent));
        }
    }
