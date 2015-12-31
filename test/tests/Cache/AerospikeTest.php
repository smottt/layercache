<?php

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

class AerospikeTest extends \PHPUnit_Framework_TestCase
{
	protected $aerospike;

	/**
	 * @before
	 */
	public function setUp_Aerospike()
	{
		$this->aerospike = $this->getMock('\Aerospike');
	}

	/**
	 * @param  string $ns
	 * @param  string $set
	 * @return \LayerCache\Cache\Aerospike
	 */
	protected function newCache($ns, $set)
	{
		return new \LayerCache\Cache\Aerospike($this->aerospike, $ns, $set);
	}

	/**
	 * @test
	 */
	public function testGetEmpty()
	{
		$key = 'test';
		$ns  = 'test-ns';
		$set = 'test-set';

		$this->aerospike
			->expects($this->once())
			->method('initKey')
			->with($ns, $set, $key)
			->willReturn([$ns, $set, $key])
		;

		$this->aerospike
			->expects($this->once())
			->method('get')
			->willReturn(\Aerospike::ERR_RECORD_NOT_FOUND)
		;

		$this->assertNull($this->newCache($ns, $set)->get($key));
	}

	/**
	 * @test
	 */
	public function testSetAndGet()
	{
		$key  = 'test';
		$ns   = 'test-ns';
		$set  = 'test-set';
		$data = 'SOME DATA';

		$this->aerospike
			->expects($this->atLeastOnce())
			->method('initKey')
			->willReturn([$ns, $set, $key])
		;

		$getCalls = 0;

		$this->aerospike
			->expects($this->exactly(2))
			->method('get')
			->willReturnCallback(function (
				array $getKey,
				array &$record
			) use ($key, $data, &$getCalls) {
				$getCalls++;

				if ($getCalls === 1) {
					return \Aerospike::ERR_RECORD_NOT_FOUND;
				}

				$record = ['bins' => [$key => $data]];
				return \Aerospike::OK;
			})
		;

		$this->aerospike
			->expects($this->once())
			->method('put')
			->with([$ns, $set, $key], [$key => $data], 7)
		;

		$cache = $this->newCache($ns, $set);

		$this->assertNull($cache->get($key));
		$cache->set($key, $data, 7);
		$this->assertSame($data, $cache->get($key));
	}

	/**
	 * @test
	 */
	public function testSetAndGetComplexStructure()
	{
		$key  = 'test';
		$ns   = 'test-ns';
		$set  = 'test-set';
		$data = ['x', (object)['z' => 34], ['a' => 12]];

		$this->aerospike
			->expects($this->atLeastOnce())
			->method('initKey')
			->willReturn([$ns, $set, $key])
		;

		$getCalls = 0;

		$this->aerospike
			->expects($this->exactly(2))
			->method('get')
			->willReturnCallback(function (
				array $getKey,
				array &$record
			) use ($key, $data, &$getCalls) {
				$getCalls++;

				if ($getCalls === 1) {
					return \Aerospike::ERR_RECORD_NOT_FOUND;
				}

				$record = ['bins' => [$key => $data]];
				return \Aerospike::OK;
			})
		;

		$this->aerospike
			->expects($this->once())
			->method('put')
			->with([$ns, $set, $key], [$key => $data], 7)
		;

		$cache = $this->newCache($ns, $set);

		$this->assertNull($cache->get($key));
		$cache->set($key, $data, 7);
		$this->assertEquals($data, $cache->get($key));
	}

	/**
	 * @test
	 */
	public function testSetAndDel()
	{
		$key  = 'test';
		$ns   = 'test-ns';
		$set  = 'test-set';
		$data = 'SOME DATA';

		$this->aerospike
			->expects($this->atLeastOnce())
			->method('initKey')
			->willReturn([$ns, $set, $key])
		;

		$getCalls = 0;

		$this->aerospike
			->expects($this->exactly(2))
			->method('get')
			->willReturnCallback(function (
				array $getKey,
				array &$record
			) use ($key, $data, &$getCalls) {
				$getCalls++;

				if ($getCalls === 1) {
					$record = ['bins' => [$key => $data]];
					return \Aerospike::OK;
				}

				return \Aerospike::ERR_RECORD_NOT_FOUND;
			})
		;

		$this->aerospike->expects($this->never())->method('put');

		$this->aerospike
			->expects($this->once())
			->method('remove')
			->with([$ns, $set, $key])
			->willReturn(\Aerospike::OK)
		;

		$cache = $this->newCache($ns, $set);

		$this->assertSame($data, $cache->get($key));
		$cache->del($key);
		$this->assertNull($cache->get($key));
	}
}
