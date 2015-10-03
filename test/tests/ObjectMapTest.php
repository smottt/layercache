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

use LayerCache\ObjectMap;

class ObjectMapTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException \RuntimeException
	 */
	public function testNoStack()
	{
		$map = new ObjectMap;
		$map->get('Inexistent');
	}

	public function testSetGet()
	{
		$map   = new ObjectMap;
		$stack = new \stdClass;
		$map->set('MyStack', $stack);
		$this->assertSame($stack, $map->get('MyStack'));
	}
}
