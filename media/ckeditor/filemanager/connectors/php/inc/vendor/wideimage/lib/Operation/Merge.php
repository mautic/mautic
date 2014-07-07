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
	 * Merge operation class
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_Merge
	{
		/**
		 * Returns a merged image
		 *
		 * @param WideImage_Image $base
		 * @param WideImage_Image $overlay
		 * @param smart_coordinate $left
		 * @param smart_coordinate $top
		 * @param numeric $pct
		 * @return WideImage_Image
		 */
		function execute($base, $overlay, $left, $top, $pct)
		{
			$x = WideImage_Coordinate::fix($left, $base->getWidth(), $overlay->getWidth());
			$y = WideImage_Coordinate::fix($top, $base->getHeight(), $overlay->getHeight());
			
			$result = $base->asTrueColor();
			$result->alphaBlending(true);
			$result->saveAlpha(true);
			
			if ($pct <= 0)
				return $result;
			
			if ($pct < 100)
			{
				if (!imagecopymerge(
					$result->getHandle(), 
					$overlay->getHandle(), 
					$x, $y, 0, 0, 
					$overlay->getWidth(), 
					$overlay->getHeight(), 
					$pct))
				throw new WideImage_GDFunctionResultException("imagecopymerge() returned false");
			}
			else
			{
				if (!imagecopy(
					$result->getHandle(), 
					$overlay->getHandle(), 
					$x, $y, 0, 0, 
					$overlay->getWidth(), 
					$overlay->getHeight()))
				throw new WideImage_GDFunctionResultException("imagecopy() returned false");
			}
			
			return $result;
		}
	}
