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
			$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), array());
			$this->assertSame('d', $stack->get(5));
		}
		
		function testSet()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->never())->method('get');
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache1 = $this->getMock('FakeCache', array('get', 'set'));
			$cache1->expects($this->never())->method('get');
			$cache1->expects($this->once())->method('set')->with('k:5', array('d' => 'DATA', 'e' => time() + 7), 7);
			
			$cache2 = $this->getMock('FakeCache', array('get', 'set'));
			$cache2->expects($this->never())->method('get');
			$cache2->expects($this->once())->method('set')->with('k:5', array('d' => 'DATA', 'e' => time() + 15), 15);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), 
				array(
					array('cache' => $cache1, 'ttl' => 7,  'ttl' => 7,  'prefetchTime' => 0, 'prefetchProbability' => 1, 'serializationMethod' => null),
					array('cache' => $cache2, 'ttl' => 15, 'ttl' => 15, 'prefetchTime' => 5, 'prefetchProbability' => 0.5, 'serializationMethod' => null)
				));
			$stack->set(5, 'DATA');
		}
		
		function testWithSingleEmptyCache()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache = $this->getMock('FakeCache', array('get', 'set'));
			$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(null));
			$cache->expects($this->once())->method('set')->with('k:5', array('d' => 'd', 'e' => time() + 7), 7);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'),
				array(array('cache' => $cache, 'ttl' => 7, 'ttl' => 7, 'prefetchTime' => 0, 'prefetchProbability' => 1, 'serializationMethod' => null)));
			$stack->get(5);
		}
		
		function testSerialization()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('d'));
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache = $this->getMock('FakeCache', array('get', 'set'));
			$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(null));
			$cache->expects($this->once())->method('set')->with('k:5', json_encode(array("d" => "d", "e" => time() + 7)), 7);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'),
				array(array('cache' => $cache, 'ttl' => 7, 'ttl_empty' => 7, 'prefetchTime' => 0, 'prefetchProbability' => 1, 'serializationMethod' => 'json')));
			$stack->get(5);
		}
		
		function testWithSingleFullCache()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->never())->method('get');
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache = $this->getMock('FakeCache', array('get'));
			$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('e' => time() + 15, 'd' => 'DATA')));
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), 
				array(array('cache' => $cache, 'ttl' => 0, 'ttl_empty' => 0, 'prefetchTime' => 10, 'prefetchProbability' => 1, 'serializationMethod' => null)));
			$stack->get(5);
		}
		
		function testWithSinglePrefetchCache()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('NEW DATA'));
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache = $this->getMock('FakeCache', array('get', 'set'));
			$cache->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('e' => time() + 10, 'd' => 'OLD DATA')));
			$cache->expects($this->once())->method('set')->with('k:5', array('e' => time() + 60, 'd' => 'NEW DATA'), 60);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), 
				array(array('cache' => $cache, 'ttl' => 60, 'ttl_empty' => 60, 'prefetchTime' => 15, 'prefetchProbability' => 1, 'serializationMethod' => null)));
			$this->assertSame('NEW DATA', $stack->get(5));
		}
		
		function testOneCacheTimeouts()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->never())->method('get');
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache1 = $this->getMock('FakeCache', array('get', 'set'));
			$cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('e' => time() + 20, 'd' => 'DATA 1')));
			$cache1->expects($this->never())->method('set');
			
			$cache2 = $this->getMock('FakeCache', array('get', 'set'));
			$cache2->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('e' => time() + 10, 'd' => 'DATA 2')));
			$cache2->expects($this->once())->method('set')->with('k:5', array('e' => time() + 30, 'd' => 'DATA 1'), 30);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), 
				array(
					array('cache' => $cache1, 'ttl' => 60, 'ttl_empty' => 60, 'prefetchTime' => 15, 'prefetchProbability' => 1, 'serializationMethod' => null),
					array('cache' => $cache2, 'ttl' => 30, 'ttl_empty' => 30, 'prefetchTime' => 15, 'prefetchProbability' => 1, 'serializationMethod' => null)
					));
			
			$this->assertSame('DATA 1', $stack->get(5));
		}
		
		function testTwoCachesTimeout()
		{
			$source = $this->getMock('FakeSource', array('get', 'normalizeKey'));
			$source->expects($this->once())->method('get')->with(5)->will($this->returnValue('NEW DATA'));
			$source->expects($this->once())->method('normalizeKey')->with(5)->will($this->returnValue('k:5'));
			
			$cache1 = $this->getMock('FakeCache', array('get', 'set'));
			$cache1->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('e' => time() + 5, 'd' => 'DATA 1')));
			$cache1->expects($this->once())->method('set')->with('k:5', array('e' => time() + 60, 'd' => 'NEW DATA'), 60);
			
			$cache2 = $this->getMock('FakeCache');
			$cache2->expects($this->once())->method('get')->with('k:5')->will($this->returnValue(array('e' => time() + 10, 'd' => 'DATA 2')));
			$cache2->expects($this->once())->method('set')->with('k:5', array('e' => time() + 30, 'd' => 'NEW DATA'), 30);
			
			$stack = new LayerCache_Stack(array($source, 'get'), array($source, 'normalizeKey'), 
				array(
					array('cache' => $cache1, 'ttl' => 60, 'ttl_empty' => 60, 'prefetchTime' => 15, 'prefetchProbability' => 1, 'serializationMethod' => null),
					array('cache' => $cache2, 'ttl' => 30, 'ttl_empty' => 30, 'prefetchTime' => 15, 'prefetchProbability' => 1, 'serializationMethod' => null)
				));
			
			$this->assertSame('NEW DATA', $stack->get(5));
		}
	}
	
