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

    * @package Internal/Mappers
  **/
	
	/**
	 * Mapper class for GIF files
	 * 
	 * @package Internal/Mappers
	 */
	class WideImage_Mapper_GIF
	{
		function load($uri)
		{
			return @imagecreatefromgif($uri);
		}
		
		function save($handle, $uri = null)
		{
			// This is a workaround for a bug, for which PHP devs claim it's not 
			// really a bug. Well, it IS.
			// You can't pass null as the second parameter, because php is
			// then trying to save an image to a '' location (which results in an
			// error, of course). And the same thing works fine for imagepng() and 
			// imagejpeg(). It's a bug! ;)
			if ($uri)
				return imagegif($handle, $uri);
			else
				return imagegif($handle);
		}
	}
