<?php

use LayerCache\Cache\CachingLayer;
use LayerCache\Test\FakeSource;
use LayerCache\Trace;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Copyright 2009-2021 Gasper Kozak
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

class StackTest extends TestCase
{
    /** @var MockObject|FakeSource */
    protected $source;

    /** @var MockObject|CachingLayer */
    protected $cache1;

    /** @var MockObject|CachingLayer */
    protected $cache2;

    /**
     * @before
     */
    public function setUpSource()
    {
        $this->source = $this->createMock(FakeSource::class);
    }

    /**
     * @before
     */
    public function setUpCaches()
    {
        $this->cache1 = $this->createMock(CachingLayer::class);
        $this->cache2 = $this->createMock(CachingLayer::class);
    }

	protected function createLayer(
		$cache,
		$ttl,
		$ttlEmpty,
		$prefetchTime,
		$prefetchProbability,
		$serializationMethod
	) {
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
		$this->source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], []);
		$this->assertSame('d', $stack->get(5));
	}

	public function testSet()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->never())->method('get');
		$this->cache1->expects($this->once())->method('set')->with('k:5', ['d' => 'DATA', 'e' => time() + 7], 7);

		$this->cache2->expects($this->never())->method('get');
		$this->cache2->expects($this->once())->method('set')->with('k:5', ['d' => 'DATA', 'e' => time() + 15], 15);

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 7, 7, 0, 1, null),
			$this->createLayer($this->cache2, 15, 15, 5, 0.5, null)
		));

		$stack->set(5, 'DATA');
	}

	public function testWithSingleEmptyCache()
	{
		$this->source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(null));
		$this->cache1->expects($this->once())->method('set')->with('k:5', ['d' => 'd', 'e' => time() + 7], 7);

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 7, 7, 0, 1, null)
		));

		$stack->get(5);
	}

	public function testCacheTTL()
	{
		$this->source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['d' => 'd', 'e' => time() - 1]));
		$this->cache1->expects($this->once())->method('set')->with('k:5', ['d' => 'd', 'e' => time() + 7], 7);

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 7, 7, 0, 1, null)
		));

		$stack->get(5);
	}

	public function testSerialization()
	{
		$this->source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(null));
		$this->cache1->expects($this->once())->method('set')->with('k:5', json_encode(['d' => 'd', 'e' => time() + 7]), 7);

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 7, 7, 0, 1, 'json')
		));

		$stack->get(5);
	}

	public function testWithSingleFullCache()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 15, 'd' => 'DATA']));

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 0, 0, 10, 1, null)
		));

		$stack->get(5);
	}

	public function testWithSinglePrefetchCache()
	{
		$this->source->expects($this->once())->method('get')->with(5)->will($this->returnValue('NEW DATA'));
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 10, 'd' => 'OLD DATA']));
		$this->cache1->expects($this->once())->method('set')->with('k:5', ['e' => time() + 60, 'd' => 'NEW DATA'], 60);

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 60, 60, 15, 1, null)
		));

		$this->assertSame('NEW DATA', $stack->get(5));
	}

	/**
	 * @group bugs
	 */
	public function testPrefetchShouldOnlyExecuteWhenNonZero()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time(), 'd' => 'OLD DATA']));
		$this->cache1->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 0, 0, 0, 1, null)
		));

		$this->assertSame('OLD DATA', $stack->get(5));
	}

	/**
	 * @group bugs
	 */
	public function testPrefetchShouldOnlyExecuteWhenTTLNonZero()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time(), 'd' => 'OLD DATA']));
		$this->cache1->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 0, 0, 5, 1, null)
		));

		$this->assertSame('OLD DATA', $stack->get(5));
	}

	public function testOneCacheTimeouts()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 20, 'd' => 'DATA 1']));
		$this->cache1->expects($this->never())->method('set');

		$this->cache2->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 10, 'd' => 'DATA 2']));
		$this->cache2->expects($this->once())->method('set')->with('k:5', ['e' => time() + 30, 'd' => 'DATA 1'], 30);

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 60, 60, 15, 1, null),
			$this->createLayer($this->cache2, 30, 30, 15, 1, null)
		));

		$this->assertSame('DATA 1', $stack->get(5));
	}

	public function testTwoCachesTimeout()
	{
		$this->source->expects($this->once())->method('get')->with(5)->will($this->returnValue('NEW DATA'));
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 5, 'd' => 'DATA 1']));
		$this->cache1->expects($this->once())->method('set')->with('k:5', ['e' => time() + 60, 'd' => 'NEW DATA'], 60);

		$this->cache2->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 10, 'd' => 'DATA 2']));
		$this->cache2->expects($this->once())->method('set')->with('k:5', ['e' => time() + 30, 'd' => 'NEW DATA'], 30);

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 60, 60, 15, 1, null),
			$this->createLayer($this->cache2, 30, 30, 15, 1, null)
		));

		$this->assertSame('NEW DATA', $stack->get(5));
	}

	public function testSetAllCaches()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->never())->method('get');
		$this->cache1->expects($this->once())->method('set')->with('k:5', serialize(['d' => 'Data', 'e' => time() + 60]), 60);

		$this->cache2->expects($this->never())->method('get');
		$this->cache2->expects($this->once())->method('set')->with('k:5', serialize(['d' => 'Data', 'e' => time() + 30]), 30);

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 60, 60, 0, 1, 'php'),
			$this->createLayer($this->cache2, 30, 30, 0, 1, 'php')
		));

		$stack->set(5, 'Data');
	}

	public function testSetAllCachesNoKeyCallback()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->never())->method('normalizeKey');

		$this->cache1->expects($this->never())->method('get');
		$this->cache1->expects($this->once())->method('set')->with('Kee', ['e' => time() + 60, 'd' => 'Data'], 60);

		$this->cache2->expects($this->never())->method('get');
		$this->cache2->expects($this->once())->method('set')->with('Kee', ['e' => time() + 30, 'd' => 'Data'], 30);

		$stack = new \LayerCache\Stack([$this->source, 'get'], null, array(
			$this->createLayer($this->cache1, 60, 60, 0, 1, null),
			$this->createLayer($this->cache2, 30, 30, 0, 1, null)
		));

		$stack->set('Kee', 'Data');
	}

	/**
	 * @group trace
	 */
	public function testTrace()
	{
		$this->source->expects($this->once())->method('get')->with(5)->will($this->returnValue('DATA'));
		$this->source->expects($this->exactly(2))->method('normalizeKey')->with(5)->will($this->returnValue('key:5'));

		$this->cache1 = new \LayerCache\Cache\Local();

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 7, 7, 0, 1, null)
		));

		$t = null;

		$stack->trace($t)->get(5);
		$this->assertInstanceOf(Trace::class, $t);

		/** @var Trace $t */
        $this->assertIsInt($t->time);
		$this->assertSame(5, $t->key);
		$this->assertSame('key:5', $t->flat_key);
		$this->assertSame(1, $t->cache_count);
		$this->assertIsInt($t->rand);
		$this->assertEquals(1, count($t->reads));
		$this->assertSame(['key' => 5, 'data' => 'DATA'], $t->source);
		$this->assertEquals(1, count($t->writes));

		$stack->trace($t)->get(5);
		$this->assertInstanceOf(Trace::class, $t);
		$this->assertIsInt($t->time);
		$this->assertSame(5, $t->key);
		$this->assertSame('key:5', $t->flat_key);
		$this->assertSame(1, $t->cache_count);
		$this->assertIsInt($t->rand);
		$this->assertEquals(1, count($t->reads));
		$this->assertSame([], $t->source);
		$this->assertEquals(0, count($t->writes));
	}

	/**
	 * @group trace
	 */
	public function testTraceWorksOnlyOnce()
	{
	    $this->source->expects($this->exactly(2))
            ->method('normalizeKey')
            ->willReturnMap([
                [5, 'key:5'],
                [7, 'key:7'],
            ])
        ;

	    $this->source->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [5, 'DATA=5'],
                [7, 'DATA=7']
            ])
        ;

		$this->cache1 = new \LayerCache\Cache\Local();

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 7, 7, 0, 1, null)
		));

		// with trace
		$t = null;

		$stack->trace($t)->get(5);
		$this->assertInstanceOf(Trace::class, $t);

        /** @var Trace $t */
		$this->assertIsInt($t->time);
		$this->assertSame(5, $t->key);
		$this->assertSame('key:5', $t->flat_key);
		$this->assertSame(1, $t->cache_count);
		$this->assertIsInt($t->rand);
		$this->assertEquals(1, count($t->reads));
		$this->assertSame(['key' => 5, 'data' => 'DATA=5'], $t->source);
		$this->assertEquals(1, count($t->writes));

		// without trace ($t keeps previous trace info)
		$stack->get(7);
		$this->assertInstanceOf(Trace::class, $t);
		$this->assertIsInt($t->time);
		$this->assertSame(5, $t->key);
		$this->assertSame('key:5', $t->flat_key);
		$this->assertSame(1, $t->cache_count);
		$this->assertIsInt($t->rand);
		$this->assertEquals(1, count($t->reads));
		$this->assertSame(['key' => 5, 'data' => 'DATA=5'], $t->source);
		$this->assertEquals(1, count($t->writes));
	}

	/**
	 * @group bugs
	 */
	public function testEmptyArrayCanBeStoredAsValidData()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 5, 'd' => []]));
		$this->cache1->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 10, 10, 0, 1, null)
		));

		$this->assertSame([], $stack->get(5));
	}

	/**
	 * @group bugs
	 */
	public function testZeroCanBeStoredAsValidData()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 5, 'd' => 0]));
		$this->cache1->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 10, 10, 0, 1, null)
		));

		$this->assertSame(0, $stack->get(5));
	}

	/**
	 * @group bugs
	 */
	public function testFalseCanBeStoredAsValidData()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 5, 'd' => false]));
		$this->cache1->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 10, 10, 0, 1, null)
		));

		$this->assertSame(false, $stack->get(5));
	}

	/**
	 * @group bugs
	 */
	public function testNullCanBeStoredAsValidData()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(['e' => time() + 5, 'd' => null]));
		$this->cache1->expects($this->never())->method('set');

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 10, 10, 0, 1, null)
		));

		$this->assertSame(null, $stack->get(5));
	}

	public function testDeleteData()
	{
		$this->source->expects($this->never())->method('get');
		$this->source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));

		$this->cache1->expects($this->never())->method('get');
		$this->cache1->expects($this->never())->method('set');
		$this->cache1->expects($this->once())->method('del')->with('k:5');

		$this->cache2->expects($this->never())->method('get');
		$this->cache2->expects($this->never())->method('set');
		$this->cache2->expects($this->once())->method('del')->with('k:5');

		$stack = new \LayerCache\Stack([$this->source, 'get'], [$this->source, 'normalizeKey'], array(
			$this->createLayer($this->cache1, 7, 7, 0, 1, null),
			$this->createLayer($this->cache2, 15, 15, 5, 0.5, null)
		));

		$stack->del(5);
	}
}
