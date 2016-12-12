<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * ApplyConvolution operation class.
     */
    class WideImage_Operation_ApplyConvolution
    {
        /**
         * Executes imageconvolution() filter.
         *
         * @param WideImage_Image $image
         * @param array           $matrix
         * @param numeric         $div
         * @param numeric         $offset
         *
         * @return WideImage_Image
         */
        public function execute($image, $matrix, $div, $offset)
        {
            $new = $image->asTrueColor();
            if (!imageconvolution($new->getHandle(), $matrix, $div, $offset)) {
                throw new WideImage_GDFunctionResultException('imageconvolution() returned false');
            }

            return $new;
        }
    }
