# Examples #

Here are a few examples that will help you get started. For details on how to specify the cache source, see SpecifyingSource.

## Example 1: caching a user profile in Memcache and APC ##

First, let's define a _source object_. In this example, the source object uses a database.

```
class UserProfileSource
{
  protected $db;
  
  function __construct($db)
  {
    $this->db = $db;
  }
  
  function mapKey($key)
  {
    return "userprofile/" . intval($key);
  }

  function get($key)
  {
    return $this->db->query('select * from user_profile where user_id = ' . intval($key));
  }
}
```

So, this is a very simple source object, that can normalize a key and read a record from the database. Now let's create a cache stack that will use two cache layers (Memcache and APC).
```
// create a database object to be used with UserProfileSource
$database = new PDO(...);

// prepare a memcache object to be used in a stack
$memcache = new Memcache;
$memcache->connect('memcachehost');

// create the source
$source = new UserProfileSource($database);

// create a stack named UserProfile
LayerCache::
  forSource(array($source, 'get'), array($source, 'mapKey'))->
  addLayer(new LayerCache_Cache_Memcache($memcache))->withTTL(3600)->
  addLayer(new LayerCache_Cache_APC())->withTTL(600)->
  toStack('UserProfile');
```

Now, use the cache stack:
```
$user_profile = LayerCache::stack('UserProfile')->get(43);
```

The caches are read in reverse order, so LayerCache will read from APC first. If the item isn't present in APC, item will be read from Memcache. If the item isn't there either, the source object's get method will be called, the data will be returned, and cached in both layers; Memcache (with TTL 1 hour) and APC (with TTL 10 minutes).

## Example 2: caching a complex calculation in APC ##

Again, we must define a _source object_ first. This time, our source object won't be using a database, but will perform a complex calculation. Our custom key in this case will be an array('a' => int, 'b' => int), and the complex calculation will multiply a with b.

```
class SomethingCalculated
{
  function flattenKey($key)
  {
    return "calc-{$key['a']}-{$key['b']}";
  }

  function get($key)
  {
    // perform a calculation
    return $key['a'] * sqrt($key['b']) * (22 / 7);
  }
}
```

Prepare the cache stack:
```
$source = new SomethingCalculated();
LayerCache::
  forSource(array($source, 'get'), array($source, 'flattenKey'))->
  addLayer(new LayerCache_Cache_APC)->withTTL(600)->
  toStack('calc');
```

Now, let's use the cache stack:
```
$calc = LayerCache::stack('calc')->get(array('a' => 65, 'b' => 2340));
```
Since the result isn't present in APC, it will be calculated with a call to get() on the _source object_. Then the result will be stored in APC, and will be retrieved upon next call.