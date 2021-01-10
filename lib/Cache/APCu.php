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
 * @author Metod N <metod@simpel.si>
 */
class APCu implements CachingLayer
{
	/**
	 * {@inheritDoc}
	 *
	 * @return mixed
	 */
	public function get($key)
	{
        $success = null;
        $data    = apcu_fetch($key, $success);

		if ($success === false) {
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
		return apcu_store($key, $data, $ttl);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public function del($key)
	{
		return apcu_delete($key);
	}
}
