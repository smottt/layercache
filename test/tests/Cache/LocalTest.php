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

class LocalTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 */
	public function testUnlimited()
	{
		$c = new \LayerCache\Cache\Local();
		$c->set('a', 'AAAAAAAAAAA', null);
		$c->set('b', 'AAAAAAAAAAA', null);
		$c->set('c', 'AAAAAAAAAAA', null);
		$c->set('d', 'AAAAAAAAAAA', null);
		$this->assertSame('AAAAAAAAAAA', $c->get('a'));
		$this->assertSame('AAAAAAAAAAA', $c->get('d'));
		$this->assertSame('AAAAAAAAAAA', $c->get('c'));
		$this->assertSame('AAAAAAAAAAA', $c->get('b'));
	}

	/**
	 * @test
	 */
	public function testCountLimit()
	{
		$c = new \LayerCache\Cache\Local(0, 2);
		$this->assertNull($c->get('a'));

		$c->set('a', 'A', null);
		$this->assertSame('A', $c->get('a'));

		$c->set('x', 'X', null);
		$c->set('y', 'Y', null);
		$c->set('z', 'Z', null);
		$this->assertNull($c->get('a'));
		$this->assertNull($c->get('x'));
		$this->assertSame('Y', $c->get('y'));
		$this->assertSame('Z', $c->get('z'));

		$c->set('z', 'Z2', null);
		$this->assertSame('Y', $c->get('y'));
		$this->assertSame('Z2', $c->get('z'));
	}

	/**
	 * @test
	 */
	public function testSizeLimit()
	{
		$c = new \LayerCache\Cache\Local(10, 0);
		$this->assertNull($c->get('a'));

		$c->set('a', 'AAAAA', null);
		$this->assertSame('AAAAA', $c->get('a'));

		$c->set('x', 'XXXXX', null);
		$c->set('y', 'YYY', null);
		$this->assertNull($c->get('a'));
		$this->assertSame('XXXXX', $c->get('x'));
		$this->assertSame('YYY', $c->get('y'));

		$c->set('y', 'YYYYYYY', null);
		$this->assertNull($c->get('x'));
		$this->assertSame('YYYYYYY', $c->get('y'));

		$c->set('a', 'AAAAA', null);
		$this->assertSame('AAAAA', $c->get('a'));
		$c->set('b', 'BBBBB', null);
		$this->assertSame('AAAAA', $c->get('a'));
		$this->assertSame('BBBBB', $c->get('b'));
		$c->set('c', 'CCCCCC', null);
		$this->assertNull($c->get('a'));
		$this->assertNull($c->get('b'));
		$this->assertSame('CCCCCC', $c->get('c'));

		$c->set('y', 'YYYYYYYYYYYYYYYY', null);
		$this->assertNull($c->get('y'));
	}

	/**
	 * @test
	 */
	public function testEvictsLeastRecent()
	{
		$c = new \LayerCache\Cache\Local(0, 2);
		$c->set('a', 'A', null);
		$c->set('b', 'B', null);
		$this->assertSame('B', $c->get('b'));
		$this->assertSame('A', $c->get('a'));
		$c->set('c', 'C', null);
		$this->assertSame('A', $c->get('a'));
		$this->assertNull($c->get('b'));
		$this->assertSame('C', $c->get('c'));
	}

	/**
	 * @test
	 */
	public function testStoreArraySerialize()
	{
		$c = new \LayerCache\Cache\Local(40, 0);
		$c->set('a', ['AAAAA'], null);
		$c->set('b', ['BBBBB'], null);
		$this->assertNull($c->get('a'));
		$this->assertSame(['BBBBB'], $c->get('b'));
	}

	/**
	 * @test
	 */
	public function testSetAndDel()
	{
		$key  = 'test/v1';
		$data = 'SOME DATA';

		$cache = new \LayerCache\Cache\Local();
		$cache->set($key, $data, 10);

		$this->assertSame($data, $cache->get($key));

		$cache->del($key);

		$this->assertNull($cache->get($key));
	}
}
