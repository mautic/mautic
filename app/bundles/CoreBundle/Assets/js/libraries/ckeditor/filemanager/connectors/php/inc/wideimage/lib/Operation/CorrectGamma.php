<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * CorrectGamma operation class.
     */
    class WideImage_Operation_CorrectGamma
    {
        /**
         * Executes imagegammacorrect().
         *
         * @param WideImage_Image $image
         * @param numeric         $input_gamma
         * @param numeric         $output_gamma
         *
         * @return WideImage_TrueColorImage
         */
        public function execute($image, $input_gamma, $output_gamma)
        {
            $new = $image->copy();
            if (!imagegammacorrect($new->getHandle(), $input_gamma, $output_gamma)) {
                throw new WideImage_GDFunctionResultException('imagegammacorrect() returned false');
            }

            return $new;
        }
    }
