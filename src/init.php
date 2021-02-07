<?php

use mindplay\composer_locator\ComposerStreamWrapper;

if (false === stream_wrapper_register("composer", ComposerStreamWrapper::class)) {
    error_log("Unable to register Composer stream-wrapper", E_USER_WARNING);
}
