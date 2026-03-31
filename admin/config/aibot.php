<?php

return [
    'configs_path' => env('BOT_CONFIGS_PATH', '/opt/ai-bot/configs'),
    'data_base_path' => env('BOT_DATA_BASE_PATH', '/opt/ai-bot/data'),
    'systemd_unit_prefix' => env('BOT_SYSTEMD_PREFIX', 'ai-bot@'),
];
