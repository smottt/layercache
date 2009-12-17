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
	class LayerCache_StackBuilder
	{
		protected $map;
		protected $dataSource;
		protected $keySource;
		protected $caches = array();
		
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
		 * Adds a cache to the stack specification
		 * 
		 * @param object $cache An arbitrary object that implements get($key) and set($key, $data, $ttl) methods
		 * @return LayerCache_StackBuilder $this
		 */
		function addCache($cache)
		{
			$this->caches[] = array('cache' => $cache, 'ttl' => 0, 'prefetchTime' => 0, 'prefetchProbability' => 1, 'serialize' => null);
			return $this;
		}
		
		/**
		 * Adds TTL to the specification
		 * 
		 * @param int $ttl
		 * @return LayerCache_StackBuilder $this
		 */
		function withTTL($ttl)
		{
			$this->caches[count($this->caches) - 1]['ttl'] = $ttl;
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
			$this->caches[count($this->caches) - 1]['serialize'] = $method;
			return $this;
		}
		
		/**
		 * Adds a prefetch feature to the stack specification
		 * 
		 * @param int $time
		 * @param float $probability
		 * @return LayerCache_StackBuilder $this
		 */
		function withPrefetch($time, $probability)
		{
			$this->caches[count($this->caches) - 1]['prefetchTime'] = $time;
			$this->caches[count($this->caches) - 1]['prefetchProbability'] = $probability;
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
			$stack = $this->stackFactory($this->dataSource, $this->keySource, $this->caches, $this->serializationMethod);
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
		 * @param array $stack
		 * @return LayerCache_Stack
		 */
		protected function stackFactory($dataSource, $keySource, $stack)
		{
			return new LayerCache_Stack($dataSource, $keySource, $stack);
		}
	}
	