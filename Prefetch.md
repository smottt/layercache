# Prefetching the data #

## The problem ##
When an item in the cache is too old, it gets removed. Likewise, if the cache is too small, some items are evicted prematurely, in order to make place for newer items. At that moment, all reads for such items will return null, and the data will be requested from the source (i.e. the database). The data will then be written to the caches, so it'll be available from there for subsequent reads.

But, if the number of requests for a certain item is high, several clients will detect a cache miss at the same time, and all of them will read the data from the source. For example, if there is 100 requests for an item per second, a few ten (up to a hundred in this example) identical queries will be issued to the database. This can put a lot of load on the database, especially if you consider that this could happen to several items at once.

Database's query cache can help you here, but not necessarily. MySQL cache, for example, has [a few problems](http://www.mysqlperformanceblog.com/2006/07/27/mysql-query-cache/).

So, how to avoid the database being slammed?

## The solution ##
The solution is simple and effective - prefetch. This means that an item will be prefetched _before_ its time is up, before it should be treated as stale. For prefetch, you can define two parameters: time and probability. Time defines how many seconds before an item is stale should prefetch be considered, and probability defines how probable is prefetch to happen.

To explain by example with an item that has TTL 360 (5 minutes), and with prefetch set to 60 seconds with 0.01 probability. When the item in the cache is within the last 60 seconds of its lifetime, each request will decide whether or not should it go fetch the item from the source. Probability 0.01 means 1 in 100, so this means only 1/100 of concurrent requests will go fetch the item, others will continue reading the item from the cache. When the item is written back by that 1/100 requests, its TTL will again be 360s, so no request will trigger prefetch for another 4 minutes.

To complicate things a bit: if an item is requested 10 times a second, this means that (if PHP's [mt\_rand()](http://www.php.net/mt_rand) behaves) in the first 10-second period of the last 60 seconds of 360-second lifetime of the item, only 1 request will fetch the item. All the others will just read from the cache as usual.

This results in a much less requests to the source, i.e. the database. Instead of _every_ request, only a few of them will fetch the item from the database. However, prefetch only solves the problem for items being "naturally evicted" (by being too old, considering TTL). Items that are prematurely evicted in order to make more space in the cache will still be slammed, because there is no way of detecting when a certain item will be evicted.

## An example ##
To enable prefetch, you have to specify it for each cache you add to a stack.
```
LayerCache::
  forSource(new SomeDataSource)->
  addLayer(new LayerCache_Cache_Memcache($memcache))->withTTL(360)->withPrefetch(60, 0.01)->
  addLayer(new LayerCache_Cache_APC)->withTTL(60)->withPrefetch(10, 0.02)->
  toStack('data');
```

## Use only one prefetch per stack ##
If you decide to use [prefetch](Prefetch.md), you may want to only use it on one cache in the stack - preferably the last (the last added via addLayer, the first that is accessed). While prefetch works fine on any number of layers, it's the probability that renders it unusable on every cache below the first cache with prefetch. Because requests for an item are already reduced by the first prefetch, which reduces the number of reads on the lower caches, it's very probable that items won't be prefetched on lower caches, because not enough requests will be made. It would be the same as with only one prefetch - the last one added via _withPrefetch_.

## Choosing time and probability values ##
These values are important for performance. If sub-optimal values are used, this can result in either under-prefetching or over-prefetching.

An example will clear things up. Imagine an item is requested 10 times per second. Reading the item from the database takes 2 seconds. You put the item in a cache with TTL=600 (10 minutes). Now let's see how prefetch works for the following values:
  * no prefetch
    * an item will be cached for 10 minutes
    * when it times-out, 10 requests will re-read the item within the first second
    * because reading the item takes 2 seconds, the item still won't be present in the cache after the first second
    * another 10 reads will be made to the source in the second second.
    * ideally, we succeed in writing the item to the cache after two seconds
    * all next requests will read the item from the cache
    * we got 20 cache misses which all resulted in database read (20 queries)
    * this is called database-slamming
  * time=60, probability=0.5
    * the last 60 seconds of the item's life-time (last minute before eviction), prefetch will be considered with 0.5 probability
    * each request will be prefetching the item with probability 0.5
    * so, 5 of 10 requests will read the item from the database in the first second of the prefetch period (second 541)
    * the next second (542), another 5 requests will re-read the item from database
    * the item will be written in cache with a new TTL, so prefetch is done
    * we got 10 misses and database reads in two seconds, and only 540s (9 minutes) of effective cache
    * this is called over-prefetching, because too many prefetches happened, and the feature behaves almost exactly like having no cache
  * time=60, probability=0.001
    * in second #541, 10 requests will consider prefetch with probability 0.001
    * the probability is very low, so no prefetch will occur
    * same for each second
    * within 60 seconds and 10 requests per second, 600 requests are made
    * the probability for any item going for prefetch is 600 × 0.001 = 0.6, so prefetch will likely happen only for 60% of cache timeouts, which is bad
    * this is called under-prefetching, because prefetch doesn't happen often enough
  * time=30, probability=0.03
    * within 30 seconds, 300 requests are made, and each has probability of 0.03 to go for prefetch
    * 300 × 0.03 = 9 requests are likely to go for prefetch within last 30 seconds
    * most likely these won't happen at the same time (9 in 30 seconds is 1 every 3.3 seconds)
    * so only one would go and read the item from the database
    * the item will then be fresh, so prefetch won't be considered for another 9.5 minutes
    * we got ideal prefetch: only one (or a few) queries issued and the item stays fresh for 9.5 minutes before prefetch occurs

As you see, the key to choosing proper time and probability values is how many requests per second are made. You can use this equation to select the values.

  * prefetchTime × requestsPerSecond × prefetchProbability = prefetchCount
  * prefetchProbability = prefetchCount / (prefetchTime × requestsPerSecond)

Ideal values (empirical):
  * prefetchTime: between 15 and 120 seconds
  * requestsPerSecond: your input
  * prefetchProbability: calculated
  * prefetchCount: between 5 and 100

Where prefetchCount / prefetchTime (prefetches per second) should be as small as possible to reduce simultaneous reads (for small prefetchTime, aim for lower prefetchCount).

Not all items of the same class have equal requestsPerSecond. Some people are prettier than others, so their profile is accessed more often. The most often accessed items make the biggest difference, so choose your parameters according to the most accessed item's requestsPerSecond. Also, if prefetchCount for your most accessed item is 10, it's still ok for items that are accessed less frequently; half-less frequently accessed item will still have prefetchCount=5, and even 10-times less accessed profiles will most likely benefit from the prefetch. But prefetch won't be much of use for items accessed less often than that (items with prefetchCount < 1), ordinary TTL will step in then. No biggy, since these items are obviously not in high demand, but you can improve this by increasing prefetchTime. This will result in higher prefetchProbability, and will make cache slightly less effective (for prefetchTime=120 and TTL=600 you only have 8 minutes of effective cache, compared to 9.5 minutes with prefetchTime=30), but won't cause database slams and will ensure that less requested items will be prefetched instead of TTL'ed also.

The problem remains, how to determine the requestsPerSecond for a single item. I use APC (apc.php) to see how many hits have been made to a certain item within its lifetime; 50000 hits within 15 minutes ~= 55 requests per second. So, I decide to use prefetchTime=30s and prefetchCount=8, and calculate the prefetchProbability = 8 / (30 × 55) ~= 0.005.