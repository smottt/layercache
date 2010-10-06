<?php
	/**
	Copyright 2009, 2010 Gasper Kozak
	
    This file is part of LayerCache.
		
    LayerCache is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    LayerCache is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with LayerCache.  If not, see <http://www.gnu.org/licenses/>.

    @package LayerCache
	**/
	
	/**
	 * @package LayerCache
	 * @author Gasper Kozak
	 */
	class LayerCache
	{
		/**
		 * @var LayerCache_ObjectMap
		 */
		protected static $stackMap;
		
		/**
		 * @var LayerCache_ObjectMap
		 */
		protected static $cacheMap;
		
		/**
		 * Returns the path to the library
		 * 
		 * @return string Library path
		 */
		static function path()
		{
			static $path;
			if ($path === null)
				$path = dirname(__FILE__);
			return $path;
		}
		
		/**
		 * Returns the version of the library
		 * 
		 * @return string Version of the library
		 */
		static function version()
		{
			return '##VERSION##';
		}

		/**
		 * Clears the cache maps and stacks
		 */
		static function clear()
		{
			self::$stackMap = null;
			self::$cacheMap = null;
		}
		
		/**
		 * Returns a stack builder object for further specification
		 * 
		 * @param mixed $dataProvider
		 * @param mixed $keyMapper
		 * @return LayerCache_StackBuilder
		 */
		static function forSource($dataProvider, $keyMapper = null)
		{
			if (self::$stackMap === null)
				self::$stackMap = new LayerCache_ObjectMap();
			
			if (self::$cacheMap === null)
				self::$cacheMap = new LayerCache_ObjectMap();
			
			if (!is_callable($dataProvider))
				throw new LayerCache_Exception("Data provider must be a callable method or function.");
			
			if ($keyMapper !== null && !is_callable($keyMapper))
				throw new LayerCache_Exception("Key mapper must be a callable method or function.");
			
			return new LayerCache_StackBuilder(self::$stackMap, self::$cacheMap, $dataProvider, $keyMapper);
		}
		
		/**
		 * Returns a named stack
		 * 
		 * Throws an exception if the named stack isn't found.
		 * 
		 * @param string $name
		 * @return LayerCache_Stack
		 */
		static function stack($name)
		{
			return self::$stackMap->get($name);
		}
		
		/**
		 * Returns true if the named stack exists, false otherwise
		 * 
		 * @param string $name
		 * @return bool
		 */
		static function hasStack($name)
		{
			return self::$stackMap && self::$stackMap->has($name);
		}
		
		/**
		 * Adds a named cache for using with addLayer
		 *
		 * @param string $name
		 * @param object $cache
		 */
		static function registerCache($name, $cache)
		{
			if (self::$cacheMap === null)
				self::$cacheMap = new LayerCache_ObjectMap();
			
			self::$cacheMap->set($name, $cache);
		}
	}
	
	require_once LayerCache::path() . '/Stack.php';
	require_once LayerCache::path() . '/Layer.php';
	require_once LayerCache::path() . '/Trace.php';
	require_once LayerCache::path() . '/Exception.php';
	require_once LayerCache::path() . '/ObjectMap.php';
	require_once LayerCache::path() . '/StackBuilder.php';
	
	require_once LayerCache::path() . '/Cache/Local.php';
	require_once LayerCache::path() . '/Cache/APC.php';
	require_once LayerCache::path() . '/Cache/File.php';
	require_once LayerCache::path() . '/Cache/Memcache.php';
	require_once LayerCache::path() . '/Cache/Memcached.php';
	require_once LayerCache::path() . '/Cache/XCache.php';
	
