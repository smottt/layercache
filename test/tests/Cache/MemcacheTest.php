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
 * @package Tests
 */

class MemcacheTest extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		if (!extension_loaded('memcache')) {
			$this->markTestSkipped('Memcache extension not available.');
		}
	}

	public function testGetEmpty()
	{
		$mc = $this->getMock('Memcache', ['get']);
		$mc->expects($this->once())->method('get')->with('test')->will($this->returnValue(false));
		$cache = new \LayerCache\Cache\Memcache($mc);
		$this->assertSame(null, $cache->get('test'));
	}

	function testSetAndGet()
	{
		$mc = $this->getMock('Memcache', ['get', 'set']);

		$mc->expects($this->at(0))->method('get')->with('test')->will($this->returnValue(false));
		$mc->expects($this->at(1))->method('set')->with('test', 'DATA', false, 10);
		$mc->expects($this->at(2))->method('get')->with('test')->will($this->returnValue('DATA'));

		$cache = new \LayerCache\Cache\Memcache($mc);
		$this->assertSame(null, $cache->get('test'));
		$cache->set('test', 'DATA', 10);
		$this->assertSame('DATA', $cache->get('test'));
	}

	function testSetAndGetComplexStructure()
	{
		$mc = $this->getMock('Memcache', ['get', 'set']);

		$o = new stdClass;
		$o->z = 34;
		$data = ['x', $o, ['a' => 12]];

		$mc->expects($this->at(0))->method('get')->with('test')->will($this->returnValue(false));
		$mc->expects($this->at(1))->method('set')->with('test', $data, 7, 10);
		$mc->expects($this->at(2))->method('get')->with('test')->will($this->returnValue(serialize($data)));

		$cache = new \LayerCache\Cache\Memcache($mc, 7);
		$this->assertSame(null, $cache->get('test'));
		$cache->set('test', $data, 10);
		$this->assertEquals($data, unserialize($cache->get('test')));
	}
}
