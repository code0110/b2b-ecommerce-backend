<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\SalesRepresentative;
use Illuminate\Http\Request;

class SalesRepresentativeController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesRepresentative::where('is_active', true)->orderBy('sort_order');

        if ($region = $request->get('region')) {
            $query->where('region', $region);
        }

        if ($county = $request->get('county')) {
            $query->whereJsonContains('counties', $county);
        }

        return $query->get();
    }
}
