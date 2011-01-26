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
	 * @package LayerCache
	 * @author Gasper Kozak
	 */
	class LayerCache_StackBuilder
	{
		/**
		 * @var LayerCache_ObjectMap
		 */
		protected $cacheMap;
		
		/**
		 * @var LayerCache_ObjectMap
		 */
		protected $stackMap;
		
		/**
		 * @var callback
		 */
		protected $dataSource;
		
		/**
		 * @var callback
		 */
		protected $keySource;
		
		/**
		 * @var array
		 */
		protected $layers = array();
		
		/**
		 * @var LayerCache_Layer
		 */
		protected $currentLayer = null;
		
		/**
		 * Creates a builder with a cache map and source
		 * 
		 * When the method toStack() is called, 
		 * StackBuilder writes an instance of LayerCache_Stack to the $map.
		 * 
		 * @param LayerCache_ObjectMap $stackMap
		 * @param LayerCache_ObjectMap $cacheMap
		 * @param callback $dataSource
		 * @param callback $keySource
		 */
		function __construct(LayerCache_ObjectMap $stackMap, LayerCache_ObjectMap $cacheMap, $dataSource, $keySource)
		{
			$this->stackMap = $stackMap;
			$this->cacheMap = $cacheMap;
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
			if ($this->currentLayer === null)
				throw new RuntimeException("No cache is being added");
			return $this->currentLayer;
		}
		
		/**
		 * Layer factory
		 * 
		 * @param object $cache
		 * @return LayerCache_Layer
		 */
		protected function createLayer($cache)
		{
			return new LayerCache_Layer($cache);
		}
		
		/**
		 * Adds a cache to the stack specification
		 * 
		 * @param mixed $cache An arbitrary object that implements get($key) and set($key, $data, $ttl) methods, or a named cache (see LayerCache::registerCache())
		 * @return LayerCache_StackBuilder $this
		 */
		function addLayer($cache)
		{
			if (is_object($cache))
				$obj = $cache;
			elseif (is_string($cache))
			{
				if ($this->cacheMap->has($cache))
					$obj = $this->cacheMap->get($cache);
				else
					throw new LayerCache_Exception("No named cache found: '{$cache}'");
			}
			else
				throw new LayerCache_Exception("Cache should be an object or a string");
			
			$this->currentLayer = $this->createLayer($obj);
			$this->layers[] = $this->currentLayer;
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
		 * @param int $ttl_empty TTL for the item if it's empty (null, false, zero, empty string, empty array). If NULL, $ttl is used.
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
		 * Pass NULL if you wish to use cache-specific serialization.
		 * 
		 * @param mixed 'json' (json_encode/json_decode), 'php' (serialize/unserialize), or null
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
			$this->stackMap->set($name, $stack);
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
	