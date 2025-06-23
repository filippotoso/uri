<?php

namespace FilippoToso\URI;

/**
 * @method scheme($scheme = null)
 * @method user($user = null)
 * @method pass($pass = null)
 * @method host($host = null)
 * @method port($port = null)
 * @method path($path = null)
 * @method fragment($fragment = null)
 */

class URI
{
    protected const PROPERTIES = ['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'];
    protected const HTTPS_SCHEME = 'https';
    protected const DEFAULT_SCHEME = self::HTTPS_SCHEME;

    protected $original;

    protected $scheme;
    protected $host;
    protected $port;
    protected $user;
    protected $pass;
    protected $path;
    protected $query;
    protected $fragment;

    protected $params = [];

    protected $numericPrefix = '';
    protected $argSeparator = '&';
    protected $encodingType = \PHP_QUERY_RFC1738;

    /**
     * Create an instance of the class 
     * @param string $url The url that you want to parse
     * @param string $numericPrefix http_build_query optional parameter used to build the querystring
     * @param string $argSeparator http_build_query optional parameter used to build the querystring
     * @param int $encodingType http_build_query optional parameter used to build the querystring
     * @return URI 
     */
    public static function make(string $url, string $numericPrefix = '', string $argSeparator = '&', int $encodingType = \PHP_QUERY_RFC1738)
    {
        return new static($url, $numericPrefix, $argSeparator, $encodingType);
    }

    /**
     * Create an instance of the class 
     * @param string $url The url that you want to parse
     * @param string $numericPrefix http_build_query optional parameter used to build the querystring
     * @param string $argSeparator http_build_query optional parameter used to build the querystring
     * @param int $encodingType http_build_query optional parameter used to build the querystring
     * @return URI 
     */
    public function __construct(string $url, string $numericPrefix = '', string $argSeparator = '&', int $encodingType = \PHP_QUERY_RFC1738)
    {
        $this->numericPrefix = $numericPrefix;
        $this->argSeparator = $argSeparator;
        $this->encodingType = $encodingType;
        $this->parse($url);
    }

    /**
     * Parse a new url
     * @param string $url The url that you want to parse
     * @return URI 
     */
    public function parse($url)
    {
        $url = $this->protocolRelative($url);

        $this->original = $url;

        $parsed = parse_url($url);

        foreach (static::PROPERTIES as $property) {
            $this->$property = $parsed[$property] ?? null;
        }

        $this->path = $this->path ?? '/';

        $this->parseQuery();

        return $this;
    }

    protected function protocolRelative($url)
    {
        // Handle urls that starts with ://
        if (strpos($url, '://') === 0) {
            $scheme = $this->scheme ?? static::DEFAULT_SCHEME;
            $url = $scheme . $url;
        }

        return $url;
    }

    protected function protocolAndPathRelative($url)
    {
        // Handle urls that starts with :///
        if (strpos($url, ':///') === 0) {
            $scheme = $this->scheme ?? static::DEFAULT_SCHEME;

            if (is_null($this->port)) {
                $url = $scheme . '://' . $this->host . substr($url, 3);
            } else {
                $url = $scheme . '://' . $this->host . ':' . $this->port . substr($url, 3);
            }
        }

        return $url;
    }

    protected function parseQuery()
    {
        parse_str((string)$this->query, $this->params);
    }

    /**
     * Get the original url
     *
     * @return URI
     */
    public function original()
    {
        return $this->original;
    }

    /**
     * Update the URI instance with a relative url.
     * The url can be: a full url, an absolute path or a relative path.
     * It can also include the querystring and the fragment.
     * 
     * @param string $url The relative url
     * @return URI
     */
    public function relative(string $url)
    {
        // It's a schemeless relative URL
        if (strpos($url, ':///') !== false) {
            $url = $this->protocolAndPathRelative($url);
            return $this->make($url);
        }

        // It's a full URL without scheme
        if (strpos($url, '://') !== false) {
            $url = $this->protocolRelative($url);
            return $this->make($url);
        }

        // Path with params
        if (strpos($url, '?') !== false) {
            [$path, $this->query] = explode('?', $url, 2);

            if (strpos($this->query, '#') !== false) {
                [$this->query, $this->fragment] = explode('#', $this->query, 2);
            } else {
                $this->fragment = null;
            }

            $this->parseQuery();
        } else {
            $path = $url;
        }

        $path = trim($path);

        if (substr($path, 0, 1) == '/') {
            $this->path = $path;
        } else {
            if (substr($path, 0, 2) == './') {
                $path = substr($path, 2);
            }
            $base = substr($this->path, 0, strrpos($this->path, '/') + 1);
            $this->path = $base . $path;
        }

        $tokens = explode('/', $this->path);

        while (in_array('..', $tokens)) {
            for ($i = 0; $i < count($tokens); $i++) {
                if ($tokens[$i] == '..') {
                    unset($tokens[$i]);
                    unset($tokens[$i - 1]);
                    $tokens = array_values($tokens);
                    break;
                }
            }
        }

        $this->path = implode('/', $tokens);

        return $this;
    }

