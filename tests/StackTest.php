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
	include_once dirname(__FILE__) . '/../lib/LayerCache.php';
	include_once dirname(__FILE__) . '/mocks.php';
	
	class CacheStackTest extends PHPUnit_Framework_TestCase
	{
		function testWithoutCache()
		{
			$source = $this->getMock('FakeSource', array('get'));
			$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('data'));
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), array());
			$this->assertSame('data', $stack->get(5));
		}
		
		function testSet()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->never())->method('get');
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache1 = $this->getMock('FakeCache', array('get', 'set'));
			$cache1->expects($this->never())->method('get');
			$cache1->expects($this->once())->method('set')->with('k:5', array('data' => 'DATA', 'expires' => time() + 7), 7);
			
			$cache2 = $this->getMock('FakeCache', array('get', 'set'));
			$cache2->expects($this->never())->method('get');
			$cache2->expects($this->once())->method('set')->with('k:5', array('data' => 'DATA', 'expires' => time() + 15), 15);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), 
				array(
					array('cache' => $cache1, 'ttl' => 7, 'prefetchTime' => 0, 'prefetchProbability' => 1),
					array('cache' => $cache2, 'ttl' => 15, 'prefetchTime' => 5, 'prefetchProbability' => 0.5)
				));
			$stack->set(5, 'DATA');
		}
		
		function testWithSingleEmptyCache()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('data'));
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache = $this->getMock('FakeCache', array('get', 'set'));
			$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(null));
			$cache->expects($this->once())->method('set')->with('k:5', array('data' => 'data', 'expires' => time() + 7), 7);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'),
				array(array('cache' => $cache, 'ttl' => 7, 'prefetchTime' => 0, 'prefetchProbability' => 1)));
			$stack->get(5);
		}
		
		function testWithSingleFullCache()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->never())->method('get');
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache = $this->getMock('FakeCache', array('get'));
			$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('expires' => time() + 15, 'data' => 'DATA')));
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), 
				array(array('cache' => $cache, 'ttl' => 0, 'prefetchTime' => 10, 'prefetchProbability' => 1)));
			$stack->get(5);
		}
		
		function testWithSinglePrefetchCache()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('NEW DATA'));
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache = $this->getMock('FakeCache', array('get', 'set'));
			$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('expires' => time() + 10, 'data' => 'OLD DATA')));
			$cache->expects($this->once())->method('set')->with('k:5', array('expires' => time() + 60, 'data' => 'NEW DATA'), 60);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), 
				array(array('cache' => $cache, 'ttl' => 60, 'prefetchTime' => 15, 'prefetchProbability' => 1)));
			$this->assertSame('NEW DATA', $stack->get(5));
		}
		
		function testOneCacheTimeouts()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->never())->method('get');
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache1 = $this->getMock('FakeCache', array('get', 'set'));
			$cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('expires' => time() + 20, 'data' => 'DATA 1')));
			$cache1->expects($this->never())->method('set');
			
			$cache2 = $this->getMock('FakeCache', array('get', 'set'));
			$cache2->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('expires' => time() + 10, 'data' => 'DATA 2')));
			$cache2->expects($this->once())->method('set')->with('k:5', array('expires' => time() + 30, 'data' => 'DATA 1'), 30);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), 
				array(
					array('cache' => $cache1, 'ttl' => 60, 'prefetchTime' => 15, 'prefetchProbability' => 1),
					array('cache' => $cache2, 'ttl' => 30, 'prefetchTime' => 15, 'prefetchProbability' => 1)
					));
			
			$this->assertSame('DATA 1', $stack->get(5));
		}
		
		function testTwoCachesTimeout()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('NEW DATA'));
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache1 = $this->getMock('FakeCache', array('get', 'set'));
			$cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('expires' => time() + 5, 'data' => 'DATA 1')));
			$cache1->expects($this->once())->method('set')->with('k:5', array('expires' => time() + 60, 'data' => 'NEW DATA'), 60);
			
			$cache2 = $this->getMock('FakeCache');
			$cache2->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('expires' => time() + 10, 'data' => 'DATA 2')));
			$cache2->expects($this->once())->method('set')->with('k:5', array('expires' => time() + 30, 'data' => 'NEW DATA'), 30);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), 
				array(
					array('cache' => $cache1, 'ttl' => 60, 'prefetchTime' => 15, 'prefetchProbability' => 1),
					array('cache' => $cache2, 'ttl' => 30, 'prefetchTime' => 15, 'prefetchProbability' => 1)
				));
			
			$this->assertSame('NEW DATA', $stack->get(5));
		}
	}
	