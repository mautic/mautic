<?php
	/**
 * @author Gasper Kozak
 * @copyright 2007-2011

    This file is part of WideImage.
		
    WideImage is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; either version 2.1 of the License, or
    (at your option) any later version.
		
    WideImage is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.
		
    You should have received a copy of the GNU Lesser General Public License
    along with WideImage; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

	* @package Internals
  **/
	
	/**
	 * @package Exceptions
	 */
	class WideImage_UnknownImageOperationException extends WideImage_Exception {}
	
	/**
	 * Operation factory
	 * 
	 * @package Internals
	 **/
	class WideImage_OperationFactory
	{
		static protected $cache = array();
		
		static function get($operationName)
		{
			$lcname = strtolower($operationName);
			if (!isset(self::$cache[$lcname]))
			{
				$opClassName = "WideImage_Operation_" . ucfirst($operationName);
				if (!class_exists($opClassName, false))
				{
					$fileName = WideImage::path() . 'Operation/' . ucfirst($operationName) . '.php';
					if (file_exists($fileName))
						require_once $fileName;
					elseif (!class_exists($opClassName))
						throw new WideImage_UnknownImageOperationException("Can't load '{$operationName}' operation.");
				}
				self::$cache[$lcname] = new $opClassName();
			}
			return self::$cache[$lcname];
		}
	}
