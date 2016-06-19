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
	 * ApplyConvolution operation class
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_ApplyConvolution
	{
		/**
		 * Executes imageconvolution() filter
		 *
		 * @param WideImage_Image $image
		 * @param array $matrix
		 * @param numeric $div
		 * @param numeric $offset
		 * @return WideImage_Image
		 */
		function execute($image, $matrix, $div, $offset)
		{
			$new = $image->asTrueColor();
			if (!imageconvolution($new->getHandle(), $matrix, $div, $offset))
				throw new WideImage_GDFunctionResultException("imageconvolution() returned false");
			return $new;
		}
	}
