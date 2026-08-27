<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case GENERATOR_OWNER = 'generator_owner';
    case SUBSCRIBER = 'subscriber';
    case TECHNICIAN = 'technician';
}
