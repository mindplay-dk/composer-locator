mindplay/composer-locator
=========================

This Composer plugin provides a means of locating the installation path for a given Composer package name.

[![PHP Version](https://img.shields.io/badge/php-7.2%2B-blue.svg)](https://packagist.org/packages/mindplay/composer-locator)
[![Build Status](https://travis-ci.org/mindplay-dk/composer-locator.svg?branch=master)](https://travis-ci.org/mindplay-dk/composer-locator)

Use this to locate vendor package roots, e.g. when working with template files or other assets in a package.

It works regardless of installers affecting the individual package installation paths, and also works whether
the package in question is currently the root package/project or a dependency.

You can think of this as a minimalist alternative to [puli](https://github.com/puli/repository) - rather than
abstracting repositories and resources through a complex virtual file system, I prefer to just use the physical
file system and standard PHP APIs.

Works for me. I like simple things. YMMV.

### Installation

Add to your `composer.json` file:

```json
{
    "require": {
        "mindplay/composer-locator": "^2"
    }
}
```

Running `composer install` or `composer update` will bootstrap your project with a stream-wrapper, and a
generated class containing a registry of Composer package installation paths.

### Stream Wrapper

The package registers a `composer://` stream-wrapper, which means you can use all the standard PHP file
functions (or any library that uses those) without adding any explicit dependencies to your code - the
package merely has to be installed and this will work "out of the box", e.g. with template engines or
most other libraries that accept paths.

The stream-wrapper emulates a file-system organized by vendors and packages, which means you can open
files from any installed Composer package - for example:

```php
echo file_get_contents("composer://mindplay/composer-locator/README.md");
```

Directory traversal all the way from the `composer://` root is supported - so, for example, to locate
all the `README` files in all the folders of all installed packages, you can do this:

```php
$iterator = new RecursiveDirectoryIterator("composer://");
$iterator = new RecursiveIteratorIterator($iterator);
$iterator = new RegexIterator($iterator, '/.*\/README\.(md|txt)$/');

foreach ($iterator as $file) {
    echo $file->getPathname() . "\n";
}
```

Functions like `fopen` and `opendir` etc. are all supported - the only notable exception is `glob`, which
relies on native file-system access and does not support any stream-wrappers.

Note that, while the stream-wrapper doesn't prevent you from creating or opening files in write-mode,
writing to package folders is generally a very bad idea.

### API

Using the API is completely optional - if you use the stream-wrapper, you don't need to concern yourself
with local file-system paths to begin with. Anything else you can do with the API can be done with the
stream-wrapper - for example, instead of `ComposerLocator::isInstalled("vendor/package")`, you can use
`is_dir("composer://vendor/package")`.

To obtain the installation path for a given package:

```php
$path = ComposerLocator::getPath("vendor/package"); // => "/path/to/vendor/package" 
```

If the specified package name is not found, the function throws a `RuntimeException`.

To check whether a given package is installed:

```php
$is_installed = ComposerLocator::isInstalled("vendor/package"); // => (bool) true|false 
```

The root project package doesn't necessarily have a package name - in that case, or in other cases where you
need the project root path, you can obtain it directly:

```php
$path = ComposerLocator::getRootPath(); // => "/path/to/project/root" 
```

You can also get a list of all installed packages via `ComposerLocator::getPackages()`, or obtain the full
map of vendor/package names to absolute root paths via `ComposerLocator::getPaths()`.

## Why?

Needing to know the root path of a package installation folder is quite a common requirement, such as when
you need to specify paths to template files or other assets.

The problem is that Composer itself offers no simple and reliable way to do that.

You can use reflection to get the path to a known class or interface from the package, and then `dirname()` up
from your `src` folder to the package installation root, but that approach is pretty clumsy and creates random
dependencies on arbitrary class/interface-names, just for the sake of locating a package root.

Even if you know the path of the vendor root folder, and the `{vendor}/{package}` folder name convention, there
is no guarantee that's always where packages are installed - something like [composer-installers](https://github.com/composer/installers)
or other [custom installers](https://github.com/akimsko/courier) could affect the installation paths.

Also, under test, when a package is the root/project package, of course the assumption about the vendor folder
is always going to be wrong.
