<?php

// NOTE: this is the "internal" test-suite, which gets installed by the "front" test-suite

require __DIR__ . '/vendor/autoload.php';

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

exit(run());
