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

class StackTest extends \PHPUnit_Framework_TestCase
{
	protected function createLayer(
		$cache,
		$ttl,
		$ttlEmpty,
		$prefetchTime,
		$prefetchProbability,
		$serializationMethod
	)
	{
		$layer = new \LayerCache\Layer($cache);
		$layer->ttl = $ttl;
		$layer->ttl_empty = $ttlEmpty;
		$layer->prefetchTime = $prefetchTime;
		$layer->prefetchProbability = $prefetchProbability;
		$layer->serializationMethod = $serializationMethod;

		return $layer;
	}

	public function testWithoutCache()
	{
		$source = $this->getMock('FakeSource', ['get']);
		$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], []);
		$this->assertSame('d', $stack->get(5));
	}

	public function testSet()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache1 = $this->getMock('FakeCache', ['get', 'set']);
		$cache1->expects($this->never())->method('get');
		$cache1->expects($this->once())->method('set')->with('k:5', ['d' => 'DATA', 'e' => time() + 7], 7);

		$cache2 = $this->getMock('FakeCache', ['get', 'set']);
		$cache2->expects($this->never())->method('get');
		$cache2->expects($this->once())->method('set')->with('k:5', ['d' => 'DATA', 'e' => time() + 15], 15);

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache1, 7, 7, 0, 1, null),
			$this->createLayer($cache2, 15, 15, 5, 0.5, null)
		));

		$stack->set(5, 'DATA');
	}

	public function testWithSingleEmptyCache()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get', 'set']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(null));
		$cache->expects($this->once())->method('set')->with('k:5', ['d' => 'd', 'e' => time() + 7], 7);

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 7, 7, 0, 1, null)
		));

		$stack->get(5);
	}

	public function testCacheTTL()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get', 'set']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['d' => 'd', 'e' => time() - 1]));
		$cache->expects($this->once())->method('set')->with('k:5', ['d' => 'd', 'e' => time() + 7], 7);

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 7, 7, 0, 1, null)
		));

		$stack->get(5);
	}

	public function testSerialization()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get', 'set']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(null));
		$cache->expects($this->once())->method('set')->with('k:5', json_encode(['d' => 'd', 'e' => time() + 7]), 7);

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 7, 7, 0, 1, 'json')
		));

		$stack->get(5);
	}

	public function testWithSingleFullCache()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 15, 'd' => 'DATA']));

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 0, 0, 10, 1, null)
		));

		$stack->get(5);
	}

	public function testWithSinglePrefetchCache()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('NEW DATA'));
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get', 'set']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 10, 'd' => 'OLD DATA']));
		$cache->expects($this->once())->method('set')->with('k:5', ['e' => time() + 60, 'd' => 'NEW DATA'], 60);

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 60, 60, 15, 1, null)
		));

		$this->assertSame('NEW DATA', $stack->get(5));
	}

	/**
	 * @group bugs
	 */
	public function testPrefetchShouldOnlyExecuteWhenNonZero()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get', 'set']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time(), 'd' => 'OLD DATA']));
		$cache->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 0, 0, 0, 1, null)
		));

		$this->assertSame('OLD DATA', $stack->get(5));
	}

	/**
	 * @group bugs
	 */
	public function testPrefetchShouldOnlyExecuteWhenTTLNonZero()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get', 'set']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time(), 'd' => 'OLD DATA']));
		$cache->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 0, 0, 5, 1, null)
		));

		$this->assertSame('OLD DATA', $stack->get(5));
	}

	public function testOneCacheTimeouts()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache1 = $this->getMock('FakeCache', ['get', 'set']);
		$cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 20, 'd' => 'DATA 1']));
		$cache1->expects($this->never())->method('set');

		$cache2 = $this->getMock('FakeCache', ['get', 'set']);
		$cache2->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 10, 'd' => 'DATA 2']));
		$cache2->expects($this->once())->method('set')->with('k:5', ['e' => time() + 30, 'd' => 'DATA 1'], 30);

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache1, 60, 60, 15, 1, null),
			$this->createLayer($cache2, 30, 30, 15, 1, null)
		));

		$this->assertSame('DATA 1', $stack->get(5));
	}

	public function testTwoCachesTimeout()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('NEW DATA'));
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache1 = $this->getMock('FakeCache', ['get', 'set']);
		$cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 5, 'd' => 'DATA 1']));
		$cache1->expects($this->once())->method('set')->with('k:5', ['e' => time() + 60, 'd' => 'NEW DATA'], 60);

		$cache2 = $this->getMock('FakeCache');
		$cache2->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 10, 'd' => 'DATA 2']));
		$cache2->expects($this->once())->method('set')->with('k:5', ['e' => time() + 30, 'd' => 'NEW DATA'], 30);

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache1, 60, 60, 15, 1, null),
			$this->createLayer($cache2, 30, 30, 15, 1, null)
		));

		$this->assertSame('NEW DATA', $stack->get(5));
	}

	public function testSetAllCaches()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache1 = $this->getMock('FakeCache', ['get', 'set']);
		$cache1->expects($this->never())->method('get');
		$cache1->expects($this->once())->method('set')->with('k:5', serialize(['d' => 'Data', 'e' => time() + 60]), 60);

		$cache2 = $this->getMock('FakeCache');
		$cache2->expects($this->never())->method('get');
		$cache2->expects($this->once())->method('set')->with('k:5', serialize(['d' => 'Data', 'e' => time() + 30]), 30);

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache1, 60, 60, 0, 1, 'php'),
			$this->createLayer($cache2, 30, 30, 0, 1, 'php')
		));

		$stack->set(5, 'Data');
	}

	public function testSetAllCachesNoKeyCallback()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->never())->method('normalizeKey');

		$cache1 = $this->getMock('FakeCache', ['get', 'set']);
		$cache1->expects($this->never())->method('get');
		$cache1->expects($this->once())->method('set')->with('Kee', ['e' => time() + 60, 'd' => 'Data'], 60);

		$cache2 = $this->getMock('FakeCache');
		$cache2->expects($this->never())->method('get');
		$cache2->expects($this->once())->method('set')->with('Kee', ['e' => time() + 30, 'd' => 'Data'], 30);

		$stack = new \LayerCache\Stack([$source, 'get'], null, array(
			$this->createLayer($cache1, 60, 60, 0, 1, null),
			$this->createLayer($cache2, 30, 30, 0, 1, null)
		));

		$stack->set('Kee', 'Data');
	}

	/**
	 * @group trace
	 */
	public function testTrace()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('DATA'));
		$source->expects($this->exactly(2))->method('normalizeKey')->with(5)->will($this->returnValue('key:5'));

		$cache = new \LayerCache\Cache\Local();

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 7, 7, 0, 1, null)
		));

		$t = null;

		$stack->trace($t)->get(5);
		$this->assertInstanceOf('\LayerCache\Trace', $t);
		$this->assertInternalType('int', $t->time);
		$this->assertSame(5, $t->key);
		$this->assertSame('key:5', $t->flat_key);
		$this->assertSame(1, $t->cache_count);
		$this->assertInternalType('int', $t->rand);
		$this->assertEquals(1, count($t->reads));
		$this->assertSame(['key' => 5, 'data' => 'DATA'], $t->source);
		$this->assertEquals(1, count($t->writes));

		$stack->trace($t)->get(5);
		$this->assertInstanceOf('\LayerCache\Trace', $t);
		$this->assertInternalType('int', $t->time);
		$this->assertSame(5, $t->key);
		$this->assertSame('key:5', $t->flat_key);
		$this->assertSame(1, $t->cache_count);
		$this->assertInternalType('int', $t->rand);
		$this->assertEquals(1, count($t->reads));
		$this->assertSame([], $t->source);
		$this->assertEquals(0, count($t->writes));
	}

	/**
	 * @group trace
	 */
	public function testTraceWorksOnlyOnce()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->at(0))->method('normalizeKey')->with(5)->will($this->returnValue('key:5'));
		$source->expects($this->at(1))->method('get')->with(5)->will($this->returnValue('DATA=5'));
		$source->expects($this->at(2))->method('normalizeKey')->with(7)->will($this->returnValue('key:7'));
		$source->expects($this->at(3))->method('get')->with(7)->will($this->returnValue('DATA=7'));

		$cache = new \LayerCache\Cache\Local();

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 7, 7, 0, 1, null)
		));

		// with trace
		$t = null;

		$stack->trace($t)->get(5);
		$this->assertInstanceOf('\LayerCache\Trace', $t);
		$this->assertInternalType('int', $t->time);
		$this->assertSame(5, $t->key);
		$this->assertSame('key:5', $t->flat_key);
		$this->assertSame(1, $t->cache_count);
		$this->assertInternalType('int', $t->rand);
		$this->assertEquals(1, count($t->reads));
		$this->assertSame(['key' => 5, 'data' => 'DATA=5'], $t->source);
		$this->assertEquals(1, count($t->writes));

		// without trace ($t keeps previous trace info)
		$stack->get(7);
		$this->assertInstanceOf('\LayerCache\Trace', $t);
		$this->assertInternalType('int', $t->time);
		$this->assertSame(5, $t->key);
		$this->assertSame('key:5', $t->flat_key);
		$this->assertSame(1, $t->cache_count);
		$this->assertInternalType('int', $t->rand);
		$this->assertEquals(1, count($t->reads));
		$this->assertSame(['key' => 5, 'data' => 'DATA=5'], $t->source);
		$this->assertEquals(1, count($t->writes));
	}

	/**
	 * @group bugs
	 */
	public function testEmptyArrayCanBeStoredAsValidData()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get', 'set']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 5, 'd' => []]));
		$cache->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 10, 10, 0, 1, null)
		));

		$this->assertSame([], $stack->get(5));
	}

	/**
	 * @group bugs
	 */
	public function testZeroCanBeStoredAsValidData()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get', 'set']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 5, 'd' => 0]));
		$cache->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 10, 10, 0, 1, null)
		));

		$this->assertSame(0, $stack->get(5));
	}

	/**
	 * @group bugs
	 */
	public function testFalseCanBeStoredAsValidData()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get', 'set']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 5, 'd' => false]));
		$cache->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 10, 10, 0, 1, null)
		));

		$this->assertSame(false, $stack->get(5));
	}

	/**
	 * @group bugs
	 */
	public function testNullCanBeStoredAsValidData()
	{
		$source = $this->getMock('FakeSource', ['get', 'normalizeKey']);
		$source->expects($this->never())->method('get');
		$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$cache = $this->getMock('FakeCache', ['get', 'set']);
		$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 5, 'd' => null]));
		$cache->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$source, 'get'], [$source, 'normalizeKey'], array(
			$this->createLayer($cache, 10, 10, 0, 1, null)
		));

		$this->assertSame(null, $stack->get(5));
	}
}
