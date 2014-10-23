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

    * @package Internal/Operations
  **/
	
	/**
	 * AsNegative operation class
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_AsNegative
	{
		/**
		 * Returns a greyscale copy of an image
		 *
		 * @param WideImage_Image $image
		 * @return WideImage_Image
		 */
		function execute($image)
		{
			$palette = !$image->isTrueColor();
			$transparent = $image->isTransparent();
			
			if ($palette && $transparent)
				$tcrgb = $image->getTransparentColorRGB();
			
			$new = $image->asTrueColor();
			if (!imagefilter($new->getHandle(), IMG_FILTER_NEGATE))
				throw new WideImage_GDFunctionResultException("imagefilter() returned false");
			
			if ($palette)
			{
				$new = $new->asPalette();
				if ($transparent)
				{
					$irgb = array('red' => 255 - $tcrgb['red'], 'green' => 255 - $tcrgb['green'], 'blue' => 255 - $tcrgb['blue'], 'alpha' => 127);
					// needs imagecolorexactalpha instead of imagecolorexact, otherwise doesn't work on some transparent GIF images
					$new_tci = imagecolorexactalpha($new->getHandle(), $irgb['red'], $irgb['green'], $irgb['blue'], 127);
					$new->setTransparentColor($new_tci);
				}
			}
			return $new;
		}
	}
