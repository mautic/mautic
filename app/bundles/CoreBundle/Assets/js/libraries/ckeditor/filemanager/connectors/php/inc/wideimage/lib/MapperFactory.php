<?php
    /**
     * @author Gasper Kozak
     * @copyright 2007-2011

     **/

    /**
     * Thrown when image format isn't supported.
     */
    class WideImage_UnsupportedFormatException extends WideImage_Exception
    {
    }

    /**
     * Mapper factory.
     **/
    abstract class WideImage_MapperFactory
    {
        protected static $mappers       = [];
        protected static $customMappers = [];

        protected static $mimeTable = [
            'image/jpg'   => 'JPEG',
            'image/jpeg'  => 'JPEG',
            'image/pjpeg' => 'JPEG',
            'image/gif'   => 'GIF',
            'image/png'   => 'PNG',
            ];

        /**
         * Returns a mapper, based on the $uri and $format.
         *
         * @param string $uri    File URI
         * @param string $format File format (extension or mime-type) or null
         *
         * @return WideImage_Mapper
         **/
        public static function selectMapper($uri, $format = null)
        {
            $format = self::determineFormat($uri, $format);

            if (array_key_exists($format, self::$mappers)) {
                return self::$mappers[$format];
            }

            $mapperClassName = 'WideImage_Mapper_'.$format;

            if (!class_exists($mapperClassName, false)) {
                $mapperFileName = WideImage::path().'Mapper/'.$format.'.php';
                if (file_exists($mapperFileName)) {
                    require_once $mapperFileName;
                }
            }

            if (class_exists($mapperClassName)) {
                self::$mappers[$format] = new $mapperClassName();

                return self::$mappers[$format];
            }

            throw new WideImage_UnsupportedFormatException("Format '{$format}' is not supported.");
        }

        public static function registerMapper($mapper_class_name, $mime_type, $extension)
        {
            self::$customMappers[$mime_type] = $mapper_class_name;
            self::$mimeTable[$mime_type]     = $extension;
        }

        public static function getCustomMappers()
        {
            return self::$customMappers;
        }

        public static function determineFormat($uri, $format = null)
        {
            if ($format == null) {
                $format = self::extractExtension($uri);
            }

            // mime-type match
            if (preg_match('~[a-z]*/[a-z-]*~i', $format)) {
                if (isset(self::$mimeTable[strtolower($format)])) {
                    return self::$mimeTable[strtolower($format)];
                }
            }

            // clean the string
            $format = strtoupper(preg_replace('/[^a-z0-9_-]/i', '', $format));
            if ($format == 'JPG') {
                $format = 'JPEG';
            }

            return $format;
        }

        public static function mimeType($format)
        {
            return array_search(strtoupper($format), self::$mimeTable);
        }

        public static function extractExtension($uri)
        {
            $p = strrpos($uri, '.');
            if ($p === false) {
                return '';
            } else {
                return substr($uri, $p + 1);
            }
        }
    }