    /**
     * Get / set the path extension
     *
     * @param string $extension
     * @return URI
     */
    public function extension(?string $extension = null)
    {
        $pos = strrpos($this->path, '.');

        if (func_num_args() == 0) {
            if ($pos === false) {
                return null;
            } else {
                return substr($this->path, $pos + 1);
            }
        } else {
            if ($pos === false) {
                $this->path .= '.' . $extension;
            } else {
                $this->path = substr($this->path, 0, $pos + 1) . $extension;
            }
        }

        return $this;
    }

    /**
     * Get / set the querystring parameters
     *
     * @param array $value
     * @return URI|array
     */
    public function params(array $value = [])
    {
        if (func_num_args() == 0) {
            return $this->params;
        }

        $this->params = $value;

        return $this;
    }

    /**
     * Add a new parameter to the querystring parameters
     *
     * @param string $key The key in dot notation
     * @param mixed $value
     * @return URI
     */
    public function add(string $key, $value)
    {
        return $this->dot($key, $this->params, function ($key, &$array) use ($value) {
            if (isset($array[$key])) {
                if (is_array($array[$key])) {
                    $array[$key][] = $value;
                } else {
                    $array[$key] = [$array[$key], $value];
                }
            } else {
                $array[$key] = $value;
            }
        });
    }

    /**
     * Remove a parameter from the querystring parameters
     *
     * @param string|callable $keyOrCallback The key in dot notation or a callback that returns true if the element has to be removed
     * @return URI
     */
    public function remove($keyOrCallback)
    {
        if (is_callable($keyOrCallback)) {
            $dotted = $this->dotted($this->params);

            foreach ($dotted as $key => $value) {
                if ($keyOrCallback($key, $value)) {
                    $this->dot($key, $this->params, function ($key, &$array) {
                        unset($array[$key]);
                    });
                }
            }

            return $this;
        }

        return $this->dot($keyOrCallback, $this->params, function ($key, &$array) {
            unset($array[$key]);
        });
    }

    /**
     * Set a parameter of the querystring parameters
     *
     * @param string $key The key in dot notation
     * @param mixed $value
     * @return URI
     */
    public function set(string $key, $value)
    {
        return $this->dot($key, $this->params, function ($key, &$array) use ($value) {
            $array[$key] = $value;
        });
    }

    /**
     * Get a parameter of the querystring parameters
     *
     * @param string $key The key in dot notation
     * @return URI
     */
    public function get(string $key, $default = null)
    {
        return $this->dot($key, $this->params, function ($key, &$array) use ($default) {
            return $array[$key] ?? $default;
        });
    }

    /**
     * Check if a parameter exists in the querystring parameters
     *
     * @return boolean
     */
    public function has(string $key): bool
    {
        return $this->dot($key, $this->params, function ($key, &$array) {
            return array_key_exists($key, $array);
        });
    }

    // @see https://github.com/laravel/framework/blob/c5908c59860177f607fb9c3b22add0513dcc7a9b/src/Illuminate/Collections/Arr.php#L286
    protected function dot(string $key, &$array, $callback)
    {
        if (!is_array($array) && !($array instanceof \ArrayAccess)) {
            return $this;
        }

        if (strpos($key, '.') === false) {
            return $callback($key, $array) ?? $this;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $key = array_shift($keys);

        $result = $callback($key, $array);
        return $result ?? $this;
    }

    // @see https://github.com/laravel/framework/blob/c5908c59860177f607fb9c3b22add0513dcc7a9b/src/Illuminate/Collections/Arr.php#L109
    protected function dotted($array, $previous = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, $this->dotted($value, $previous . $key . '.'));
            } else {
                $results[$previous . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get / set the querystring as a encoded string
     *
     * @param string $query
     * @return URI
     */
    public function query($query = null)
    {
        if (func_num_args() == 0) {
            return http_build_query($this->params, $this->numericPrefix, $this->argSeparator, $this->encodingType);
        }

        $this->query = $query;

        $this->parseQuery();

        return $this;
    }

    /**
     * Dynamically set / get URI::PROPERTIES properties.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return URI|void
     */
    public function __call(string $name, array $arguments)
    {
        if (in_array($name, static::PROPERTIES)) {
            if (count($arguments) == 0) {
                return $this->$name;
            }
            $this->$name = $arguments[0];

            return $this;
        }
    }

    public function __toString()
    {
        return $this->url();
    }

    /**
     * Render the new url
     *
     * @return string
     */
    public function url()
    {
        $result = $this->scheme . '://';

        if (!is_null($this->user) && !is_null($this->pass)) {
            $result .= urlencode($this->user) . ':' . urlencode($this->pass) . '@';
        } elseif (!is_null($this->user)) {
            $result .= urlencode($this->user) . '@';
        }

        $result .= $this->host;

        if (!is_null($this->port)) {
            $result .= ':' . $this->port;
        }

        if (!is_null($this->path)) {
            $result .= $this->path;
        }

        if (count($this->params) > 0) {
            $result .= '?' . $this->query();
        }

        if (!is_null($this->fragment)) {
            $result .= '#' . $this->fragment;
        }

        return $result;
    }
}
