<?php
	/**
	Copyright 2009 Gasper Kozak
	
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
	
	class LayerCache
	{
		protected static $map;
		
		static function path()
		{
			static $path;
			if ($path === null)
				$path = dirname(__FILE__);
			return $path;
		}
		
		static function version()
		{
			return '##VERSION##';
		}
		
		static function forSource($dataSource, $keySource = null)
		{
			if (self::$map === null)
				self::$map = new LayerCache_StackMap();
			
			if (is_object($dataSource))
				$read_func = array($dataSource, 'get');
			else
				$read_func = $dataSource;
			
			if ($keySource === null && is_object($dataSource))
				$key_func = array($dataSource, 'normalizeKey');
			else
				$key_func = $keySource;
			
			return new LayerCache_StackBuilder(self::$map, $read_func, $key_func);
		}
		
		static function stack($name)
		{
			return self::$map->get($name);
		}
		
		static function hasStack($name)
		{
			return self::$map->has($name);
		}
	}
	
	require_once LayerCache::path() . '/Stack.php';
	require_once LayerCache::path() . '/StackMap.php';
	require_once LayerCache::path() . '/StackBuilder.php';
	
	require_once LayerCache::path() . '/Cache/Local.php';
	require_once LayerCache::path() . '/Cache/APC.php';
	require_once LayerCache::path() . '/Cache/File.php';
	require_once LayerCache::path() . '/Cache/Memcache.php';
	require_once LayerCache::path() . '/Cache/Memcached.php';
	require_once LayerCache::path() . '/Cache/XCache.php';
	