# URI

A simple class to easily manipulate URLs.

## Requirements

- PHP 7.2+

## Installing

Use Composer to install it:

```
composer require filippo-toso/uri
```

## Basic usage

You can create an instance of the URI class using the static `make()` or through its constructor:

```
use FilippoToso\URI\URI;

$url = 'http://www.example.com/dir/sub/file.php?name=john&emailjohn@smith.com#fragment';

$uri = URI::make($url);

// or

$uri = new URI($url);
```

You can also pass other optional parameters that will be used when creating the querystring part of the URL. Check the source code for more details.

Once you have an instance of the class, you can use its fluent API to manipulate the URL as you like.

For instance, let's change the schema and domain:

```
use FilippoToso\URI\URI;

$url = 'http://www.example.com/dir/sub/file.php?name=john&emailjohn@smith.com';

$newUrl = URI::make($url)
    ->scheme('http')
    ->domain('test.com')
    ->url();

```

You can call the following methods to get/set the relative parts of the URL: `scheme(), user(), pass(), host(), port(), path(), query() and fragment()`.

For instance, let's get the domain:

```
use FilippoToso\URI\URI;

$url = 'http://www.example.com/dir/sub/file.php?name=john&emailjohn@smith.com';

$domain = URI::make($url)
    ->domain();

```

The class can be casted to string to get the whole url or you can use the `url()` method as shown above. 
You can also get the unmodified url using the `original()` method.

## More complex usage

Now let's do something more complex, for instance, let's change an url through a relative path. 

```
use FilippoToso\URI\URI;

$url = 'http://www.example.com/dir/sub/file.php?name=john&emailjohn@smith.com';

$relativeUrl = '../../hello.php';

$newUrl = URI::make($url)
    ->relative($relativeUrl)
    ->url();

```

The parameter passed to the `relative()` method can be a full URL (in this case the whole URL will be replaced with the new one), an absolute path or a relative path. It can also include the querystring and the fragment.

You can also change only the extension of the file using the `extension()` method or replace the querystring using the `params()` method (it accepts an array of parameters as input) or the `query()` (it accepts a string as input).

## Querystring manipulation

Talking about querystring manipulation, there are other useful methods to do that. For instance, you can use:

- `add()` to add a parameter 
- `remove()` to remove a parameter
- `set()` to replace the parameter value
- `get()` to get the parameter value

All these methods accept a dot notation as the key name. For instance, to change the a parameter like `$_GET['post']['content']['html']` you will use the dot notation `post.content.html`.

Talking about the `remove()` method, instead of a key, you can pass it a callback to remove multiple elements in one go. For instance, here's the code to remove all the `utm_*` parameters used to track campaigns in Google Analytics:

```
$url = 'https://www.example.com/?utm_source=summer-mailer&utm_medium=email&utm_campaign=summer-sale';

$newUrl = URI::make($url)->remove(function ($key, $value) {
    return (bool)preg_match('#^utm_#si', $key);
})->url();
```

That's it, go change the URLs!.
