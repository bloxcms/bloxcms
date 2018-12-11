<?php

    $code = (int)$_GET['code'] ?: 404;
        
    /**
     * @see https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     */
    $statuses = [
        400 => 'Bad Request',
        401 => 'Unauthorized', #!!!
        402 => 'Payment Required',
        403 => 'Forbidden', #!!!
        404 => 'Not Found', #!!!
        405 => 'Method Not Allowed', #!!!
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot (RFC 2324)',
        419 => 'Authentication Timeout (not in RFC 2616)',
        420 => 'Enhance Your Calm (Twitter)',
        420 => 'Method Failure (Spring Framework)',
        422 => 'Unprocessable Entity (WebDAV; RFC 4918)',
        423 => 'Locked (WebDAV; RFC 4918)',
        424 => 'Failed Dependency (WebDAV; RFC 4918)',
        426 => 'Upgrade Required',
        428 => 'Precondition Required (RFC 6585)',
        429 => 'Too Many Requests (RFC 6585)',
        431 => 'Request Header Fields Too Large (RFC 6585)',
        440 => 'Login Timeout (Microsoft)',
        444 => 'No Response (Nginx)',
        449 => 'Retry With (Microsoft)',
        450 => 'Blocked by Windows Parental Controls (Microsoft)',
        451 => 'Redirect (Microsoft)',
        451 => 'Unavailable For Legal Reasons (Internet draft)',
        494 => 'Request Header Too Large (Nginx)',
        495 => 'Cert Error (Nginx)',
        496 => 'No Cert (Nginx)',
        497 => 'HTTP to HTTPS (Nginx)',
        498 => 'Token expired/invalid (Esri)',
        499 => 'Client Closed Request (Nginx)',
        499 => 'Token required (Esri)',            
        500 => 'Internal Server Error',
        501 => 'Not Implemented', #!!!
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',  # Site maintanance
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (RFC 2295)',
        507 => 'Insufficient Storage (WebDAV; RFC 4918)',
        508 => 'Loop Detected (WebDAV; RFC 5842)',
        509 => 'Bandwidth Limit Exceeded (Apache bw/limited extension)[29]',
        510 => 'Not Extended (RFC 2774)',
        511 => 'Network Authentication Required (RFC 6585)',
        598 => 'Network read timeout error (Unknown)',
        599 => 'Network connect timeout error (Unknown)',
    ];


    $msg  = $terms['error'].' '.$code;
    if ($statuses[$code])
        $msg .= ' ('.$statuses[$code].')';
        
    if ($note = $_GET['note']) {
        $msg .= '<br>';
        if ($note == 'no-session')
            $msg .=  $terms['no-session'];
        elseif ($note == 'page-is-hidden-and-denied')
            $msg .=  $terms['page-is-hidden-and-denied'];
    }
    
    echo '<p>'.$msg.'</p>';
    echo '<p><a href="'.Blox::info('site','url').'/">'.$terms['go-to-home-page'].'</a></p>';
    exit;