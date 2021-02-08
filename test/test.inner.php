<?php

// NOTE: this is the "internal" test-suite, which gets installed by the "front" test-suite

require __DIR__ . '/vendor/autoload.php';

test(
    'can open files via stream-wrapper',
    function () {
        $file = fopen("composer://mindplay/composer-locator/test/assets/a.txt", "r");

        ok(is_resource($file), "can open file in installed Composer package");

        eq(stream_get_contents($file), "A", "can read contents of files in installed Composer packages");

        ok(fclose($file), "can close file");
    }
);

test(
    'can read directories via stream-wrapper',
    function () {
        $dir = opendir("composer://mindplay/composer-locator/test/assets");

        eq(readdir($dir), ".");
        eq(readdir($dir), "..");
        eq(readdir($dir), "a.txt");
        eq(readdir($dir), "b.txt");
        eq(readdir($dir), false);

        closedir($dir);
    }
);

test(
    'can read vendor-names as directories via stream-wrapper',
    function () {
        $dir = opendir("composer://");

        eq(readdir($dir), ".");
        eq(readdir($dir), "..");
        eq(readdir($dir), "mindplay");
        eq(readdir($dir), false);

        closedir($dir);
    }
);

test(
    'can read vendor/package-names as directories via stream-wrapper',
    function () {
        $dir = opendir("composer://mindplay");

        eq(readdir($dir), ".");
        eq(readdir($dir), "..");
        eq(readdir($dir), "composer-locator");
        eq(readdir($dir), "testies");
        eq(readdir($dir), false);

        closedir($dir);
    }
);

test(
    'can iterate through vendors/packages/files via stream-wrapper',
    function () {
        $iterator = new RecursiveDirectoryIterator("composer://");
        $iterator = new RecursiveIteratorIterator($iterator);
        $iterator = new RegexIterator($iterator, '/.*\/composer\.json$/');
        
        $found = iterator_to_array($iterator);

        ok($found["composer://mindplay/composer-locator/composer.json"] instanceof SplFileInfo);
        ok($found["composer://mindplay/testies/composer.json"] instanceof SplFileInfo);
    }
);

test(
    'can check if vendors/package/file paths exist via stream-wrapper',
    function () {
        ok(is_dir("composer://"), "vendor root exists");
        ok(is_dir("composer://mindplay"), "vendor exists");
        ok(is_dir("composer://mindplay/composer-locator"), "package exists");
        ok(is_file("composer://mindplay/composer-locator/README.md"), "file in package exists");

        ok(! file_exists("composer://foo"), "vendor does not exist");
        ok(! file_exists("composer://mindplay/foo"), "package does not exist");
        ok(! file_exists("composer://mindplay/composer-locator/foo"), "file in package does not exist");
    }
);

test(
    'stream-wrapper prevents opening of non-existent files',
    function () {
        ok(false === fopen("composer://foo", "r"), "vendor does not exist");
        ok(false === fopen("composer://mindplay/foo", "r"), "package does not exist");
        ok(false === fopen("composer://mindplay/composer-locator/foo", "r"), "file in package does not exist");
    }
);

test(
    'stream-wrapper resources should not be writable',
    function () {
        ok(! is_writable("composer://"));
        ok(! is_writable("composer://mindplay"));
        ok(! is_writable("composer://mindplay/composer-locator"));
        ok(! is_writable("composer://mindplay/composer-locator/README.md"));

        ok(false === fopen("composer://mindplay/composer-locator/README.md", "w"), "cannot open file for writing");
    }
);

/*

// NOTE: so this is pretty disappointing: glob() does not support stream wrappers, at all.

test(
    'can read directories via stream-wrapper',
    function () {
        eq(
            glob("composer://mindplay/composer-locator/test/assets/*.txt"),
            [
                "composer://mindplay/composer-locator/test/assets/a.txt",
                "composer://mindplay/composer-locator/test/assets/b.txt"
            ],
            "can list contents of directories in installed Composer packages"
        );
    }
);

*/

test(
    'can check if packages are installed',
    function () {
        eq(ComposerLocator::isInstalled('mindplay/composer-locator'), true);
        eq(ComposerLocator::isInstalled('mindplay/testies'), true);
        eq(ComposerLocator::isInstalled('nobody/not-installed'), false);
    }
);

test(
    'can get root path',
    function () {
        $filename = uniqid();

        $root_path = ComposerLocator::getRootPath() . "/" . $filename;

        touch($root_path);

        ok(file_exists($root_path));
        ok(file_exists(__DIR__ . "/" . $filename));
    }
);

test(
    'can get package path',
    function () {
        $filename = uniqid();

        $root_path = ComposerLocator::getPath('mindplay/composer-locator') . "/" . $filename;

        touch($root_path);

        ok(file_exists($root_path));
        ok(file_exists(__DIR__ . "/vendor/mindplay/composer-locator/" . $filename));
    }
);

test(
    'can list installed packages',
    function () {
        $packages = ComposerLocator::getPackages();

        eq(count($packages), 2);

        eq($packages, ['mindplay/composer-locator', 'mindplay/testies']);
    }
);

test(
    'can get map of installation paths',
    function () {
        $paths = ComposerLocator::getPaths();

        eq(count($paths), 2);

        ok(is_dir($paths['mindplay/composer-locator']));
        ok(is_dir($paths['mindplay/testies']));
    }
);

configure()->disableErrorHandler(); // stream-wrappers may trigger errors

exit(run());
