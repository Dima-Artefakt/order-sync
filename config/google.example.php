<?php

return [
    'developer_key' => "",
    'client_id'     => "",
    'client_secret' => "",
    'scopes'        => [
        'https://www.googleapis.com/auth/drive',
        'https://spreadsheets.google.com/feeds',
    ],
    'access_type'   => 'offline',
    'approval_prompt' => 'force',
    
    'service' => [
        'enable' => true,
        'file'   => storage_path('app/credentials.json'),
    ],
];