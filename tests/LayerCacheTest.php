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

	include_once dirname(__FILE__) . '/../lib/LayerCache.php';
	include_once dirname(__FILE__) . '/mocks.php';

	class LayerCacheTest extends PHPUnit_Framework_TestCase
	{
		function setup()
		{
			LayerCache::clear();
		}

		function testForSourceReturnsStackBuilder()
		{
			$source = new FakeSource;
			$b = LayerCache::forSource(array($source, 'get'));
			$this->assertInstanceOf('LayerCache_StackBuilder', $b);
		}

		function testStack()
		{
			$source = new FakeSource;
			$stack = LayerCache::forSource(array($source, 'get'))->toStack('X');
			$this->assertSame($stack, LayerCache::stack('X'));
		}

		function testForSourceCallback()
		{
			$source = $this->getMock('FakeSource', array('getById', 'mapKey'));
			$source->expects($this->once())->method('getById')->with(123)->will($this->returnValue('DATA'));
			$source->expects($this->never())->method('mapKey');

			$stack = LayerCache::forSource(array($source, 'getById'), array($source, 'mapKey'))->toStack('X');
			$this->assertSame('DATA', $stack->get(123));
		}

		function testHasStack()
		{
			LayerCache::forSource(array(new FakeSource, 'get'))->toStack('X');
			$this->assertTrue(LayerCache::hasStack('X'));
			$this->assertFalse(LayerCache::hasStack('Y'));
		}

		function testRegisterCache()
		{
			$c = new stdClass;
			LayerCache::registerCache('mc', $c);
		}

		/**
		 * @expectedException RuntimeException
		 */
		function testRegisterCacheSameNameThrowsUp()
		{
			LayerCache::registerCache('mc', new StdClass);
			LayerCache::registerCache('mc', new StdClass);
		}
	}
