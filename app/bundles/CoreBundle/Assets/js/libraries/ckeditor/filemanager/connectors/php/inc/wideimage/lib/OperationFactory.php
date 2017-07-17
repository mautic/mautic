<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/
    class WideImage_UnknownImageOperationException extends WideImage_Exception
    {
    }

    /**
     * Operation factory.
     **/
    class WideImage_OperationFactory
    {
        protected static $cache = [];

        public static function get($operationName)
        {
            $lcname = strtolower($operationName);
            if (!isset(self::$cache[$lcname])) {
                $opClassName = 'WideImage_Operation_'.ucfirst($operationName);
                if (!class_exists($opClassName, false)) {
                    $fileName = WideImage::path().'Operation/'.ucfirst($operationName).'.php';
                    if (file_exists($fileName)) {
                        require_once $fileName;
                    } elseif (!class_exists($opClassName)) {
                        throw new WideImage_UnknownImageOperationException("Can't load '{$operationName}' operation.");
                    }
                }
                self::$cache[$lcname] = new $opClassName();
            }

            return self::$cache[$lcname];
        }
    }
