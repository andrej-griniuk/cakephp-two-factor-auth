<?php
declare(strict_types=1);

return [
    'users' => [
        'columns' => [
            'id' => ['type' => 'integer'],
            'username' => ['type' => 'string', 'null' => true],
            'password' => ['type' => 'string', 'null' => true],
            'secret' => ['type' => 'string', 'null' => true],
            'created' => ['type' => 'timestamp', 'null' => true],
            'updated' => ['type' => 'timestamp', 'null' => true],
        ],
        'constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ],
];
