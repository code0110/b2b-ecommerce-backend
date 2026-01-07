<?php

namespace App\Services;

use App\Models\DiscountRule;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class DiscountRuleService
{
    /**
     * Get the "best" applicable rule for the given user/customer context.
     * Hierarchy: User > Customer Group > Role > Global.
     * Within same level (e.g. multiple roles), the MOST PERMISSIVE (highest limit) wins.
     *
     * @param User|null $user
     * @param Customer|null $customer
     * @param string|null $ruleType Optional rule type filter
     * @return DiscountRule|null
     */
    public function getBestApplicableRule(?User $user, ?Customer $customer = null, ?string $ruleType = null): ?DiscountRule
    {
        $query = DiscountRule::where('active', true);

        if ($ruleType) {
            $query->where('rule_type', $ruleType);
        }

        $allRules = $query->get();
        
        $applicableRules = new Collection();

        foreach ($allRules as $rule) {
            if ($this->isRuleApplicable($rule, $user, $customer)) {
                $applicableRules->push($rule);
            }
        }

        if ($applicableRules->isEmpty()) {
            return null;
        }

        // Apply Specificity Hierarchy
        // 1. User Rules
        $userRules = $applicableRules->where('target_type', 'user');
        if ($userRules->isNotEmpty()) {
            return $userRules->sortByDesc('limit_percent')->first();
        }

        // 2. Customer Group Rules
        $groupRules = $applicableRules->where('target_type', 'customer_group');
        if ($groupRules->isNotEmpty()) {
            return $groupRules->sortByDesc('limit_percent')->first();
        }

        // 3. Role Rules
        $roleRules = $applicableRules->where('target_type', 'role');
        if ($roleRules->isNotEmpty()) {
            return $roleRules->sortByDesc('limit_percent')->first();
        }

        // 4. Global Rules
        $globalRules = $applicableRules->where('target_type', 'global');
        if ($globalRules->isNotEmpty()) {
            return $globalRules->sortByDesc('limit_percent')->first();
        }

        return null;
    }

    /**
     * Check if a specific rule applies to a user/customer context.
     *
     * @param DiscountRule $rule
     * @param User|null $user
     * @param Customer|null $customer
     * @return boolean
     */
    protected function isRuleApplicable(DiscountRule $rule, ?User $user, ?Customer $customer): bool
    {
        switch ($rule->target_type) {
            case 'global':
                return true;
                
            case 'role':
                if (!$user) return false;
                return $user->roles()->where('id', $rule->target_id)->exists();
                
            case 'user':
                if (!$user) return false;
                return $user->id == $rule->target_id;

            case 'customer_group':
                if (!$customer) {
                    if ($user && $user->customer) {
                        $customer = $user->customer;
                    }
                }
                if (!$customer || !$customer->group_id) return false;
                return $customer->group_id == $rule->target_id;
                
            default:
                return false;
        }
    }

    /**
     * Get the approval threshold percent.
     * 
     * @param User|null $user
     * @param Customer|null $customer
     * @return float
     */
    public function getApprovalThreshold(?User $user, ?Customer $customer = null): float
    {
        $default = 15.0; 
        
        $rule = $this->getBestApplicableRule($user, $customer, 'approval_threshold');
        
        return $rule ? (float) $rule->limit_percent : $default;
    }

    /**
     * Get the max discount percent that applies to the TOTAL (Manual + Promo).
     * 
     * @param User|null $user
     * @param Customer|null $customer
     * @return float|null Returns null if no limit applies.
     */
    public function getTotalMaxDiscount(?User $user, ?Customer $customer = null): ?float
    {
        // We need to fetch 'max_discount' rules that have apply_to_total=true
        // But getBestApplicableRule filters by rule_type only.
        // We should replicate logic or filter after?
        // Let's filter manually.
        
        $allRules = DiscountRule::where('active', true)
            ->where('rule_type', 'max_discount')
            ->where('apply_to_total', true)
            ->get();
            
        $applicable = new Collection();
        foreach ($allRules as $rule) {
            if ($this->isRuleApplicable($rule, $user, $customer)) {
                $applicable->push($rule);
            }
        }
        
        if ($applicable->isEmpty()) {
            return null;
        }
        
        // Use same hierarchy
        // User
        $userRules = $applicable->where('target_type', 'user');
        if ($userRules->isNotEmpty()) return (float) $userRules->sortByDesc('limit_percent')->first()->limit_percent;
        
        // Group
        $groupRules = $applicable->where('target_type', 'customer_group');
        if ($groupRules->isNotEmpty()) return (float) $groupRules->sortByDesc('limit_percent')->first()->limit_percent;
        
        // Role
        $roleRules = $applicable->where('target_type', 'role');
        if ($roleRules->isNotEmpty()) return (float) $roleRules->sortByDesc('limit_percent')->first()->limit_percent;
        
        // Global
        $globalRules = $applicable->where('target_type', 'global');
        if ($globalRules->isNotEmpty()) return (float) $globalRules->sortByDesc('limit_percent')->first()->limit_percent;
        
        return null;
    }

    /**
     * Get the max discount percent.
     * 
     * @param User|null $user
     * @param Customer|null $customer
     * @return float
     */
    public function getMaxDiscount(?User $user, ?Customer $customer = null): float
    {
        $default = 20.0;
        
        $rule = $this->getBestApplicableRule($user, $customer, 'max_discount');
        
        return $rule ? (float) $rule->limit_percent : $default;
    }
}
