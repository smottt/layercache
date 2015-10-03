LayerCache is a simple caching framework for PHP5 which allows you to easily plug a multi-layered caching mechanism into your application.

See [introduction](Introduction.md) and [examples](Examples.md) for more details.

The current trunk version is running in production with 5 web servers (512MB APC each), 3 memcache nodes (3GB each), and a database backend. Most of the items are cached in two layers: Memcache and APC.

And ... so far it works fine. :)
