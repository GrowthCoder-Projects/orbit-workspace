<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workspace Application Mode
    |--------------------------------------------------------------------------
    |
    | This configuration controls whether the workspace operates in a single
    | user mode (suitable for personal use) or multi-user mode.
    |
    */

    'single_user' => (bool) env('WORKSPACE_SINGLE_USER', true),
];
