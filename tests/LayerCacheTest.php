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
	
	class LayerCacheTest extends PHPUnit_Framework_TestCase
	{
		function testForReaderReturnsBuilder()
		{
			$source = new StdClass;
			$b = LayerCache::forSource($source);
			$this->assertType('LayerCache_StackBuilder', $b);
		}
		
		function testStack()
		{
			$source = new StdClass;
			$stack = LayerCache::forSource($source)->toStack('X');
			$this->assertSame($stack, LayerCache::stack('X'));
		}
	}
	