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
		
		function get($key = null)
		{
			$c = count($this->caches);
			$emptyList = array();
			$data = null;
			
			if ($c > 0)
			{
				$now = time();
				$nk = call_user_func($this->keyCallback, $key);
				$r = mt_rand(1, $this->probabilityFactor);
				
				Debug::write("key($key) nk=$nk r=$r");
				
				foreach ($this->caches as $i => $cache)
				{
					$entry = $cache['cache']->get($nk);
					Debug::write("cache $i (prefetch: {$cache['prefetchTime']}, {$cache['prefetchProbability']}) t={$now} pf=" . ($now + $cache['prefetchTime']));
					Debug::write($entry);
					
					if ($entry === null || !isset($entry['d']) || !isset($entry['e']) || 
						($now + $cache['prefetchTime'] >= $entry['e'] && $r <= $cache['prefetchProbability']))
					{
						Debug::write("prefetch or inexistent");
						$emptyList[] = $i;
					}
					else
					{
						Debug::write('present and valid');
						$data = $entry['d'];
						break;
					}
				}
			}
			
			if (!$data)
				$data = call_user_func($this->dataCallback, $key);
			
			foreach ($emptyList as $i)
			{
				$cache = $this->caches[$i];
				$entry = array('d' => $data, 'e' => $now + $cache['ttl']);
				$cache['cache']->set($nk, $entry, $cache['ttl']);
			}
			
			Debug::write($data);
			
			return $data;
		}
		
		function set($key, $data)
		{
			$now = time();
			$nk = call_user_func($this->keyCallback, $key);
			foreach ($this->caches as $cache)
			{
				$entry = array('d' => $data, 'e' => $now + $cache['ttl']);
				$cache['cache']->set($nk, $entry, $cache['ttl']);
			}
		}
	}
	
