mindplay/composer-locator
=========================

This Composer plugin provides a means of locating the installation path for a given Composer package name.

Use this to locate vendor package roots, e.g. when working with template files or other assets in a package.

You can think of this as a minimalist alternative to [puli](https://github.com/puli/repository) - rather than
abstracting repositories and resources through a complex virtual file system, I prefer to just use the physical
file system and standard PHP APIs. Works for me. I like simple things. YMMV.

### Usage

Add to your `composer.json` file:

```json
{
    "require": {
        "mindplay/composer-locator": "^1"
    }
}
```

Running `composer install` or `composer update` will bootstrap your project with a simple, global function
that provides the installation path for a given Composer package name:

```php
$root_path = composer_path("mindplay/composer-locator");
```

If the specified package name is not found, the function throws a `RuntimeException`.

That's all.
