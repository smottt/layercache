<?php
	/**
	Copyright 2009-2011 Gasper Kozak
	
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
	
	include_once dirname(__FILE__) . '/../../lib/LayerCache.php';
	
	class FileTest extends PHPUnit_Framework_TestCase
	{
		function setUp()
		{
			$dir = realpath(dirname(__FILE__) . '/../temp/');
			foreach (new DirectoryIterator($dir) as $file)
				if ($file->isFile() && !$file->isDot())
					unlink($file->getPathName());
			$this->cache = new LayerCache_Cache_File($dir);
		}
		
		function testGetEmpty()
		{
			$this->assertSame(null, $this->cache->get('test'));
		}
		
		function testSetAndGet()
		{
			$this->assertSame(null, $this->cache->get('test'));
			$this->cache->set('test', 'DATA', 10);
			$this->assertSame('DATA', $this->cache->get('test'));
		}
		
		function testSetAndGetComplexStructure()
		{
			$this->assertSame(null, $this->cache->get('test'));
			$o = new StdClass;
			$o->z = 34;
			$data = array('x', $o, array('a' => 12));
			$this->cache->set('test', serialize($data), 10);
			$this->assertEquals($data, unserialize($this->cache->get('test')));
		}
		
		function testTTL()
		{
			$this->assertSame(null, $this->cache->get('test'));
			$this->cache->set('test', 'DATA', 1);
			$this->assertSame('DATA', $this->cache->get('test'));
			sleep(2);
			$this->assertSame(null, $this->cache->get('test'));
		}
	}
	
