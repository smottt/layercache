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
	
	class CacheStackBuilderTest extends PHPUnit_Framework_TestCase
	{
		function testCreateWithoutCache()
		{
			$reader = new FakeReader;
			$stack = $this->getMock('LayerCache_Stack', array(), array($reader, array()));
			
			$map = $this->getMock('LayerCache_StackMap');
			$map->expects($this->once())->
				method('set')->
				with($this->equalTo('fake', $stack));
			
			$pb = $this->getMock('LayerCache_StackBuilder', array('stackFactory'), array($map, $reader));
			
			$pb->expects($this->once())->
				method('stackFactory')->
				with($reader, array())->
				will($this->returnValue($stack));
			
			$p = $pb->toStack('fake');
			$this->assertSame($p, $stack);
		}
		
		function testAppendCache()
		{
			$reader = new FakeReader;
			$stack = $this->getMock('LayerCache_Stack', array(), array($reader, array()));
			$cache = new FakeCache;
			
			$map = $this->getMock('LayerCache_StackMap');
			$map->expects($this->once())->
				method('set')->
				with($this->equalTo('fake', $stack));
			
			$pb = $this->getMock('LayerCache_StackBuilder', array('stackFactory'), array($map, $reader));
			
			$pb->expects($this->once())->
				method('stackFactory')->
				with($reader, array(array('cache' => $cache, 'ttl' => 0, 'prefetchTime' => 0, 'prefetchProbability' => 1)))->
				will($this->returnValue($stack));
			
			$p = $pb->addCache($cache)->toStack('fake');
			$this->assertSame($p, $stack);
		}
		
		function testAppendCacheWithPrefetch()
		{
			$reader = new FakeReader;
			$stack = $this->getMock('LayerCache_Stack', array(), array($reader, array()));
			$cache = new FakeCache;
			
			$map = $this->getMock('LayerCache_StackMap');
			$map->expects($this->once())->
				method('set')->
				with($this->equalTo('fake', $stack));
			
			$pb = $this->getMock('LayerCache_StackBuilder', array('stackFactory'), array($map, $reader));
			
			$pb->expects($this->once())->
				method('stackFactory')->
				with($reader, array(array('cache' => $cache, 'ttl' => 120, 'prefetchTime' => 20, 'prefetchProbability' => 0.1)))->
				will($this->returnValue($stack));
			
			$p = $pb->addCache($cache)->withTTL(120)->withPrefetch(20, 0.1)->toStack('fake');
			$this->assertSame($p, $stack);
		}
		
		function testAppendTwoCaches()
		{
			$reader = new FakeReader;
			$stack = $this->getMock('LayerCache_Stack', array(), array($reader, array()));
			$cache1 = new FakeCache;
			$cache2 = new FakeCache;
			
			$map = $this->getMock('LayerCache_StackMap');
			$map->expects($this->once())->
				method('set')->
				with($this->equalTo('fake', $stack));
			
			$pb = $this->getMock('LayerCache_StackBuilder', array('stackFactory'), array($map, $reader));
			
			$pb->expects($this->once())->
				method('stackFactory')->
				with($reader, 
				array(
					array(
						'cache' => $cache1, 
						'ttl' => 0,
						'prefetchTime' => 20,
						'prefetchProbability' => 0.1
					), 
					array(
						'cache' => $cache2,
						'ttl' => 360, 
						'prefetchTime' => 0,
						'prefetchProbability' => 1
					)))->
				will($this->returnValue($stack));
			
			$p = $pb->
				addCache($cache1)->withPrefetch(20, 0.1)->
				addCache($cache2)->withTTL(360)->toStack('fake');
			$this->assertSame($p, $stack);
		}
	}
	