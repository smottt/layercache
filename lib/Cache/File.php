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
 */

namespace LayerCache\Cache;

use LayerCache\Cache\CachingLayer;

/**
 * @package LayerCache
 *
 * @author Gasper Kozak
 * @author Metod N <metod@simpel.si.
 */
class File implements CachingLayer
{
	/** @var string */
	protected $dir;

	/**
	 * Construct a new File cache layer object.
	 *
	 * @param  string $dir
	 * @throws \RuntimeException
	 */
	public function __construct($dir)
	{
		if (substr($dir, -1) !== DIRECTORY_SEPARATOR) {
			$dir .= DIRECTORY_SEPARATOR;
		}

		if (!file_exists($dir) || !is_dir($dir)) {
			throw new \RuntimeException('Directory does not exist.');
		}

		if (!is_writable($dir)) {
			throw new \RuntimeException('Directory is not writable.');
		}

		$this->dir = $dir;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return mixed
	 */
	public function get($key)
	{
		$fname = $this->generateFilePath($key);

		if (!file_exists($fname)) {
			return null;
		}

		$data = file($fname);

		if (time() > $data[0]) {
			return null;
		}

		return $data[1];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public function set($key, $data, $ttl)
	{
		$fname    = $this->generateFilePath($key);
		$tempName = $fname . '-' . mt_rand(10000, 99999);

		file_put_contents($tempName, time() + $ttl . "\n" . $data);

		return rename($tempName, $fname);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public function del($key)
	{
		$fname = $this->generateFilePath($key);

		if (!file_exists($fname)) {
			return true;
		}

		return @unlink($fname);
	}

	/**
	 * Generate the cache file path for a particular key.
	 *
	 * @param  string $key
	 * @return string
	 */
	protected function generateFilePath($key)
	{
		return $this->dir . sha1($key);
	}
}
