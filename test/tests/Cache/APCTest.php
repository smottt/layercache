<?php

/**
Copyright 2009-2015 Gasper Kozak

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

@package Tests
**/

class APCTest extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		if (!extension_loaded('apc') || !ini_get('apc.enable_cli')) {
			$this->markTestSkipped('APC extension not available.');
		}

		apc_delete('test');
	}

	public function testSetAndGet()
	{
		$key = 'test-simple-' . rand(100, 999);

		$cache = new \LayerCache\Cache\APC();

		$this->assertSame(null, $cache->get($key));

		$data = 'SOME DATA';

		$cache->set($key, $data, 1);

		$this->assertSame($data, apc_fetch($key));
		$this->assertSame($data, $cache->get($key));
	}

	public function testSetAndGetComplexStructure()
	{
		$key = 'test-complex-' . rand(100, 999);

		$cache = new \LayerCache\Cache\APC();
		$this->assertSame(null, $cache->get($key));

		$data = ['x', ['a' => 12]];

		$cache->set($key, $data, 10);
		$this->assertEquals($data, $cache->get($key));
	}
}
