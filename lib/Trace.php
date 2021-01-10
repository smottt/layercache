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
class Trace
{
	/** @var int */
	public $time;

	/** @var string */
	public $key;

	/** @var int */
	public $cache_count;

	/** @var string */
	public $flat_key;

	/** @var int */
	public $rand;

	/** @var array */
	public $reads = [];

	/** @var array */
	public $source = [];

	/** @var array */
	public $writes = [];
}
