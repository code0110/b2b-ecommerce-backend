<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\SalesRepresentative;
use Illuminate\Http\Request;

class SalesRepresentativeController extends Controller
{
    /**
     * GET /api/sales-representatives
     * Filtre: ?region=...&county=...
     */
    public function index(Request $request)
    {
        $region = $request->query('region');
        $county = $request->query('county');

        $query = SalesRepresentative::query()
            ->where('is_active', true);

        if ($region) {
            $query->where('region', $region);
        }

        if ($county) {
            $query->where(function ($q) use ($county) {
                $q->whereJsonContains('counties', $county);
            });
        }

        $reps = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (SalesRepresentative $rep) {
                return [
                    'id'      => $rep->id,
                    'name'    => $rep->name,
                    'email'   => $rep->email,
                    'phone'   => $rep->phone,
                    'region'  => $rep->region,
                    'counties'=> $rep->counties ?? [],
                ];
            });

        // Construim liste de filtre din toți reprezentanții activi
        $allActive = SalesRepresentative::query()
            ->where('is_active', true)
            ->get();

        $regions = $allActive
            ->pluck('region')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $counties = $allActive
            ->pluck('counties')
            ->filter()
            ->flatMap(function ($c) {
                return is_array($c) ? $c : [];
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        return response()->json([
            'data'    => $reps,
            'filters' => [
                'regions' => $regions,
                'counties'=> $counties,
            ],
        ]);
    }
}
