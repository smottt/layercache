<?php

namespace LayerCache\Tests\Cache;

use LayerCache\Cache\APCu;
use PHPUnit\Framework\TestCase;

/**
 * Copyright 2009-2017 Gasper Kozak
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

class APCuTest extends TestCase
{
	/** @var \PHPUnit_Framework_MockObject_MockObject|APCu */
	protected $cache;

	/** @var string|null */
	private $apcKey;

    /**
     * @before
     */
	public function checkExtensionAvailability()
    {
        if (!extension_loaded('apcu') || !ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APCu extension not available.');
        }
    }

	/**
	 * @before
	 */
	public function setUpCache()
	{
		$this->cache = new APCu();

	}

    /**
     * @after
     */
	public function cleanup()
    {
        if ($this->apcKey) {
            apcu_delete($this->apcKey);
        }
    }

	/**
	 * @test
	 */
	public function testSetAndGet()
	{
		$this->apcKey = 'test-simple-' . rand(100, 999);

		$this->assertNull($this->cache->get($this->apcKey));

		$data = 'SOME DATA';

		$this->cache->set($this->apcKey, $data, 1);

		$this->assertSame($data, apcu_fetch($this->apcKey));
		$this->assertSame($data, $this->cache->get($this->apcKey));
	}

	/**
	 * @test
	 */
	public function testSetAndGetComplexStructure()
	{
		$this->apcKey = 'test-complex-' . rand(100, 999);

		$this->assertNull($this->cache->get($this->apcKey));

		$data = ['x', ['a' => 12]];

		$this->cache->set($this->apcKey, $data, 10);
		$this->assertEquals($data, $this->cache->get($this->apcKey));
	}

	/**
	 * @test
	 */
	public function testSetAndDel()
	{
		$this->apcKey = 'test-del-' . rand(100, 999);

		$this->assertNull($this->cache->get($this->apcKey));

		$data = 'SOME DATA';

		$this->cache->set($this->apcKey, $data, 10);

		$this->assertSame($data, apcu_fetch($this->apcKey));
		$this->assertSame($data, $this->cache->get($this->apcKey));

		$this->assertTrue($this->cache->del($this->apcKey));

		$this->assertFalse(apcu_exists($this->apcKey));
		$this->assertNull($this->cache->get($this->apcKey));
	}
}
