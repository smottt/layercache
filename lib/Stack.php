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
	class LayerCache_Stack
	{
		/**
		 * Data retrieval callback method
		 * @var callback
		 */
		protected $dataCallback;
		
		/**
		 * Key normalization callback
		 * @var callback
		 */
		protected $keyCallback;
		
		/**
		 * An array of caches with meta data
		 * @var array
		 */
		protected $layers = array();
		
		/**
		 * Probability factor for prefetch
		 * @var int
		 */
		protected $probabilityFactor = 1000000;
		
		/**
		 * Creates a stack with callbacks and layers
		 * 
		 * @param callback $dataCallback Data retrieval callback method
		 * @param callback $keyCallback Key normalization callback method
		 * @param array $layers An array of caches with meta data
		 */
		function __construct($dataCallback, $keyCallback, array $layers = array())
		{
			$this->dataCallback = $dataCallback;
			$this->keyCallback = $keyCallback;
			
			$c = count($layers);
			for ($i = $c - 1; $i >= 0; $i--)
			{
				$layers[$i]->prefetchProbability = round($layers[$i]->prefetchProbability * $this->probabilityFactor);
				$this->layers[] = $layers[$i];
			}
		}
		
		/**
		 * Returns a value for a specific key
		 * 
		 * Calls key normalization method first, then iterates over the caches and reads data. 
		 * If no cache contains the data, the data retrieval method is called, and the result is written to all caches.
		 * 
		 * @param mixed $key Custom key
		 * @return mixed
		 */
		function get($key = null)
		{
			$c = count($this->layers);
			$emptyList = array();
			$data = null;
			
			if ($c > 0)
			{
				$now = time();
				$nk = call_user_func($this->keyCallback, $key);
				$r = mt_rand(1, $this->probabilityFactor);
				
				/* @var $layer LayerCache_Layer */
				foreach ($this->layers as $i => $layer)
				{
					$raw_entry = $layer->cache->get($nk);
					$entry = $this->unserialize($raw_entry, $layer->serializationMethod);
					
					if (!$entry || 
						!isset($entry['d']) || 
						!isset($entry['e']) || 
						!is_numeric($entry['e']) || 
						($now >= $entry['e'] && $layer->ttl > 0) ||
						($layer->prefetchTime > 0 && $layer->ttl > 0 && $now + $layer->prefetchTime >= $entry['e'] && $r <= $layer->prefetchProbability))
					{
						$emptyList[] = $i;
					}
					else
					{
						$data = $entry['d'];
						break;
					}
				}
			}
			
			if ($data === null)
				$data = call_user_func($this->dataCallback, $key);
			
			foreach ($emptyList as $i)
			{
				$layer = $this->layers[$i];
				
				if ($data !== null)
					$ttl = $layer->ttl;
				else
					$ttl = $layer->ttl_empty;
				
				$entry = array('d' => $data, 'e' => $now + $ttl);
				$raw_entry = $this->serialize($entry, $layer->serializationMethod);
				$layer->cache->set($nk, $raw_entry, $ttl);
			}
			
			return $data;
		}
		
		/**
		 * Sets data in all layers
		 * 
		 * @param mixed $key Custom key
		 * @param mixed $data
		 */
		function set($key, $data)
		{
			$now = time();
			$nk = call_user_func($this->keyCallback, $key);
			foreach ($this->layers as $layer)
			{
				$entry = array('d' => $data, 'e' => $now + $layer->ttl);
				$layer->cache->set($nk, $entry, $layer->ttl);
			}
		}
		
		protected function serialize($data, $method)
		{
			if ($method == 'php')
				return serialize($data);
			elseif ($method == 'json')
				return json_encode($data);
			else
				return $data;
		}
		
		protected function unserialize($data, $method)
		{
			if ($method == 'php')
				return unserialize($data);
			elseif ($method == 'json')
				return json_decode($data, true);
			else
				return $data;
		}
		
	}
	