<?php

return [
    'alipay' => [
        'app_id'         => '2016091700529492',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtZ0+kbGVUgbtDcvyMfWWP0f439c7G/u9HweK9vj2PUDlPEMfljIt9UacOK5sIv1/iKdxBC5+HkwFbT2k0yUDJuRyVXcpX87ejABhbsSju9vJkSEeZwIVNULFrWoElJuTLgCdaY6r/Dh+YCgbNwJEXc3vV7SfTOMjd4z6jcHxB3y/rVAoPCnvBT10G4mmOV5XoY8HkjSDPp5g++Ws9VGzPEcXEtt9HpSJWIeZJFBRTY9egxLqyT6k12biyLwYlLUOIqa8t6lUQtrNQpMKY3qp4SqhEeHm5LJe24/o5clYfx8g5c7gBJ9gS5skAfhvkPzH2Q+eYb+WVRGxJN+3Ez7SRwIDAQAB',
        'private_key'    => 'MIIEowIBAAKCAQEAoW9RZ88MqR3Gfqh+S3Fc/csqZW35J1BeR6OyI+N9/7S8UYUbqa0CiwVn9XTMXQLo6NafIXhe0zLFLg0Y+YSAhcB3MnB2NtApH20Yw4cdvhQRCJlT1wFq5q3wComIke9+8xT0eR0m+WlmiFPZrvw7WGq/GxmQJ+5xtMYftJrxJ8V2xDWkHyV2CzOeisIQUG5TG42OOI+0VfgHYyR8IXkESPXZWq5fvPZG6c8L9sq+nyjMtLSyLKIZxryIve8jCj6hfzG5KEAs3fGysTSHCxPXTCvKVHz3e7VQ1PWmR5/eweA3WbglMh++yNMw7ZrnZTwVKZDxL4dPRGU2MHCA5gR5/QIDAQABAoIBAD3tKdkcf8rP9TjZAbmG/xkMOnM2QIFoPwJUNR23Qro4ZpUO7pGkIHmb1Uk/SLXVS+sliV2ZgyaZLzJ/K7lrrrGgJxleMrrMFzpK61j2F962a+JUjujHTm63AoHISG9S4+vzeeSL3kuGtyJMqBCMCWfdavnGUJpAybWC2t65IRET/nD+345e5CCz0pbpM5IxhBltmHMCN7LP1E9ABvk27X/TsdPmH2W5w0N3bnl/irwPeiyhk4Ed2k194bMKbUhFOqSCZdfO5s6O9S0vYVnYhgWtE7KOFFs+R6hgZqfLrSYjcTuQCnFmJXksT3Ysl0A8uukePf5+uAM8PgG9wEx8H7ECgYEA0j6F+j+5QUt7fCf002oLmv+kA/mQGBXgnLdkKUxw0DrFhAyVGEGKnykYg2dQE1L9Q1Co2otTwf1xEF1iH/nnRtpt3cS+p6uAahzaeveVQd0Ha/zVEjohuARFCxWpS7QxRA+T1kBA3btvvmuZblCUOZCQiJG5V9UsB669uONn5icCgYEAxJFxvtZGGKXlUC52ndkYkFMgoZRN/d/OWkPsFnRv9elGBscx+E0D3D5iXI9/1vdX/j/g/a/fzW09JA1/SDL6fxS5+dVs7I9LFGaFzuJkuGaEHYA/WQshwzCnVEdnt9DdWbMivXn9mjn4w5RqgYtjrmg72HVulm6Rre/CKDymeTsCgYB2MKPMnIoeRLq0fxnIx7KifdfMTD1O7O2J7CaoeMpqpL9ojlI6go3VD5syM5/2XQ73Cp8BzJVXVox68KQ66Ze0YxKkOvga9fSIspO30PrdPc3wTP/S8U8HTY8qvhCf+DpB0qf/J7vPapFU8NQeCbpp6fT2cUVKNRI9d2Q8TcqYvQKBgQCFetqJo4mv/R9Npm+H2r8SalBzSHj5l3vR0ePEj8bjb0fNHIDzQqEHcxlqD0vZVtba4NfQjRhlr/NfwwaWqX9uwk1TuLkhnmB2dvQQO633hi3atpbNyYBgwm4uDdsBeISG/9zsW6V5kDo73VHBrDzBVyikYYH6BxVHztLCbgUBtwKBgHbWb3fbIUcCDVhCWQFxrBVEoQwiQRQyxs3cP3sRQncTOYbY7ZvpfAB92na7XRSfAAnOfcDWteJNvAzsj9wOOJIrzOAPMaazSe4mIlzXDxpyKgBSFsuwjqLpGhnSQQvVwHi+fm9sD6IRIXO97h2L3YvLZ5buRniAlxf0rmT2KXCp',
        'log'            => [
            'file' => storage_path('logs/alipay.log'),
        ],
    ],

    'wechat' => [
        'app_id'      => '',
        'mch_id'      => '',
        'key'         => '',
        'cert_client' => '',
        'cert_key'    => '',
        'log'         => [
            'file' => storage_path('logs/wechat_pay.log'),
        ],
    ],
];