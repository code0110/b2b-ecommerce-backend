<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromotionPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->hasAnyRole(['admin'])) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['sales_director', 'sales_agent']);
    }

    public function view(User $user, Promotion $promotion)
    {
        return $user->hasAnyRole(['sales_director', 'sales_agent']);
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['sales_director']);
    }

    public function update(User $user, Promotion $promotion)
    {
        return $user->hasAnyRole(['sales_director']);
    }

    public function delete(User $user, Promotion $promotion)
    {
        return $user->hasAnyRole(['sales_director']);
    }
}
