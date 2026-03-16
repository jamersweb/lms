<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role-based access: which roles can access which admin routes
    |--------------------------------------------------------------------------
    | admin = full access
    | mentor = limited admin access (whatsapp, ask, users view, broadcasts)
    | student = no admin access
    */
    'roles' => ['admin', 'mentor', 'student'],

    'permissions' => [
        'admin' => ['*'], // Full access
        'mentor' => [
            'admin.dashboard',
            'admin.whatsapp-settings',
            'admin.triggers.*',
            'admin.ask.*',
            'admin.questions.*',
            'admin.users.index',
            'admin.users.show',
            'admin.broadcasts.*',
        ],
        'student' => [],
    ],

    'statuses' => ['active', 'inactive', 'suspended'],
];
