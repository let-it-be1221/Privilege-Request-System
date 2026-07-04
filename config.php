<?php
// Basic configuration
return [
    'db' => [
        'host' => '127.0.0.1',
        'dbname' => 'privaccess',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],
    // Toggle to true to attempt LDAP auth (not configured here)
    'use_ldap' => false,
    'ldap' => [
        'host' => 'ldap://ldap.example.local',
        'base_dn' => 'dc=example,dc=local'
    ],
    'smtp' => [
        'host' => 'localhost',
        'port' => 25,
        'username' => '',
        'password' => '',
        'from_email' => 'no-reply@example.local',
        'from_name' => 'PrivAccess'
    ],
    // Application roles order for approval flow
    'workflow' => [
        'employee','manager','machine_owner','machine_admin_manager','machine_admin'
    ]
];
