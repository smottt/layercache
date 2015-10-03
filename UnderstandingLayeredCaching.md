# Layered caching #

## The principle ##
Layered caching is well-known and often used concept. The idea is to have several caches stacked together. Usually, the first cache is the fastest and smallest, and each next cache is bigger and slower. The reason for that is that fast cache is usually more expensive (CPU cache), so it comes in smaller quantities.

Because the first level cache is the smallest, only the most accessed data is stored in it, other data gets flushed. This ensures that the access to the most needed data is always fast.

## Cache sizes and TTL ##
Imagine a very simple layout: a single web-server with APC and 1 Memcache node, with equal space for cache.

In this layout, setting item's TTL in APC higher than TTL of the same item in Memcache, doesn't make sense, because when that item in APC will timeout, so will the same item in Memcache. Then item will have to be read from the source, will be written to Memcache and APC, and it will timeout again. In this case, it's usually better to use a single cache layer.

With a more complex layout (several APC and Memcache nodes), things change. When an item time-outs in one of the APC nodes, it may still be present in the Memcache, because there's a chance that the same item already time-outed on another APC node and wrote to Memcache.

Also, size of the caches can affect the fetches. Even with the same TTL, items may be evicted from APC earlier, if APC cache size is smaller, and space was needed to cache other items.

The most common configuration is to use shorter TTL on faster caches, which are usually smaller, too. But this may differ depending on your infrastructure.

## How layers affect the life-time of data ##
If a cache miss occurs, the request is issued on the next cache layer. If the item is present there, it's written in the former cache with the specified TTL. This effectively means that the actual life-time of an item is (in the worst case) the sum of all TTLs, specified in the stack. Keep that in mind when deciding on the TTL values.

Here's the image that shows the timeline and the values in cache.
![http://layercache.googlecode.com/svn/wiki/images/multi-layer-cache-ttl.png](http://layercache.googlecode.com/svn/wiki/images/multi-layer-cache-ttl.png)

## Best practice ##
It's probably best to stack caches in decreasing order by size and TTL and increasing order by speed.

See [Cache Performance Comparison](http://www.mysqlperformanceblog.com/2006/08/09/cache-performance-comparison/) on MySQL Performance Blog for cache speed comparison. As you can read in the comments there, the performance varies depending on the architecture, so don't forget to profile your system. APC seems to cause problems with many writes (it's very fast for reading), so make sure you use [prefetch](Prefetch.md) feature.