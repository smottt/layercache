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
 * @author Metod N <simpel.si>
 */
class Aerospike implements CachingLayer
{
	/** @var \Aerospike */
	protected $aerospike;

	/** @var string */
	protected $ns;

	/** @var string */
	protected $set;

	/**
	 * Construct a new aerospike cache object.
	 *
	 * @param \Aerospike $aerospike
	 * @param string     $ns
	 * @param string     $set
	 */
	public function __construct(\Aerospike $aerospike, $ns, $set)
	{
		$this->aerospike = $aerospike;
		$this->ns        = $ns;
		$this->set       = $set;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return mixed
	 */
	public function get($key)
	{
		$record = [];
		$status = $this->aerospike->get($this->initKey($key), $record, [$key]);

		if (!$this->isOK($status)) {
			return null;
		}

		return $record['bins'][$key];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public function set($key, $value, $ttl)
	{
		$status = $this->aerospike->put($this->initKey($key), array(
			$key => $value
		), $ttl);

		return $this->isOK($status);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public function del($key)
	{
		return $this->isOK(
			$this->aerospike->remove($this->initKey($key))
		);
	}

	/**
	 * Initialize a key tuple.
	 *
	 * @param  string $key
	 * @return array
	 */
	protected function initKey($key)
	{
		return $this->aerospike->initKey($this->ns, $this->set, $key);
	}

	/**
	 * Check the aerospike status.
	 *
	 * @param  mixed $status
	 * @return bool
	 */
	protected function isOK($status)
	{
		return $status === \Aerospike::OK;
	}
}
