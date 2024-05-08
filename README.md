# mindplay/composer-locator

### ⚠️ DEPRECATED: use [Composer Runtime Utilities](https://getcomposer.org/doc/07-runtime.md)

This Composer plugin provides a means of locating the installation path for a given Composer package name.

[![PHP Version](https://img.shields.io/badge/php-7.3_--_8.3%2B-blue.svg)](https://packagist.org/packages/mindplay/composer-locator)
[![CI](https://github.com/mindplay-dk/composer-locator/actions/workflows/ci.yml/badge.svg)](https://github.com/mindplay-dk/composer-locator/actions/workflows/ci.yml)

Use this to locate vendor package roots, e.g. when working with template files or other assets in a package,
locating and discovering plugins, and so on.

It works regardless of installers affecting the individual package installation paths, and also works whether
the package in question is currently the root package/project or a dependency.

### Usage

Add to your `composer.json` file:

```json
{
    "require": {
        "mindplay/composer-locator": "^2"
    },
    "config": {
        "allow-plugins": {
            "mindplay/composer-locator": true
        }
    }
}
```

Running `composer install` or `composer update` will bootstrap your project with a generated class containing
a registry of Composer package installation paths.

To obtain the installation path for given package:

```php
$path = ComposerLocator::getPath("vendor/package"); // => "/path/to/vendor/package" 
```

If the specified package name is not found, the function throws a `RuntimeException`.

To check whether a given package is installed:

```php
$is_installed = ComposerLocator::isInstalled("vendor/package"); // => (bool) true|false 
```

The root Composer project package doesn't necessarily have a package name - to get the root path
of the root Composer project, without specifying the package name:

```php
$path = ComposerLocator::getRootPath(); // => "/path/to/project/root" 
```

You can also get a list of all installed packages via `ComposerLocator::getPackages()`, or obtain a full
key/value map of package-names to absolute root paths via `ComposerLocator::getPaths()`.

## Why?

Needing to know the root path of a package installation folder is quite a common requirement, such as when
you need to specify paths to template files or other assets.

The problem is that Composer itself offers no simple and reliable way to do that.

You can use reflection to get the path to a known class or interface from the package, and then `dirname()` up
from your `src` folder to the package installation root, but that approach isn't very robust, since the location
of a class file may change from one version to another.

Even if you know the path of the vendor root folder, and the `{vendor}/{package}` folder name convention, there
is no guarantee that's always where packages are installed - something like [composer-installers](https://github.com/composer/installers)
could affect the installation paths.

Also, when developing a library, during testing and development, the package will be installed as the root/project
package, but this path will be different when it's installed as a dependency in another project.
