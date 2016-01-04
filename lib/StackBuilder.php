<?php

/**
 * Copyright 2009-2016 Gasper Kozak
 *
 * This file is part of LayerCache.
 *
 * LayerCache is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LayerCache is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with LayerCache.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package LayerCache
 */

namespace LayerCache;

use LayerCache\Exception;
use LayerCache\ObjectMap;
use LayerCache\Layer;
use LayerCache\Stack;
use LayerCache\Cache\CachingLayer;

/**
 * @package LayerCache
 *
 * @author Gasper Kozak
 * @author Metod N <metod@simpel.si>
 */
class StackBuilder
{
	/**
	 * @var \LayerCache\ObjectMap
	 */
	protected $cacheMap;

	/**
	 * @var \LayerCache\ObjectMap
	 */
	protected $stackMap;

	/**
	 * @var callable
	 */
	protected $dataSource;

	/**
	 * @var callable
	 */
	protected $keySource;

	/**
	 * @var array
	 */
	protected $layers = [];

	/**
	 * @var \LayerCache\Layer
	 */
	protected $currentLayer;

	/**
	 * Creates a builder with a cache map and source
	 *
	 * When the method toStack() is called,
	 * StackBuilder writes an instance of \LayerCache\Stack to the $map.
	 *
	 * @param \LayerCache\ObjectMap $stackMap
	 * @param \LayerCache\ObjectMap $cacheMap
	 * @param callable $dataSource
	 * @param callable $keySource
	 */
	public function __construct(
		ObjectMap $stackMap,
		ObjectMap $cacheMap,
		callable $dataSource,
		callable $keySource = null
	) {
		$this->stackMap   = $stackMap;
		$this->cacheMap   = $cacheMap;
		$this->dataSource = $dataSource;
		$this->keySource  = $keySource;
	}

	/**
	 * Returns the currently added cache
	 *
	 * @return \LayerCache\Layer
	 */
	protected function currentLayer()
	{
		if ($this->currentLayer === null) {
			throw new Exception('No cache is being added');
		}

		return $this->currentLayer;
	}

	/**
	 * Layer factory
	 *
	 * @param  object $cache
	 * @return \LayerCache\Layer
	 */
	protected function createLayer($cache)
	{
		return new Layer($cache);
	}

	/**
	 * Adds a cache to the stack specification
	 *
	 * @param  \LayerCache\Cache\CachingLayer|string $cache
	 * @return \LayerCache\StackBuilder
	 */
	public function addLayer($cache)
	{
		if (is_object($cache)) {
			if (!($cache instanceof CachingLayer)) {
				throw new \LayerCache\Exception(
					'Cache should implement \LayerCache\Cache\CachingLayer interface!'
				);
			}

			$obj = $cache;
		} elseif (is_string($cache)) {
			if (!$this->cacheMap->has($cache)) {
				throw new \LayerCache\Exception(
					"No named cache found: '{$cache}'"
				);
			}

			$obj = $this->cacheMap->get($cache);
		} else {
			throw new Exception('Cache should be an object or a string');
		}

		$this->currentLayer = $this->createLayer($obj);
		$this->layers[]     = $this->currentLayer;

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
	 * See also \LayerCache\StackBuilder::withPrefetch().
	 *
	 * @param  int $ttl TTL for the item
	 * @param  int $ttlEmpty TTL for the item if it's empty (null, false, zero, empty string, empty array). If NULL, $ttl is used.
	 * @return \LayerCache\StackBuilder
	 */
	public function withTTL($ttl, $ttlEmpty = null)
	{
		$this->currentLayer()->ttl = $ttl;
		$this->currentLayer()->ttl_empty = ($ttlEmpty === null ? $ttl : $ttlEmpty);

		return $this;
	}

	/**
	 * Specifies the serialization method
	 *
	 * Pass NULL if you wish to use cache-specific serialization.
	 *
	 * @param  mixed 'json' (json_encode/json_decode), 'php' (serialize/unserialize), or null
	 * @return \LayerCache\StackBuilder
	 */
	public function serializeWith($method)
	{
		$this->currentLayer()->serializationMethod = $method;

		return $this;
	}

	/**
	 * Adds a prefetch feature to the stack specification
	 *
	 * @param  int $time Prefetch time (must be less than TTL)
	 * @param  float $probability Prefetch probability, valid values are from 0 to 1 inclusive
	 * @return \LayerCache\StackBuilder
	 */
	public function withPrefetch($time, $probability)
	{
		$this->currentLayer()->prefetchTime = $time;
		$this->currentLayer()->prefetchProbability = $probability;

		return $this;
	}

	/**
	 * Creates a stack from the specification and adds it to the cache stack registry
	 *
	 * @param  string $name
	 * @return \LayerCache\Stack
	 */
	public function toStack($name)
	{
		$stack = $this->createStack(
			$this->dataSource,
			$this->keySource,
			$this->layers
		);

		$this->stackMap->set($name, $stack);

		return $stack;
	}

	/**
	 * Creates a stack from specification.
	 *
	 * Internal method, used for unit testing
	 *
	 * @param  callable $dataSource
	 * @param  callable $keySource
	 * @param  array    $layers
	 * @return \LayerCache\Stack
	 */
	protected function createStack(
		callable $dataSource,
		callable $keySource = null,
		array $layers = []
	) {
		return new Stack($dataSource, $keySource, $layers);
	}
}
