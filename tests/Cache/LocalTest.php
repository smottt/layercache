<?php
	/**
	Copyright 2009 Gasper Kozak
	
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
	
	require_once 'PHPUnit/Framework.php';
	include_once dirname(__FILE__) . '/../../lib/LayerCache.php';
	
	class LocalTest extends PHPUnit_Framework_TestCase
	{
		function testUnlimited()
		{
			$c = new LayerCache_Cache_Local();
			$c->write('a', 'AAAAAAAAAAA');
			$c->write('b', 'AAAAAAAAAAA');
			$c->write('c', 'AAAAAAAAAAA');
			$c->write('d', 'AAAAAAAAAAA');
			$this->assertSame('AAAAAAAAAAA', $c->read('a'));
			$this->assertSame('AAAAAAAAAAA', $c->read('d'));
			$this->assertSame('AAAAAAAAAAA', $c->read('c'));
			$this->assertSame('AAAAAAAAAAA', $c->read('b'));
		}
		
		function testCountLimit()
		{
			$c = new LayerCache_Cache_Local(0, 2);
			$this->assertSame(null, $c->read('a'));
			
			$c->write('a', 'A');
			$this->assertSame('A', $c->read('a'));
			
			$c->write('x', 'X');
			$c->write('y', 'Y');
			$c->write('z', 'Z');
			$this->assertSame(null, $c->read('a'));
			$this->assertSame(null, $c->read('x'));
			$this->assertSame('Y', $c->read('y'));
			$this->assertSame('Z', $c->read('z'));
			
			$c->write('z', 'Z2');
			$this->assertSame('Y', $c->read('y'));
			$this->assertSame('Z2', $c->read('z'));
		}
		
		function testSizeLimit()
		{
			$c = new LayerCache_Cache_Local(10, 0);
			$this->assertSame(null, $c->read('a'));
			
			$c->write('a', 'AAAAA');
			$this->assertSame('AAAAA', $c->read('a'));
			
			$c->write('x', 'XXXXX');
			$c->write('y', 'YYY');
			$this->assertSame(null, $c->read('a'));
			$this->assertSame('XXXXX', $c->read('x'));
			$this->assertSame('YYY', $c->read('y'));
			
			$c->write('y', 'YYYYYYY');
			$this->assertSame(null, $c->read('x'));
			$this->assertSame('YYYYYYY', $c->read('y'));
			
			$c->write('a', 'AAAAA');
			$this->assertSame('AAAAA', $c->read('a'));
			$c->write('b', 'BBBBB');
			$this->assertSame('AAAAA', $c->read('a'));
			$this->assertSame('BBBBB', $c->read('b'));
			$c->write('c', 'CCCCCC');
			$this->assertSame(null, $c->read('a'));
			$this->assertSame(null, $c->read('b'));
			$this->assertSame('CCCCCC', $c->read('c'));
			
			$c->write('y', 'YYYYYYYYYYYYYYYY');
			$this->assertSame(null, $c->read('y'));
		}
		
		function testEvictsLeastRecent()
		{
			$c = new LayerCache_Cache_Local(0, 2);
			$c->write('a', 'A');
			$c->write('b', 'B');
			$this->assertSame('B', $c->read('b'));
			$this->assertSame('A', $c->read('a'));
			$c->write('c', 'C');
			$this->assertSame('A', $c->read('a'));
			$this->assertSame(null, $c->read('b'));
			$this->assertSame('C', $c->read('c'));
		}
	}
	