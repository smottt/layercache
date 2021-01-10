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

use LayerCache\Exception;
use LayerCache\ObjectMap;
use LayerCache\StackBuilder;
use LayerCache\Cache\CachingLayer;

/**
 * @package LayerCache
 *
 * @author Gasper Kozak
 * @author Metod N <metod@simpel.si>
 */
class LayerCache
{
	/**
	 * @var \LayerCache\ObjectMap
	 */
	protected static $stackMap;

	/**
	 * @var \LayerCache\ObjectMap
	 */
	protected static $cacheMap;

	/**
	 * Returns the version of the library
	 *
	 * @static
	 * @return string Version of the library
	 * @throws \LayerCache\Exception
	 */
	public static function version()
	{
		$composerJson  = __DIR__ . DIRECTORY_SEPARATOR . '..';
		$composerJson .= DIRECTORY_SEPARATOR . 'composer.json';

		$composerInfo = json_decode(file_get_contents($composerJson), true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception(
				'Could not read version from composer.json!'
			);
		}

		if (!array_key_exists('version', $composerInfo)) {
			throw new Exception('Version info missing in composer.json!');
		}

		return $composerInfo['version'];
	}

	/**
	 * Clears the cache maps and stacks
	 *
	 * @static
	 */
	public static function clear()
	{
		self::$stackMap = null;
		self::$cacheMap = null;
	}

	/**
	 * Returns a stack builder object for further specification
	 *
	 * @static
	 * @param  callable $dataProvider
	 * @param  callable $keyMapper
	 * @return \LayerCache\StackBuilder
	 */
	public static function forSource(
		callable $dataProvider,
		callable $keyMapper = null
	) {
		if (self::$stackMap === null) {
			self::$stackMap = new ObjectMap();
		}

		if (self::$cacheMap === null) {
			self::$cacheMap = new ObjectMap();
		}

		return new StackBuilder(
			self::$stackMap, self::$cacheMap, $dataProvider, $keyMapper
		);
	}

	/**
	 * Returns a named stack
	 *
	 * Throws an exception if the named stack isn't found.
	 *
	 * @static
	 * @param  string $name
	 * @return \LayerCache\Stack
	 */
	public static function stack($name)
	{
		return self::$stackMap->get($name);
	}

	/**
	 * Returns true if the named stack exists, false otherwise
	 *
	 * @static
	 * @param  string $name
	 * @return bool
	 */
	public static function hasStack($name)
	{
		return self::$stackMap && self::$stackMap->has($name);
	}

	/**
	 * Adds a named cache for using with addLayer
	 *
	 * @static
	 * @param string $name
	 * @param \LayerCache\Cache\CachingLayer $cache
	 */
	public static function registerCache($name, CachingLayer $cache)
	{
		if (self::$cacheMap === null) {
			self::$cacheMap = new ObjectMap();
		}

		self::$cacheMap->set($name, $cache);
	}
}
