<?php

/**
 * Copyright 2009-2021 Gasper Kozak
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
 */

namespace LayerCache\Cache;

/**
 * @package LayerCache
 *
 * @author Gasper Kozak
 * @author Metod N <metod@simpel.si>
 */
class Memcache implements CachingLayer
{
	/** @var \Memcache */
	protected $memcache;

	/** @var int */
	protected $flags;

	/**
	 * Construct a new memcache cache layer object.
	 *
	 * @param \Memcache $memcache
	 * @param int $flags
	 */
	public function __construct(\Memcache $memcache, $flags = 0)
	{
		$this->memcache = $memcache;
		$this->flags    = $flags;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return mixed
	 */
	public function get($key)
	{
		$data = $this->memcache->get($key);

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
		return $this->memcache->set($key, $data, $this->flags, $ttl);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public function del($key)
	{
		return $this->memcache->delete($key);
	}
}
