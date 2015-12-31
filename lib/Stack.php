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
 * @package LayerCache
 */

namespace LayerCache;

use LayerCache\Trace;

/**
 * @package LayerCache
 * @author Gasper Kozak
 * @author Metod N <metod@simpel.si>
 */
class Stack
{
	/**
	 * Data retrieval callback method
	 * @var callable
	 */
	protected $dataProvider;

	/**
	 * Key normalization callback
	 * @var callable
	 */
	protected $keyMapper;

	/**
	 * An array of caches with meta data
	 * @var array
	 */
	protected $layers = [];

	/**
	 * Probability factor for prefetch
	 * @var int
	 */
	protected $probabilityFactor = 1000000;

	/**
	 * @var \LayerCache\Trace
	 */
	protected $trace;

	/**
	 * Creates a stack with callbacks and layers
	 *
	 * @param callable $dataProvider Data retrieval callback method
	 * @param callable $keyMapper Key normalization callback method
	 * @param array    $layers An array of caches with meta data
	 */
	public function __construct(
		callable $dataProvider,
		callable $keyMapper = null,
		array $layers = []
	) {
		$this->dataProvider = $dataProvider;
		$this->keyMapper    = $keyMapper;

		$layerCount = count($layers);

		for ($i = $layerCount - 1; $i >= 0; $i--) {
			$layers[$i]->prefetchProbability = round(
				$layers[$i]->prefetchProbability * $this->probabilityFactor
			);

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
	 * @param  mixed $trace
	 * @return \LayerCache\Stack
	 */
	public function trace(&$trace)
	{
		$trace = new Trace();

		$this->trace = $trace;

		return $this;
	}

	/**
	 * Returns a value for a specific key
	 *
	 * Calls key normalization method first, then iterates over the caches and reads data.
	 * If no cache contains the data, the data retrieval method is called, and the result is written to all caches.
	 *
	 * @param  mixed $key Custom key
	 * @return mixed
	 */
	public function get($key = null)
	{
		$trace = $this->trace;

		if ($trace) {
			$this->trace = null;
			$trace->key = $key;
			$trace->cache_count = count($this->layers);
		}

		$layerCount = count($this->layers);

		$emptyList = [];
		$data      = null;
		$retrieved = false;

		if ($layerCount > 0) {
			$now = time();

			if ($this->keyMapper) {
				$mappedKey = call_user_func($this->keyMapper, $key);
			} else {
				$mappedKey = $key;
			}

			$random = mt_rand(1, $this->probabilityFactor);

			if ($trace) {
				$trace->time     = $now;
				$trace->flat_key = $mappedKey;
				$trace->rand     = $random;
			}

			/* @var $layer \LayerCache\Layer */
			foreach ($this->layers as $i => $layer) {
				$raw_entry = $layer->cache->get($mappedKey);
				$entry     = $this->unserialize(
					$raw_entry,
					$layer->serializationMethod
				);

				if ($trace) {
					$read = array(
						'index'       => $i,
						'class'       => get_class($layer->cache),
						'unserialize' => $layer->serializationMethod,
						'data'        => $raw_entry,
						'type'        => null,
						'prefetch'    => array(
							'active'      => $layer->prefetchTime > 0,
							'time'        => $layer->prefetchTime,
							'probability' => $layer->prefetchProbability / $this->probabilityFactor
						)
					);
				}

				if (!$entry) {
					if ($trace) {
						if (strlen($raw_entry) > 0) {
							$read['result'] = 'format mismatch';
						} else {
							$read['result'] = 'null';
						}
					}
				} elseif (!is_array($entry) || !array_key_exists('d', $entry) || !isset($entry['e']) || !is_numeric($entry['e']))
				{
					if ($trace) {
						$read['result'] = 'invalid entry';
					}
				} elseif ($layer->ttl > 0 && $now >= $entry['e']) {
					if ($trace) {
						$read['result'] = 'expired by ttl';
					}
				} elseif ($layer->prefetchTime > 0 && $layer->ttl > 0 && $now + $layer->prefetchTime >= $entry['e'] && $random <= $layer->prefetchProbability) {
					if ($trace) {
						$read['result'] = 'expired by prefetch';
					}
				} else {
					if ($trace) {
						$read['result'] = 'OK';
						$read['type']   = gettype($entry['d']);
						$read['data']   = $entry['d'];
					}

					$data = $entry['d'];
					$retrieved = true;
				}

				if ($trace) {
					$trace->reads[] = $read;
				}

				if ($retrieved) {
					break;
				}

				$emptyList[] = $i;
			}
		}

		if (!$retrieved) {
			$data = call_user_func($this->dataProvider, $key);

			if ($trace) {
				$trace->source = array(
					'key'  => $key,
					'data' => $data
				);
			}
		}

		foreach ($emptyList as $i) {
			$layer = $this->layers[$i];

			if (!$data) {
				$ttl = $layer->ttl;
			} else {
				$ttl = $layer->ttl_empty;
			}

			$entry = array(
				'd' => $data,
				'e' => $now + $ttl
			);

			$raw_entry = $this->serialize($entry, $layer->serializationMethod);

			$layer->cache->set($mappedKey, $raw_entry, $ttl);

			if ($trace) {
				$trace->writes[] = array(
					'index'     => $i,
					'class'     => get_class($layer->cache),
					'ttl'       => $ttl,
					'data'      => $raw_entry,
					'serialize' => $layer->serializationMethod
				);
			}
		}

		return $data;
	}

	/**
	 * Sets data in all layers
	 *
	 * @param mixed $key Custom key
	 * @param mixed $data
	 */
	public function set($key, $data)
	{
		$now = time();

		if ($this->keyMapper) {
			$mappedKey = call_user_func($this->keyMapper, $key);
		} else {
			$mappedKey = $key;
		}

		foreach ($this->layers as $layer) {
			$entry = $this->serialize(
				['d' => $data, 'e' => $now + $layer->ttl],
				$layer->serializationMethod
			);

			$layer->cache->set($mappedKey, $entry, $layer->ttl);
		}
	}

	/**
	 * Internal serialization mapper
	 *
	 * @param mixed  $data
	 * @param string $method php, json, null
	 */
	protected function serialize($data, $method)
	{
		if ($method === 'php') {
			return serialize($data);
		}

		if ($method === 'json') {
			return json_encode($data);
		}

		return $data;
	}

	/**
	 * Internal unserialization mapper
	 *
	 * @param mixed  $data
	 * @param string $method php, json, null
	 */
	protected function unserialize($data, $method)
	{
		if ($method === 'php') {
			return unserialize($data);
		}

		if ($method === 'json') {
			return json_decode($data, true);
		}

		return $data;
	}
}
