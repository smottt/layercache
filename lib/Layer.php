<?php

/**
 * Copyright 2009-2017 Gasper Kozak
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

use LayerCache\Cache\CachingLayer;

/**
 * @package LayerCache
 *
 * @author Gasper Kozak
 * @author Metod N <metod@simpel.si>
 */
class Layer
{
	/** @var \LayerCache\Cache\CachingLayer */
	public $cache;

	/** @var int */
	public $ttl = 0;

	/** @var int */
	public $ttl_empty = 0;

	/** @var int */
	public $prefetchTime = 0;

	/** @var float */
	public $prefetchProbability = 0;

	/** @var string */
	public $serializationMethod = 'php';

	/**
	 * Construct a new cache layer object.
	 *
	 * @param \LayerCache\Cache\CachingLayer $cache
	 */
	public function __construct(CachingLayer $cache)
	{
		$this->cache = $cache;
	}
}
