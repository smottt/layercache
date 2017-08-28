<?php

namespace LayerCache\Tests\Cache;

use LayerCache\LayerCache;
use LayerCache\StackBuilder;
use LayerCache\Test\FakeCache;
use LayerCache\Test\FakeSource;
use PHPUnit\Framework\TestCase;

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
class LayerCacheTest extends TestCase
{
    /**
     * @before
     */
    public function clearCache()
    {
		LayerCache::clear();
    }

    /**
     * @test
     */
	public function testForSourceReturnsStackBuilder()
	{
		$source = new FakeSource();
		$b = LayerCache::forSource([$source, 'get']);

		$this->assertInstanceOf(StackBuilder::class, $b);
	}

	/**
	 * @test
	 */
	public function testStack()
	{
		$source = new FakeSource();
		$stack = LayerCache::forSource([$source, 'get'])->toStack('X');
		$this->assertSame($stack, LayerCache::stack('X'));
	}

	/**
	 * @test
	 */
	public function testForSourceCallback()
	{
		$source = $this->createMock(FakeSource::class);
		$source->expects($this->once())
		  ->method('getById')
		  ->with(123)
		  ->willReturn('DATA')
		;
		$source->expects($this->never())->method('mapKey');

		$stack = LayerCache::forSource(
		    [$source, 'getById'],
		    [$source, 'mapKey']
		)->toStack('X');

		$this->assertSame('DATA', $stack->get(123));
	}

	/**
	 * @test
	 */
	public function testHasStack()
	{
		LayerCache::forSource([new FakeSource(), 'get'])->toStack('X');

		$this->assertTrue(LayerCache::hasStack('X'));
		$this->assertFalse(LayerCache::hasStack('Y'));
	}

	/**
	 * @test
	 */
	public function testRegisterCacheSameNameThrowsUp()
	{
		LayerCache::registerCache('mc', new FakeCache());

	    $this->expectException(\RuntimeException::class);

		LayerCache::registerCache('mc', new FakeCache());
	}
}
