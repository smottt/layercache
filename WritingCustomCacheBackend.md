# Writing a custom backend #
Writing a custom cache backend is very easy. All you need is a class with a getter and a setter:
  * it has to implement get($key) and set($key, $value, $ttl) methods
  * the $key is a string value (normalized key)
  * $value can be any PHP variable; scalar, array, object.
  * you have to ensure that get() returns **null** if item isn't present or is stale

That's all.

## Example ##
Let's say I want a very simple cache backend that caches in an array. Here's the cache backend class:
```
class ArrayCache
{
  protected $items = array();
  
  function get($key)
  {
    if (isset($this->items[$key]))
      return $this->items[$key];
  }

  function set($key, $value, $ttl)
  {
    $this->items[$key] = $value;
  }
}
```

Now let's use this.
```
LayerCache::
  forSource('data_fetch_func', 'key_map_func')->
  addLayer(new ArrayCache)->
  toStack('items');

echo LayerCache::stack('items')->get('a');
echo LayerCache::stack('items')->get('b');
```

This is a simple array cache, which stores items in an array. The array gets destroyed when the request finishes, so it's not of much use. Besides, LayerCache already has such a cache, named LayerCache\_Cache\_Local. This is a PHP array LRU cache, enabling you to limit the number of entries or size stored in cache.

## Adding TTL ##
Let's suppose we want to add a TTL feature to our class. We'll have to store the expiration time with every item and check it upon read.

```
class ArrayCache
{
  protected $items = array();
  
  function get($key)
  {
    if (isset($this->items[$key]))
    {
      if ($this->items[$key]['expires'] > time())
        return null;
      else
        return $this->items[$key]['data'];
    }
  }

  function set($key, $value, $ttl)
  {
    $this->items[$key] = array('data' => $value, 'expires' => time() + $ttl);
  }
}
```
That's about it. You now have a local PHP array cache with TTL feature so your items get removed as they become too old. Again, this was a useless exercise, because LayerCache already comes with a class like this, only better :). But you now have to knowledge to write your own cache backend.