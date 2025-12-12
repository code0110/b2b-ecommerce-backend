<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\SalesRepresentative;
use Illuminate\Http\Request;

class SalesRepresentativeController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesRepresentative::query()
            ->where('active', true)
            ->orderBy('region')
            ->orderBy('name');

        if ($region = $request->input('region')) {
            $query->where('region', $region);
        }

        if ($county = $request->input('county')) {
            $query->where(function ($q) use ($county) {
                $q->where('counties', 'like', '%' . $county . '%');
            });
        }

        $reps = $query->get();

        // extragem liste distincte pentru filtre
        $regions = SalesRepresentative::query()
            ->where('active', true)
            ->select('region')
            ->whereNotNull('region')
            ->distinct()
            ->orderBy('region')
            ->pluck('region')
            ->values();

        return response()->json([
            'filters' => [
                'regions' => $regions,
            ],
            'representatives' => $reps,
        ]);
    }
}
