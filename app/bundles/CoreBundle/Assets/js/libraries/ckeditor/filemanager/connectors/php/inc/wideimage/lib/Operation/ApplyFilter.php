<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * ApplyFilter operation class.
     */
    class WideImage_Operation_ApplyFilter
    {
        /**
         * A list of filters that only accept one arguments for imagefilter().
         *
         * @var array
         */
        protected static $one_arg_filters = [IMG_FILTER_SMOOTH, IMG_FILTER_CONTRAST, IMG_FILTER_BRIGHTNESS];

        /**
         * Executes imagefilter.
         *
         * @param WideImage_Image $image
         * @param int             $filter
         * @param numeric         $arg1
         * @param numeric         $arg2
         * @param numeric         $arg3
         *
         * @return WideImage_TrueColorImage
         */
        public function execute($image, $filter, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null)
        {
            $new = $image->asTrueColor();

            if (in_array($filter, self::$one_arg_filters)) {
                $res = imagefilter($new->getHandle(), $filter, $arg1);
            } elseif (defined('IMG_FILTER_PIXELATE') && $filter == IMG_FILTER_PIXELATE) {
                $res = imagefilter($new->getHandle(), $filter, $arg1, $arg2);
            } elseif ($filter == IMG_FILTER_COLORIZE) {
                $res = imagefilter($new->getHandle(), $filter, $arg1, $arg2, $arg3, $arg4);
            } else {
                $res = imagefilter($new->getHandle(), $filter);
            }

            if (!$res) {
                throw new WideImage_GDFunctionResultException('imagefilter() returned false');
            }

            return $new;
        }
    }
