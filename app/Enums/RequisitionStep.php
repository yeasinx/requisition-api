<?php

namespace App\Enums;

enum RequisitionStep: string
{
   case APPROVER_1 = "APPROVER_1";
   case APPROVER_2 = "APPROVER_2";
   case BUSINESS_CONTROLLER = "BUSINESS_CONTROLLER";
   case ACCOUNTS = "ACCOUNTS";
   case HR_ADMIN = "HR_ADMIN";
}
