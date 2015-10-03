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

use LayerCache\LayerCache;
use LayerCache\ObjectMap;

class StackBuilderTest extends \PHPUnit_Framework_TestCase
{
	public function testCreateWithoutCache()
	{
		$source = new FakeSource;
		$stack = $this->getMock('\LayerCache\Stack', [], [[$source, 'get'], [$source, 'normalizeKey'], []]);

		$stack_map = $this->getMock('\LayerCache\ObjectMap');
		$stack_map->expects($this->once())
			->method('set')
			->with('fake', $stack)
		;

		$cache_map = new ObjectMap;

		$pb = $this->getMock('\LayerCache\StackBuilder', ['createStack'], [$stack_map, $cache_map, [$source, 'get'], [$source, 'normalizeKey']]);

		$pb->expects($this->once())
			->method('createStack')
			->with([$source, 'get'], [$source, 'normalizeKey'], [])
			->will($this->returnValue($stack))
		;

		$p = $pb->toStack('fake');
		$this->assertSame($p, $stack);
	}

	public function testAppendCache()
	{
		$source = new FakeSource;
		$stack = $this->getMock('\LayerCache\Stack', [], [[$source, 'get'], [$source, 'normalizeKey'], []]);
		$cache = new FakeCache;
		$layer = new \LayerCache\Layer($cache);

		$stack_map = $this->getMock('\LayerCache\ObjectMap');
		$stack_map->expects($this->once())->
			method('set')->
			with('fake', $stack);

		$cache_map = new ObjectMap;
		$pb = $this->getMock('\LayerCache\StackBuilder', ['createLayer', 'createStack'], [$stack_map, $cache_map, [$source, 'get'], [$source, 'normalizeKey']]);

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
		$source = new FakeSource;
		$stack = $this->getMock('\LayerCache\Stack', [], [[$source, 'get'], [$source, 'normalizeKey'], []]);
		$cache = new FakeCache;
		$layer = new \LayerCache\Layer($cache);

		$stack_map = $this->getMock('\LayerCache\ObjectMap');
		$stack_map->expects($this->once())
			->method('set')
			->with('fake', $stack)
		;

		$cache_map = new ObjectMap;

		$pb = $this->getMock('\LayerCache\StackBuilder', ['createLayer', 'createStack'], [$stack_map, $cache_map, [$source, 'get'], [$source, 'normalizeKey']]);

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
		$source = new FakeSource;
		$stack = $this->getMock('\LayerCache\Stack', [], [[$source, 'get'], [$source, 'normalizeKey'], []]);
		$cache1 = new FakeCache;
		$layer1 = new \LayerCache\Layer($cache1);
		$cache2 = new FakeCache;
		$layer2 = new \LayerCache\Layer($cache2);

		$stack_map = $this->getMock('\LayerCache\ObjectMap');
		$stack_map->expects($this->once())
			->method('set')
			->with('fake', $stack)
		;

		$cache_map = new ObjectMap;

		$pb = $this->getMock('\LayerCache\StackBuilder', ['createLayer', 'createStack'], [$stack_map, $cache_map, [$source, 'get'], [$source, 'normalizeKey']]);

		$pb->expects($this->at(0))
			->method('createLayer')
			->with($cache1)
			->will($this->returnValue($layer1))
		;

		$pb->expects($this->at(1))
			->method('createLayer')
			->with($cache2)
			->will($this->returnValue($layer2))
		;

		$pb->expects($this->at(2))
			->method('createStack')
			->with([$source, 'get'], [$source, 'normalizeKey'], [$layer1, $layer2])
			->will($this->returnValue($stack))
		;

		$p = $pb
			->addLayer($cache1)->withPrefetch(20, 0.1)
			->addLayer($cache2)->withTTL(360, 15)->toStack('fake')
		;

		$this->assertSame($p, $stack);
	}

	public function testAddLayerWithNamedCache()
	{
		$cache = $this->getMock('FakeCache', ['get']);
		$cache->expects($this->once())->method('get')->with('kee')->will($this->returnValue(serialize(['d' => 'DATA', 'e' => time() + 10])));

		LayerCache::registerCache('cache1', $cache);
		LayerCache::forSource([new FakeSource, 'get'])
			->addLayer('cache1')
			->toStack('named')
		;

		$stack = LayerCache::stack('named');
		$v = $stack->get('kee');
		$this->assertSame('DATA', $v);
	}
}
