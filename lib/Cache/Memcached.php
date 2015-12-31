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

namespace LayerCache\Cache;

use LayerCache\Cache\CachingLayer;

/**
 * @package LayerCache
 *
 * @author Gasper Kozak
 * @author Metod N <metod@simpel.si>
 */
class Memcached implements CachingLayer
{
	/** @var \Memcached */
	protected $memcached;

	/**
	 * Construct a new memcached cache layer object.
	 *
	 * @param \Memcached $memcached
	 */
	public function __construct(\Memcached $memcached)
	{
		$this->memcached = $memcached;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return mixed
	 */
	public function get($key)
	{
		$data = $this->memcached->get($key);

		if ($data === false) {
			return null;
		}

		return $data;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public function set($key, $data, $ttl)
	{
		return $this->memcached->set($key, $data, $ttl);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public function del($key)
	{
		return $this->memcached->delete($key);
	}
}
