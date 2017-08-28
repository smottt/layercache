<?php

namespace LayerCache\Tests;

use LayerCache\ObjectMap;
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
class ObjectMapTest extends TestCase
{
	/**
	 * @test
	 */
	public function testNoStack()
	{
	    $this->expectException(\RuntimeException::class);

		$map = new ObjectMap;
		$map->get('Inexistent');
	}

	/**
	 * @test
	 */
	public function testSetGet()
	{
		$map   = new ObjectMap;
		$stack = new \stdClass;
		$map->set('MyStack', $stack);
		$this->assertSame($stack, $map->get('MyStack'));
	}
}
