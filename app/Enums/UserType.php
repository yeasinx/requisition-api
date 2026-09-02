<?php

namespace App\Enums;

enum UserType: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case HR_ADMIN = 'HR_ADMIN';
    case EMPLOYEE = 'EMPLOYEE';
    case ACCOUNTS = 'ACCOUNTS';
}
