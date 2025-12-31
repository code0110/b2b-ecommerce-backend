<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Customer;

class ImpersonateCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $impersonatedId = $request->header('X-Impersonated-Client-Id');

        if ($impersonatedId && $request->user()) {
            $user = $request->user();

            // Verify permissions - checks if user has sales roles
            // We assume hasRole is available on User model
            $isAgent = $user->hasRole('sales_agent');
            $isDirector = $user->hasRole('sales_director');

            if ($isAgent || $isDirector) {
                 // Verify ownership
                 $customer = Customer::find($impersonatedId);
                 
                 if ($customer) {
                     $canImpersonate = false;
                     
                     if ($isDirector) {
                         // Director can impersonate own clients OR clients of their agents
                         // Assuming logic: Director -> Agent -> Customer
                         // If Director is set as sales_director_user_id on customer, they have access
                         if ($customer->sales_director_user_id == $user->id || $customer->agent_user_id == $user->id) {
                             $canImpersonate = true;
                         }
                     } 
                     
                     if ($isAgent && !$canImpersonate) {
                         if ($customer->agent_user_id == $user->id) {
                             $canImpersonate = true;
                         }
                     }

                     if ($canImpersonate) {
                         // Swap customer_id for this request so Cart/Order controllers use the client's ID
                         $user->customer_id = $customer->id;
                         
                         // Mark attributes for debugging or specific logic
                         $request->attributes->set('is_impersonating', true);
                         $request->attributes->set('impersonated_customer', $customer);
                     }
                 }
            }
        }

        return $next($request);
    }
}
