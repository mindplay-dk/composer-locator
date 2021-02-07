<?php

if (false === stream_wrapper_register("composer", "mindplay\\composer_locator\\ComposerStreamWrapper")) {
    error_log("Unable to register Composer stream-wrapper", E_USER_WARNING);
}
