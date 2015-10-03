# Specifying cache source #

## Specify data and key callbacks ##
To specify the source, you have to pass two callbacks for forSource() method; one is the data retrieval method, the other is a key mapping method (optional). Both callbacks can be [any type of callback that PHP supports](http://www.php.net/callback); object method, class (static) method, create\_function construct, a closure (since PHP 5.3), and even a global function.


An example of usage with Propel show how to use the existing retrieveByPK, along with a new mapKey method.
```
class UserProfilePeer extends BaseUserProfilePeer
{
  static function mapKey($id)
  {
    return "user-prof/$id";
  }
}

LayerCache::forSource(array('UserProfilePeer', 'retrieveByPK'), array('UserProfilePeer', 'mapKey'))-> ...
```

Another Propel example, this time the key-function is a closure (PHP 5.3+).
```
LayerCache::forSource(array('UserProfilePeer', 'retrieveByPK'), function ($key) { return "user/$key"; })-> ...
```

Third example, this time without key mapper method (in this case, the key is used as-passed).
```
class UserProfileReader
{
  function __construct($db)
  {
    $this->db = $db;
  }
  
  function read($user_id)
  {
    return $db->query(...);
  }
}
$source = new UserProfileReader($db);
LayerCache::forSource(array($source, 'read'))-> ...
```