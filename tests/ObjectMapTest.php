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

	class ObjectMapTest extends \PHPUnit_Framework_TestCase
	{
		/**
		 * @expectedException RuntimeException
		 */
		function testNoStack()
		{
			$map = new LayerCache_ObjectMap;
			$map->get('Inexistent');
		}

		function testSetGet()
		{
			$map = new LayerCache_ObjectMap;
			$stack = new \stdClass;
			$map->set('MyStack', $stack);
			$this->assertSame($stack, $map->get('MyStack'));
		}
	}
