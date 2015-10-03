<?php
	/**
	Copyright 2009-2011 Gasper Kozak

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

	include_once dirname(__FILE__) . '/../../lib/LayerCache.php';

	class LocalTest extends PHPUnit_Framework_TestCase
	{
		function testUnlimited()
		{
			$c = new LayerCache_Cache_Local();
			$c->set('a', 'AAAAAAAAAAA');
			$c->set('b', 'AAAAAAAAAAA');
			$c->set('c', 'AAAAAAAAAAA');
			$c->set('d', 'AAAAAAAAAAA');
			$this->assertSame('AAAAAAAAAAA', $c->get('a'));
			$this->assertSame('AAAAAAAAAAA', $c->get('d'));
			$this->assertSame('AAAAAAAAAAA', $c->get('c'));
			$this->assertSame('AAAAAAAAAAA', $c->get('b'));
		}

		function testCountLimit()
		{
			$c = new LayerCache_Cache_Local(0, 2);
			$this->assertSame(null, $c->get('a'));

			$c->set('a', 'A');
			$this->assertSame('A', $c->get('a'));

			$c->set('x', 'X');
			$c->set('y', 'Y');
			$c->set('z', 'Z');
			$this->assertSame(null, $c->get('a'));
			$this->assertSame(null, $c->get('x'));
			$this->assertSame('Y', $c->get('y'));
			$this->assertSame('Z', $c->get('z'));

			$c->set('z', 'Z2');
			$this->assertSame('Y', $c->get('y'));
			$this->assertSame('Z2', $c->get('z'));
		}

		function testSizeLimit()
		{
			$c = new LayerCache_Cache_Local(10, 0);
			$this->assertSame(null, $c->get('a'));

			$c->set('a', 'AAAAA');
			$this->assertSame('AAAAA', $c->get('a'));

			$c->set('x', 'XXXXX');
			$c->set('y', 'YYY');
			$this->assertSame(null, $c->get('a'));
			$this->assertSame('XXXXX', $c->get('x'));
			$this->assertSame('YYY', $c->get('y'));

			$c->set('y', 'YYYYYYY');
			$this->assertSame(null, $c->get('x'));
			$this->assertSame('YYYYYYY', $c->get('y'));

			$c->set('a', 'AAAAA');
			$this->assertSame('AAAAA', $c->get('a'));
			$c->set('b', 'BBBBB');
			$this->assertSame('AAAAA', $c->get('a'));
			$this->assertSame('BBBBB', $c->get('b'));
			$c->set('c', 'CCCCCC');
			$this->assertSame(null, $c->get('a'));
			$this->assertSame(null, $c->get('b'));
			$this->assertSame('CCCCCC', $c->get('c'));

			$c->set('y', 'YYYYYYYYYYYYYYYY');
			$this->assertSame(null, $c->get('y'));
		}

		function testEvictsLeastRecent()
		{
			$c = new LayerCache_Cache_Local(0, 2);
			$c->set('a', 'A');
			$c->set('b', 'B');
			$this->assertSame('B', $c->get('b'));
			$this->assertSame('A', $c->get('a'));
			$c->set('c', 'C');
			$this->assertSame('A', $c->get('a'));
			$this->assertSame(null, $c->get('b'));
			$this->assertSame('C', $c->get('c'));
		}

		function testStoreArraySerialize()
		{
			$c = new LayerCache_Cache_Local(40, 0);
			$c->set('a', array('AAAAA'));
			$c->set('b', array('BBBBB'));
			$this->assertSame(null, $c->get('a'));
			$this->assertSame(array('BBBBB'), $c->get('b'));
		}
	}

