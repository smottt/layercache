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
	
	class LayerCache_StackBuilder
	{
		protected $map;
		protected $reader;
		protected $caches = array();
		
		function __construct(LayerCache_StackMap $map, $reader)
		{
			$this->map = $map;
			$this->reader = $reader;
		}
		
		function addCache($cache)
		{
			$this->caches[] = array('cache' => $cache, 'ttl' => 0, 'prefetchTime' => 0, 'prefetchProbability' => 1);
			return $this;
		}
		
		function withTTL($ttl)
		{
			$this->caches[count($this->caches) - 1]['ttl'] = $ttl;
			return $this;
		}
		
		function withPrefetch($time, $probability)
		{
			$this->caches[count($this->caches) - 1]['prefetchTime'] = $time;
			$this->caches[count($this->caches) - 1]['prefetchProbability'] = $probability;
			return $this;
		}
		
		function toStack($name)
		{
			$stack = $this->stackFactory($this->reader, $this->caches);
			$this->map->set($name, $stack);
			return $stack;
		}
		
		protected function stackFactory($reader, $stack)
		{
			return new LayerCache_Stack($reader, $stack);
		}
	}
	