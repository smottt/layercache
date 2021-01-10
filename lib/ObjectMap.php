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
 *
 * @package LayerCache
 */

namespace LayerCache;

/**
 * @package LayerCache
 *
 * @author Gasper Kozak
 * @author Metod N <metod@simpel.si>
 */
class ObjectMap
{
	/**
	 * @var array
	 */
	protected $objects = [];

	/**
	 * @var bool
	 */
	protected $writeOnce = true;

	/**
	 * Creates an instance of object map
	 *
	 * @param bool $writeOnce Determines whether named objects can be overwritten
	 */
	public function __construct($writeOnce = true)
	{
		$this->writeOnce = $writeOnce;
	}

	/**
	 * Sets a named object
	 *
	 * @param string $name
	 * @param mixed  $object
	 */
	public function set($name, $object)
	{
		if ($this->writeOnce && $this->has($name)) {
			throw new \RuntimeException(
				"Named object '{$name}' already exists."
			);
		}

		$this->objects[$name] = $object;
	}

	/**
	 * Returns a named object
	 *
	 * Throws an exception if stack doesn't exist
	 *
	 * @param  string $name
	 * @return mixed
	 */
	public function get($name)
	{
		if (!isset($this->objects[$name])) {
			throw new \RuntimeException("No named object '{$name}' found");
		}

		return $this->objects[$name];
	}

	/**
	 * Returns true if the object exists, false otherwise
	 *
	 * @param  string $name
	 * @return bool
	 */
	public function has($name)
	{
		return isset($this->objects[$name]);
	}
}
