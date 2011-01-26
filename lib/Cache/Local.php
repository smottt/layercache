<?php
	/**
	Copyright 2009-2011 Gasper Kozak
	
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
	 * 
	 * 
	 * A php array LRU cache
	 * 
	 * @package LayerCache
	 * @author Gasper Kozak
	 *
	 */
	class LayerCache_Cache_Local
	{
		protected $items = array();
		protected $maxItems = 0;
		protected $maxSize = 0;
		protected $size = 0;
		protected $count = 0;
		
		/**
		 * Creates a local cache (php array) instance
		 * 
		 * @param int $maxSize Max allowed size for all items
		 * @param int $maxItems Max allowed entries in the cache
		 */
		function __construct($maxSize = 0, $maxItems = 0)
		{
			$this->maxSize = $maxSize;
			$this->maxItems = $maxItems;
		}
		
		/**
		 * Returns the value for the key, or null if it doesn't exist
		 * 
		 * @param string $key Normalized key
		 * @return mixed Cached entry or null
		 */
		function get($key)
		{
			if (isset($this->items[$key]))
			{
				$item = $this->items[$key];
				unset($this->items[$key]);
				$this->items[$key] = $item;
				return $item['data'];
			}
		}
		
		/**
		 * Sets the value in the array
		 * 
		 * @param string $key Normalized key
		 * @param mixed $data Data
		 */
		function set($key, $data)
		{
			if (isset($this->items[$key]))
			{
				$this->size = $this->size - $this->items[$key]['size'];
				$this->count--;
				unset($this->items[$key]);
			}
			
			$this->count++;
			
			if (is_scalar($data))
				$size = strlen($data);
			else
				$size = strlen(serialize($data));
			
			$this->size += $size;
			$this->items[$key] = array('size' => $size, 'data' => $data);
			
			while (($this->maxItems > 0 && $this->count > $this->maxItems) || ($this->maxSize > 0 && $this->size > $this->maxSize))
				$this->evict();
		}
		
		/**
		 * Evicts a single item
		 */
		protected function evict()
		{
			$k = key($this->items);
			$this->count--;
			$this->size = $this->size - $this->items[$k]['size'];
			unset($this->items[$k]);
		}
	}
		
