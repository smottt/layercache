## Description ##
LayerCache is a simple caching framework for PHP5 which allows you to easily plug a multi-layered caching mechanism into your application.

It implements the concept of layered caching where an item can be cached in several layers. When you request an item, the framework reads from each cache in the stack. If the item is present, it's returned, if it isn't, it's retrieved from the source and stored in all caches in the stack.

## The basics ##
First, let's get familiar with the concept, terms and basics.

For each type of item you want to cache (i.e. user profile, partial html, complex calculation), you have to create _a stack_. A stack is composed of a _source_ object and several caches, and has a unique name. Each stack can have different caches and usually a different source.

The source object is an object that you must create and pass to LayerCache. It has to implement two methods: normalizeKey($key) and get($key). The first method should map your _custom key_ (i.e. id of a record from the table) into _cache key_ (a string value which is used as a key for reading from and writing to every cache layer).

For example, if you're caching user profiles, your _custom key_ will probably be an integer ID from the database, say 79. The normalizeKey() method would map this integer value into a string, such as "users/79". This string representation of the key should be unique across all different cacheable items. If you also cache some other type of item with id=79, the string representations should differ. People usually use some prefix with which they simulate namespaces, like "user/54" or "profile/65".

When you read an item, you call _get()_ method on the stack, and pass it your _custom key_. The stack first gets the _cache key_ for the item by calling _normalizeKey()_ method of the _source object_. Then it reads each cache in the stack, one by one. If the data is present (and valid), it's returned. If the data isn't present (or valid) in any of the caches in the stack, the method _get()_ of the _source object_ is called, and the _custom key_ is passed to it. The object should return the result, which is then cached in every cache in the stack. The result can be an array or an object, because LayerCache serializes the data prior to writing it in any cache layer.

## More information ##
  * [Examples](Examples.md)
  * [UnderstandingLayeredCaching](UnderstandingLayeredCaching.md)