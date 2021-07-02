<?php

return [

    'use_authenticated' => false,

    'user' => [
        'name' => env('GITAMIC_GIT_USER_NAME', 'Gitamic'),
        'email' => env('GITAMIC_GIT_USER_EMAIL',  'gitamic@example.com'),
    ],

    'cache' => [
        'ttl' => 300,
    ]

];
