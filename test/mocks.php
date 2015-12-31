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
 * @package Tests
 */

class FakeSource
{
	public function get($key)
	{
	}

	public function normalizeKey($key)
	{
	}

	public function ttl($value)
	{
	}
}

class FakeCache implements \LayerCache\Cache\CachingLayer
{
	public function get($key)
	{
	}

	public function set($key, $data, $ttl)
	{
	}

	public function del($key)
	{
	}
}

if (!class_exists('\Aerospike')) {
	class Aerospike
	{
		const OK = 'OK';
		const ERR_RECORD_NOT_FOUND = 'ERR_RECORD_NOT_FOUND';

		public function initKey($ns, $set, $pk, $is_digest = false) {}

		public function get(
			array $key,
			array &$record,
			array $filter = [],
			array $options = []
		) {}

		public function put(
			array $key,
			array $bins,
			$ttl = 0,
			array $options = []
		) {}

		public function remove(array $key, array $options = []) {}
	}
}
