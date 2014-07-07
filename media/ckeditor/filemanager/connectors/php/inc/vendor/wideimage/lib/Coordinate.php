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
	class WideImage_InvalidCoordinateException extends WideImage_Exception {}
	
	/**
	 * A utility class for smart coordinates
	 *  
	 * @package Internals
	 **/
	class WideImage_Coordinate
	{
		static protected $coord_align = array("left", "center", "right", "top", "middle", "bottom");
		static protected $coord_numeric = array("[0-9]+", "[0-9]+\.[0-9]+", "[0-9]+%", "[0-9]+\.[0-9]+%");
		
		/**
		 * Parses a numeric or string representation of a corrdinate into a structure
		 * 
		 * @param string $coord Smart coordinate
		 * @return array Parsed smart coordinate
		 */
		static function parse($c)
		{
			$tokens = array();
			$operators = array('+', '-');
			
			$flush_operand = false;
			$flush_operator = false;
			$current_operand = '';
			$current_operator = '';
			$coordinate = strval($c);
			$expr_len = strlen($coordinate);
			
			for ($i = 0; $i < $expr_len; $i++)
			{
				$char = $coordinate[$i];
				
				if (in_array($char, $operators))
				{
					$flush_operand = true;
					$flush_operator = true;
					$current_operator = $char;
				}
				else
				{
					$current_operand .= $char;
					if ($i == $expr_len - 1)
						$flush_operand = true;
				}
				
				if ($flush_operand)
				{
					if (trim($current_operand) != '')
						$tokens[] = array('type' => 'operand', 'value' => trim($current_operand));
					
					$current_operand = '';
					$flush_operand = false;
				}
				
				if ($flush_operator)
				{
					$tokens[] = array('type' => 'operator', 'value' => $char);
					$flush_operator = false;
				}
			}
			return $tokens;
		}
		
		/**
		 * Evaluates the $coord relatively to $dim
		 * 
		 * @param string $coord A numeric value or percent string
		 * @param int $dim Dimension
		 * @param int $sec_dim Secondary dimension (for align)
		 * @return int Calculated value
		 */
		static function evaluate($coord, $dim, $sec_dim = null)
		{
			$comp_regex = implode('|', self::$coord_align) . '|' . implode('|', self::$coord_numeric);
			if (preg_match("/^([+-])?({$comp_regex})$/", $coord, $matches))
			{
				$sign = intval($matches[1] . "1");
				$val = $matches[2];
				if (in_array($val, self::$coord_align))
				{
					if ($sec_dim === null)
					{
						switch ($val)
						{
							case 'left':
							case 'top':
								return 0;
								break;
							case 'center':
							case 'middle':
								return $sign * intval($dim / 2);
								break;
							case 'right':
							case 'bottom':
								return $sign * $dim;
								break;
							default:
								return null;
						}
					}
					else
					{
						switch ($val)
						{
							case 'left':
							case 'top':
								return 0;
								break;
							case 'center':
							case 'middle':
								return $sign * intval($dim / 2 - $sec_dim / 2);
								break;
							case 'right':
							case 'bottom':
								return $sign * ($dim - $sec_dim);
								break;
							default:
								return null;
						}
					}
				}
				elseif (substr($val, -1) === '%')
					return intval(round($sign * $dim * floatval(str_replace('%', '', $val)) / 100));
				else
					return $sign * intval(round($val));
			}
		}
		
		/**
		 * Calculates and fixes a smart coordinate into a numeric value
		 * 
		 * @param mixed $value Smart coordinate, relative to $dim
		 * @param int $dim Coordinate to which $value is relative
		 * @param int $sec_dim Secondary dimension (for align)
		 * @return int Calculated value
		 */
		static function fix($value, $dim, $sec_dim = null)
		{
			$coord_tokens = self::parse($value);
			
			if (count($coord_tokens) == 0 || $coord_tokens[count($coord_tokens) - 1]['type'] != 'operand')
				throw new WideImage_InvalidCoordinateException("Couldn't parse coordinate '$value' properly.");
			
			$value = 0;
			$operation = 1;
			foreach ($coord_tokens as $token)
			{
				if ($token['type'] == 'operand')
				{
					$operand_value = self::evaluate($token['value'], $dim, $sec_dim);
					if ($operation == 1)
						$value = $value + $operand_value;
					elseif ($operation == -1)
						$value = $value - $operand_value;
					else
						throw new WideImage_InvalidCoordinateException("Invalid coordinate syntax.");
					
					$operation = 0;
				}
				elseif ($token['type'] == 'operator')
				{
					if ($token['value'] == '-')
					{
						if ($operation == 0)
							$operation = -1;
						else
							$operation = $operation * -1;
					}
					elseif ($token['value'] == '+')
					{
						if ($operation == 0)
							$operation = '1';
					}
				}
			}
			return $value;
		}
	}
