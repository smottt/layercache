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
		 * @var LayerCache_Trace
		 */
		protected $trace;
		
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
		 * Enables tracing for the next get
		 * 
		 * Example:
		 * LayerCache::stack('Users')->trace($tr)->get(5);
		 * print_r($tr);
		 * 
		 * @param $trace
		 */
		function trace(&$trace)
		{
			$trace = new LayerCache_Trace();
			$this->trace = $trace;
			return $this;
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
			$trace = $this->trace;
			if ($trace)
			{
				$this->trace = null;
				$trace->key = $key;
			}
			
			$c = count($this->layers);
			$emptyList = array();
			$data = null;
			
			if ($trace)
				$trace->cache_count = $c;
			
			if ($c > 0)
			{
				$now = time();
				$nk = call_user_func($this->keyCallback, $key);
				$r = mt_rand(1, $this->probabilityFactor);
				
				if ($trace)
				{
					$trace->time = $now;
					$trace->flat_key = $nk;
					$trace->rand = $r;
				}
				
				/* @var $layer LayerCache_Layer */
				foreach ($this->layers as $i => $layer)
				{
					$data = null;
					$raw_entry = $layer->cache->get($nk);
					$entry = $this->unserialize($raw_entry, $layer->serializationMethod);
					
					if ($trace)
						$read = array('index' => $i, 
							'class' => get_class($layer->cache), 
							'unserialize' => $layer->serializationMethod, 
							'data' => $raw_entry, 
							'type' => null,
							'prefetch_active' => $layer->prefetchTime > 0);
					
					if (!$entry)
					{
						if ($trace)
							$read['result'] = 'null';
					}
					elseif (!is_array($entry) || !isset($entry['d']) || !isset($entry['e']) || !is_numeric($entry['e']))
					{
						if ($trace)
							$read['result'] = 'invalid entry';
					}
					elseif ($now >= $entry['e'] && $layer->ttl > 0)
					{
						if ($trace)
							$read['result'] = 'expired by ttl';
					}
					elseif ($layer->prefetchTime > 0 && $layer->ttl > 0 && $now + $layer->prefetchTime >= $entry['e'] && $r <= $layer->prefetchProbability)
					{
						if ($trace)
							$read['result'] = 'expired by prefetch';
					}
					else
					{
						if ($trace)
						{
							$read['result'] = 'OK';
							$read['type'] = gettype($entry['d']);
							$read['data'] = $entry['d'];
						}
						$data = $entry['d'];
					}
					
					if ($trace)
						$trace->reads[] = $read;
					
					if ($data)
						break;
					
					$emptyList[] = $i;
				}
			}
			
			if ($data === null)
			{
				$data = call_user_func($this->dataCallback, $key);
				if ($trace)
					$trace->source = array('key' => $key, 'data' => $data);
			}
			
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
				
				if ($trace)
					$trace->writes[] = array('index' => $i, 'ttl' => $ttl, 'data' => $raw_entry, 'serialize' => $layer->serializationMethod);
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
	