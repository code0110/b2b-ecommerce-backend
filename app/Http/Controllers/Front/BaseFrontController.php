<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;

class BaseFrontController extends Controller
{
    protected function resolveCustomerFromUser(?User $user): ?Customer
    {
        if (!$user) {
            return null;
        }

        if (method_exists($user, 'customer')) {
            return $user->customer;
        }

        if (isset($user->customer_id)) {
            return Customer::find($user->customer_id);
        }

        return null;
    }
}
