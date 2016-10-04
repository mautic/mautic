<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * Flip operation class.
     */
    class WideImage_Operation_Flip
    {
        /**
         * Returns a flipped image.
         *
         * @param WideImage_Image $image
         *
         * @return WideImage_Image
         */
        public function execute($image)
        {
            $new = $image->copy();

            $width  = $image->getWidth();
            $height = $image->getHeight();

            if ($new->isTransparent()) {
                imagefilledrectangle($new->getHandle(), 0, 0, $width, $height, $new->getTransparentColor());
            }

            for ($y = 0; $y < $height; ++$y) {
                if (!imagecopy($new->getHandle(), $image->getHandle(), 0, $y, 0, $height - $y - 1, $width, 1)) {
                    throw new WideImage_GDFunctionResultException('imagecopy() returned false');
                }
            }

            return $new;
        }
    }
