<?php

namespace LayerCache\Tests;

use LayerCache\Cache\CachingLayer;
use LayerCache\Layer;
use LayerCache\LayerCache;
use LayerCache\ObjectMap;
use LayerCache\Test\FakeCache;
use LayerCache\Test\FakeSource;
use PHPUnit\Framework\TestCase;
use LayerCache\Stack;
use LayerCache\StackBuilder;

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
class StackBuilderTest extends TestCase
{
    /**
     * @test
     */
	public function testCreateWithoutCache()
	{
		$source = new FakeSource();

		$stack = $this->getMockBuilder(Stack::class)
            ->setConstructorArgs([
                [$source, 'get'],
                [$source, 'normalizeKey'],
                []
            ])
            ->getMock()
        ;

		$stackMap = $this->createMock(ObjectMap::class);
		$stackMap->expects($this->once())
			->method('set')
			->with('fake', $stack)
		;

		/** @var \PHPUnit_Framework_MockObject_MockObject|StackBuilder $pb */
		$pb = $this->getMockBuilder(StackBuilder::class)
            ->setMethods(['createStack'])
            ->setConstructorArgs([
                $stackMap,
                new ObjectMap(),
                [$source, 'get'], [$source, 'normalizeKey']
            ])
            ->getMock()
        ;

		$pb->expects($this->once())
			->method('createStack')
			->with([$source, 'get'], [$source, 'normalizeKey'], [])
			->willReturn($stack)
		;

		$p = $pb->toStack('fake');
		$this->assertSame($p, $stack);
	}

	/**
	 * @test
	 */
	public function testAppendCache()
	{
		$source = new FakeSource();
		$stack = $this->getMockBuilder(Stack::class)
            ->setConstructorArgs([
                [$source, 'get'],
                [$source, 'normalizeKey'],
                []
            ])
            ->getMock()
        ;

		$cache = new FakeCache();
		$layer = new Layer($cache);

		$stackMap = $this->createMock(ObjectMap::class);
		$stackMap->expects($this->once())
            ->method('set')
            ->with('fake', $stack)
        ;

		/** @var \PHPUnit_Framework_MockObject_MockObject|StackBuilder $pb */
		$pb = $this->getMockBuilder(StackBuilder::class)
            ->setMethods(['createLayer', 'createStack'])
            ->setConstructorArgs([
                $stackMap,
                new ObjectMap(),
                [$source, 'get'],
                [$source, 'normalizeKey']
            ])
            ->getMock()
        ;

		$pb->expects($this->once())
			->method('createLayer')
			->with($cache)
			->will($this->returnValue($layer))
		;

		$pb->expects($this->once())
			->method('createStack')
			->with([$source, 'get'], [$source, 'normalizeKey'], [$layer])
			->will($this->returnValue($stack))
		;

		$p = $pb->addLayer($cache)->serializeWith('json')->toStack('fake');
		$this->assertSame($p, $stack);
	}

	public function testAppendCacheWithPrefetch()
	{
		$source = new FakeSource();
		$stack = $this->getMockBuilder(Stack::class)
            ->setConstructorArgs([
                [$source, 'get'],
                [$source, 'normalizeKey']
            ])
            ->getMock()
        ;

		$cache = new FakeCache();
		$layer = new Layer($cache);

		$stackMap = $this->createMock(ObjectMap::class);
		$stackMap->expects($this->once())
			->method('set')
			->with('fake', $stack)
		;

		/** @var \PHPUnit_Framework_MockObject_MockObject|StackBuilder $pb */
		$pb = $this->getMockBuilder(StackBuilder::class)
            ->setMethods(['createLayer', 'createStack'])
            ->setConstructorArgs([
                $stackMap,
                new ObjectMap(),
                [$source, 'get'],
                [$source, 'normalizeKey']
            ])
            ->getMock()
        ;

		$pb->expects($this->once())
			->method('createLayer')
			->with($cache)
			->will($this->returnValue($layer))
		;

		$pb->expects($this->once())
			->method('createStack')
			->with([$source, 'get'], [$source, 'normalizeKey'], [$layer])
			->will($this->returnValue($stack))
		;

		$p = $pb->addLayer($cache)->withTTL(120)->withPrefetch(20, 0.1)->toStack('fake');
		$this->assertSame($p, $stack);
	}

	public function testAppendTwoCaches()
	{
		$source = new FakeSource();
		$stack = $this->getMockBuilder(Stack::class)
            ->setConstructorArgs([
                [$source, 'get'],
                [$source, 'normalizeKey']
            ])
            ->getMock()
        ;
		$cache1 = new FakeCache();
		$layer1 = new Layer($cache1);
		$cache2 = new FakeCache();
		$layer2 = new Layer($cache2);

		$stackMap = $this->createMock(ObjectMap::class);
		$stackMap->expects($this->once())
			->method('set')
			->with('fake', $stack)
		;

		/** @var \PHPUnit_Framework_MockObject_MockObject|StackBuilder $pb */
		$pb = $this->getMockBuilder(StackBuilder::class)
            ->setMethods(['createLayer', 'createStack'])
            ->setConstructorArgs([
                $stackMap,
                new ObjectMap(),
                [$source, 'get'],
                [$source, 'normalizeKey']
            ])
            ->getMock()
        ;

		$pb->expects($this->at(0))
			->method('createLayer')
			->with($cache1)
            ->willReturn($layer1)
		;

		$pb->expects($this->at(1))
			->method('createLayer')
			->with($cache2)
            ->willReturn($layer2)
		;

		$pb->expects($this->at(2))
			->method('createStack')
			->with([$source, 'get'], [$source, 'normalizeKey'], [$layer1, $layer2])
            ->willReturn($stack)
		;

		$p = $pb
			->addLayer($cache1)->withPrefetch(20, 0.1)
			->addLayer($cache2)->withTTL(360, 15)->toStack('fake')
		;

		$this->assertSame($p, $stack);
	}

	public function testAddLayerWithNamedCache()
	{
	    /** @var \PHPUnit_Framework_MockObject_MockObject|CachingLayer $cache */
	    $cache = $this->createMock(CachingLayer::class);
		$cache->expects($this->once())
            ->method('get')
            ->with('kee')
            ->willReturn(serialize(['d' => 'DATA', 'e' => time() + 10]))
        ;

		LayerCache::registerCache('cache1', $cache);
		LayerCache::forSource([new FakeSource(), 'get'])
			->addLayer('cache1')
			->toStack('named')
		;

		$stack = LayerCache::stack('named');
		$v = $stack->get('kee');
		$this->assertSame('DATA', $v);
	}
}
