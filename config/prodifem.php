<?php

return [
    'legal_name' => env('PRODIFEM_LEGAL_NAME', 'Prodifem S.A. de C.V.'),
    'fiscal_address' => env(
        'PRODIFEM_FISCAL_ADDRESS',
        'Calle San Francisco No. 524, int. C, Col. Del Valle, Alcaldia Benito Juarez, Ciudad de Mexico, C.P. 03100'
    ),
    'rfc' => env('PRODIFEM_RFC', 'PRO170214P96'),
    'phone' => env('PRODIFEM_PHONE', '5591862620'),
    'email' => env('PRODIFEM_EMAIL', 'contacto@prodifem.com.mx'),
    'logo' => env('PRODIFEM_LOGO', 'img/logo-cbta.jpg'),
];
