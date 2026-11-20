<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allow Private Monitor Targets
    |--------------------------------------------------------------------------
    |
    | When enabled, monitors may target private, reserved, or local addresses
    | (e.g. .test domains, 127.0.0.1, LAN hosts). This disables the SSRF
    | protections that keep checks on public endpoints only. Enable only in
    | trusted local environments.
    |
    */

    'allow_private_targets' => env('MONITOR_ALLOW_PRIVATE_TARGETS', false),

];
