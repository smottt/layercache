<?php
	/**
	Copyright 2009, 2010 Gasper Kozak
	
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
	class LayerCache_StackBuilder
	{
		protected $map;
		protected $dataSource;
		protected $keySource;
		protected $layers = array();
		
		/**
		 * Creates a builder with a cache map and source
		 * 
		 * When the method toStack() is called, 
		 * StackBuilder writes an instance of LayerCache_Stack to the $map.
		 * 
		 * @param LayerCache_StackMap $map
		 * @param callback $dataSource
		 * @param callback $keySource
		 */
		function __construct(LayerCache_ObjectMap $map, $dataSource, $keySource)
		{
			$this->map = $map;
			$this->dataSource = $dataSource;
			$this->keySource = $keySource;
		}
		
		/**
		 * 
		 * Returns the currently added cache
		 * @return LayerCache_Layer
		 */
		protected function currentLayer()
		{
			$c = count($this->layers);
			if ($c == 0)
				throw new RuntimeException("No cache is being added");
			return $this->layers[$c - 1];
		}
		
		protected function createLayer($cache)
		{
			return new LayerCache_Layer($cache);
		}
		
		/**
		 * Adds a cache to the stack specification
		 * 
		 * @param object $cache An arbitrary object that implements get($key) and set($key, $data, $ttl) methods
		 * @return LayerCache_StackBuilder $this
		 */
		function addCache($cache)
		{
			$this->layers[] = $this->createLayer($cache);
			return $this;
		}
		
		/**
		 * Adds TTL to the specification
		 * 
		 * TTL specifies how much time in seconds the items read from this cache will be treated as valid.
		 * If an item is older than TTL seconds, it will be treated as non-existent and will be fetched from next cache or source.
		 * 
		 * If prefetch is enabled on the cache, the item may also be treated as non-existent, if the reading is issued within the last
		 * prefetch time seconds of the TTL, and prefetch probability randomly evaluates to true.
		 * See also LayerCache_StackBuilder::withPrefetch().
		 * 
		 * @param int $ttl TTL for the item
		 * @param int $ttl_empty TTL for the item if it's empty (null, false, emtpy string). If NULL, $ttl is used.
		 * @return LayerCache_StackBuilder $this
		 */
		function withTTL($ttl, $ttl_empty = null)
		{
			$this->currentLayer()->ttl = $ttl;
			$this->currentLayer()->ttl_empty = ($ttl_empty === null ? $ttl : $ttl_empty);
			return $this;
		}
		
		/**
		 * Specifies the serialization method
		 * 
		 * Pass NULL if you wish to use cache-specific serialization (usually serialize).
		 * 
		 * @param mixed 'json', 'serialize', or null
		 * @return LayerCache_StackBuilder $this
		 */
		function serializeWith($method)
		{
			$this->currentLayer()->serializationMethod = $method;
			return $this;
		}
		
		/**
		 * Adds a prefetch feature to the stack specification
		 * 
		 * @param int $time Prefetch time (must be less than TTL)
		 * @param float $probability Prefetch probability, valid values are from 0 to 1 inclusive
		 * @return LayerCache_StackBuilder $this
		 */
		function withPrefetch($time, $probability)
		{
			$this->currentLayer()->prefetchTime = $time;
			$this->currentLayer()->prefetchProbability = $probability;
			return $this;
		}
		
		/**
		 * Creates a stack from the specification and adds it to the cache stack registry
		 * 
		 * @param string $name
		 * @return LayerCache_Stack
		 */
		function toStack($name)
		{
			$stack = $this->createStack($this->dataSource, $this->keySource, $this->layers);
			$this->map->set($name, $stack);
			return $stack;
		}
		
		/**
		 * Creates a stack from specification.
		 * 
		 * Internal method, used for unit testing
		 * 
		 * @param callback $dataSource
		 * @param callback $keySource
		 * @param array $layers
		 * @return LayerCache_Stack
		 */
		protected function createStack($dataSource, $keySource, $layers)
		{
			return new LayerCache_Stack($dataSource, $keySource, $layers);
		}
	}
	