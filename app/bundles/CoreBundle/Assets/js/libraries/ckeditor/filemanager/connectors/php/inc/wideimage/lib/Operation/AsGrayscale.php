<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * AsGrayscale operation class.
     */
    class WideImage_Operation_AsGrayscale
    {
        /**
         * Returns a greyscale copy of an image.
         *
         * @param WideImage_Image $image
         *
         * @return WideImage_Image
         */
        public function execute($image)
        {
            $new = $image->asTrueColor();
            if (!imagefilter($new->getHandle(), IMG_FILTER_GRAYSCALE)) {
                throw new WideImage_GDFunctionResultException('imagefilter() returned false');
            }

            if (!$image->isTrueColor()) {
                $new = $new->asPalette();
            }

            return $new;
        }
    }
