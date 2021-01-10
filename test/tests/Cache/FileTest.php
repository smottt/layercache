<?php

namespace LayerCache\Tests\Cache;

use PHPUnit\Framework\TestCase;

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
 * @package Tests
 */
class FileTest extends TestCase
{
	/** @var \LayerCache\Cache\File */
	protected $cache;

	/**
	 * @before
	 */
	public function setUp_Cache()
	{
		$this->cache = new \LayerCache\Cache\File(LAYERCACHE_TEST_TMP_DIR);
	}

	/**
	 * @before
	 */
	public function cleanTempDir()
	{
		foreach (new \DirectoryIterator(LAYERCACHE_TEST_TMP_DIR) as $file) {
			if ($file->isFile() && !$file->isDot()) {
				unlink($file->getPathName());
			}
		}
	}

	/**
	 * @test
	 */
	public function testGetEmpty()
	{
		$this->assertNull($this->cache->get('test'));
	}

	/**
	 * @test
	 */
	public function testSetAndGet()
	{
		$this->assertNull($this->cache->get('test'));
		$this->cache->set('test', 'DATA', 10);
		$this->assertSame('DATA', $this->cache->get('test'));
	}

	/**
	 * @test
	 */
	public function testSetAndGetComplexStructure()
	{
		$this->assertNull($this->cache->get('test'));
		$o = new \stdClass();
		$o->z = 34;
		$data = ['x', $o, ['a' => 12]];
		$this->cache->set('test', serialize($data), 10);
		$this->assertEquals($data, unserialize($this->cache->get('test')));
	}

	/**
	 * @test
	 */
	public function testTTL()
	{
		$this->assertNull($this->cache->get('test'));
		$this->cache->set('test', 'DATA', 1);
		$this->assertSame('DATA', $this->cache->get('test'));
		sleep(2);
		$this->assertNull($this->cache->get('test'));
	}

	/**
	 * @test
	 */
	public function testSetAndDel()
	{
		$key  = 'test';
		$data = 'SOME DATA';

		$this->assertNull($this->cache->get($key));

		$this->cache->set($key, $data, 10);

		$this->assertSame($data, $this->cache->get($key));
		$this->assertTrue($this->cache->del($key));
		$this->assertNull($this->cache->get($key));
	}
}
