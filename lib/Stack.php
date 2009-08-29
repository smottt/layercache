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
	
	class LayerCache_Stack
	{
		protected $dataCallback;
		protected $keyCallback;
		protected $caches = array();
		protected $probabilityFactor = 1000000;
		
		function __construct($dataCallback, $keyCallback, array $caches = array())
		{
			$this->dataCallback = $dataCallback;
			$this->keyCallback = $keyCallback;
			
			$c = count($caches);
			for ($i = $c - 1; $i >= 0; $i--)
			{
				$caches[$i]['prefetchProbability'] = round($caches[$i]['prefetchProbability'] * $this->probabilityFactor);
				$this->caches[] = $caches[$i];
			}
		}
		
		function get($key)
		{
			$c = count($this->caches);
			$emptyList = array();
			$data = null;
			
			if ($c > 0)
			{
				$now = time();
				$nk = call_user_func($this->keyCallback, $key);
				$r = mt_rand(1, $this->probabilityFactor);
				
				foreach ($this->caches as $i => $cache)
				{
					$entry = $cache['cache']->read($nk);
					
					if ($entry === null || !isset($entry['data']) || !isset($entry['expires']) || 
						($now + $cache['prefetchTime'] >= $entry['expires'] && $r <= $cache['prefetchProbability']))
					{
						$emptyList[] = $i;
					}
					else
					{
						$data = $entry['data'];
						break;
					}
				}
			}
			
			if (!$data)
				$data = call_user_func($this->dataCallback, $key);
			
			foreach ($emptyList as $i)
			{
				$cache = $this->caches[$i];
				$entry = array('data' => $data, 'expires' => $now + $cache['ttl']);
				$cache['cache']->write($nk, $entry, $cache['ttl']);
			}
			
			return $data;
		}
		
		function set($key, $data)
		{
			$now = time();
			$nk = call_user_func($this->keyCallback, $key);
			foreach ($this->caches as $cache)
			{
				$entry = array('data' => $data, 'expires' => $now + $cache['ttl']);
				$cache['cache']->write($nk, $entry, $cache['ttl']);
			}
		}
	}
	
