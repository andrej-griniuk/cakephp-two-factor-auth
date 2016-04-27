<?php
return [
    'TwoFactorAuth' => [
        // Will be displayed in the app as issuer name
        'issuer' => null,
        // The number of digits the resulting codes will be
        'digits' => 6,
        // The number of seconds a code will be valid
        'period' => 30,
        // The algorithm used
        'algorithm' => 'sha1',
        // QR-code provider (more on this later)
        'qrcodeprovider' => null,
        // Random Number Generator provider (more on this later)
        'rngprovider' => null,
        // Key used for encrypting the user credentials, leave this false to use Security.salt
        'encryptionKey' => false
    ]
];
