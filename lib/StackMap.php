<?php
	/**
	Copyright 2009 Gasper Kozak
	
    This file is part of LayerCache.
		
    LayerCache is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    LayerCache is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with LayerCache.  If not, see <http://www.gnu.org/licenses/>.

    @package LayerCache
	**/
	
	/**
	 * @package LayerCache
	 * @author Gasper Kozak
	 */
	class LayerCache_StackMap
	{
		protected $stacks = array();
		
		/**
		 * Sets a named cache stack
		 *  
		 * @param string $name
		 * @param LayerCache_Stack $stack
		 */
		function set($name, $stack)
		{
			$this->stacks[$name] = $stack;
		}
		
		/**
		 * Returns a named cache stack
		 *  
		 * Throws an exception if stack doesn't exist
		 *  
		 * @param string $name
		 * @return LayerCache_Stack
		 */
		function get($name)
		{
			if (!isset($this->stacks[$name]))
				throw new RuntimeException("No stack map '{$name}' found");
			
			return $this->stacks[$name];
		}
		
		/**
		 * Returns true if the stack exists, false otherwise
		 * 
		 * @param string $name
		 * @return bool
		 */
		function has($name)
		{
			return isset($this->stacks[$name]);
		}
	}
	