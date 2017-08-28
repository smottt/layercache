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
 */

namespace LayerCache\Cache;

/**
 * A php array LRU cache
 *
 * @package LayerCache
 *
 * @author Gasper Kozak
 * @author Metod N <metod@simpel.si>
 */
class Local implements CachingLayer
{
	/** @var array */
	protected $items = [];

	/** @var int */
	protected $maxItems = 0;

	/** @var int */
	protected $maxSize = 0;

	/** @var int */
	protected $size = 0;

	/** @var int */
	protected $count = 0;

	/**
	 * Creates a local cache (php array) instance
	 *
	 * @param int $maxSize Max allowed size for all items
	 * @param int $maxItems Max allowed entries in the cache
	 */
	public function __construct($maxSize = 0, $maxItems = 0)
	{
		$this->maxSize  = $maxSize;
		$this->maxItems = $maxItems;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get($key)
	{
		if (!isset($this->items[$key])) {
			return;
		}

		$item = $this->items[$key];

		unset($this->items[$key]);

		$this->items[$key] = $item;

		return $item['data'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function set($key, $data, $ttl)
	{
		if (isset($this->items[$key])) {
			$this->size = $this->size - $this->items[$key]['size'];
			$this->count--;

			unset($this->items[$key]);
		}

		$this->count++;

		if (is_scalar($data)) {
			$size = strlen($data);
		} else {
			$size = strlen(serialize($data));
		}

		$this->size += $size;

		$this->items[$key] = array(
			'size' => $size,
			'data' => $data
		);

		while (($this->maxItems > 0 && $this->count > $this->maxItems) || ($this->maxSize > 0 && $this->size > $this->maxSize)) {
			$this->evict();
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function del($key)
	{
		if (!array_key_exists($key, $this->items)) {
			return;
		}

		$this->count--;
		$this->size -= $this->items[$key]['size'];

		unset($this->items[$key]);
	}

	/**
	 * Evicts a single item
	 */
	protected function evict()
	{
		$this->del(key($this->items));
	}
}
