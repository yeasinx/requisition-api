<?php

namespace App\Enums;

enum RequisitionStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case DENIED = 'DENIED';
}
