<?php

return [
    'max_devices' => (int) env('ADMIN_MAX_DEVICES', 5),
    'rotate_after_seconds' => (int) env('ADMIN_ROTATE_AFTER_SECONDS', 900),
    'session_seconds_default' => (int) env('ADMIN_SESSION_SECONDS_DEFAULT', 7200),
    'session_seconds_remember' => (int) env('ADMIN_SESSION_SECONDS_REMEMBER', 2592000),
    'idle_timeout_minutes' => (int) env('ADMIN_IDLE_TIMEOUT_MINUTES', 30),
    'max_login_attempts' => (int) env('ADMIN_MAX_LOGIN_ATTEMPTS', 5),
];
