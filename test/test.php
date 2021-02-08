<?php

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

require dirname(__DIR__) . '/vendor/autoload.php';

function create_composer_json($package_dir)
{
    $package_dir = json_encode($package_dir);

    $composer_json = <<<JSON
{
    "repositories": [
        {
            "type": "path",
            "url": {$package_dir},
            "options": {
                "symlink": false
            }
        }
    ],
    "require":{
        "mindplay/testies": "^0.3.0",
        "mindplay/composer-locator": "*@dev"
    }
}
JSON;

    return $composer_json;
}

test(
    'run composer integration test-suite',
    function () {
        $PROJECT_DIR = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . uniqid();

        echo "Installing to: {$PROJECT_DIR}\n";

        $PACKAGE_DIR = dirname(__DIR__);

        $fs = new Filesystem();

        try {
            $fs->dumpFile("{$PROJECT_DIR}/composer.json", create_composer_json($PACKAGE_DIR));

            Process::fromShellCommandline('composer update', $PROJECT_DIR)->mustRun();

            $fs->copy(__DIR__ . "/test.inner.php", "{$PROJECT_DIR}/test.inner.php");

            Process::fromShellCommandline('php test.inner.php', $PROJECT_DIR)->mustRun();
        } catch (Exception $e) {
            $fs->remove("{$PROJECT_DIR}");

            throw $e;
        }

        ok(true, "integration test completed without errors");
    }
);

exit(run());
