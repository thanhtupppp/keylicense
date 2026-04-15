<?php

return [
    'max_devices' => (int) env('ADMIN_MAX_DEVICES', 2),
    'rotate_after_seconds' => (int) env('ADMIN_ROTATE_AFTER_SECONDS', 900),
    'session_seconds_default' => (int) env('ADMIN_SESSION_SECONDS_DEFAULT', 7200),
    'session_seconds_remember' => (int) env('ADMIN_SESSION_SECONDS_REMEMBER', 2592000),
];
