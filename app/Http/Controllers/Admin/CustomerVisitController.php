<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerVisitController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $query = CustomerVisit::with(['customer', 'agent', 'orders', 'payments'])->orderByDesc('created_at');

        if ($user->hasRole('sales_agent')) {
            $query->where('agent_id', $user->id);
        } elseif ($user->hasRole('sales_director')) {
            $subordinates = $user->subordinates()->pluck('id');
            $subordinates->push($user->id);
            $query->whereIn('agent_id', $subordinates);
        }

        if ($agentId = $request->get('agent_id')) {
            $query->where('agent_id', $agentId);
        }
        
        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($date = $request->get('date')) {
            $query->whereDate('start_time', $date);
        }

        return response()->json($query->paginate(request('per_page', 20)));
    }

    public function startVisit(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'latitude'    => ['nullable', 'numeric'],
            'longitude'   => ['nullable', 'numeric'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $customer = Customer::findOrFail($data['customer_id']);
        
        // Close any ongoing visits for this user
        CustomerVisit::where('agent_id', $user->id)
            ->where('status', 'in_progress')
            ->update(['status' => 'completed', 'end_time' => now()]);

        // Calculate distance deviation
        $distanceDeviation = null;
        $isOffSite = false;

        if (!empty($data['latitude']) && !empty($data['longitude']) && 
            !empty($customer->latitude) && !empty($customer->longitude)) {
            
            $distanceDeviation = $this->calculateDistance(
                $data['latitude'], 
                $data['longitude'], 
                $customer->latitude, 
                $customer->longitude
            );
            
            // Assume 500m threshold
            if ($distanceDeviation > 500) {
                $isOffSite = true;
            }
        }

        $visit = CustomerVisit::create([
            'agent_id'    => $user->id,
            'customer_id' => $data['customer_id'],
            'start_time'  => now(),
            'status'      => 'in_progress',
            'latitude'    => $data['latitude'] ?? null,
            'longitude'   => $data['longitude'] ?? null,
            'distance_deviation' => $distanceDeviation,
            'is_off_site' => $isOffSite
        ]);

        return response()->json($visit, 201);
    }

    public function endVisit(Request $request, $id)
    {
        $visit = CustomerVisit::findOrFail($id);
        
        /** @var User $user */
        $user = Auth::user();

        // Check if user is the agent OR an admin OR a director of the agent
        $isAuthorized = false;

        if ($user->id === $visit->agent_id) {
            $isAuthorized = true;
        } elseif ($user->hasRole('admin')) {
            $isAuthorized = true;
        } elseif ($user->hasRole('sales_director')) {
            // Check if the visit agent is a subordinate of this director
            $agent = User::find($visit->agent_id);
            if ($agent && $agent->director_id === $user->id) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $updateData = [
            'status'    => 'completed',
            'end_time'  => now(),
            'notes'     => $data['notes'] ?? $visit->notes,
            'outcome'   => $data['outcome'] ?? null,
        ];

        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $updateData['end_latitude'] = $data['latitude'];
            $updateData['end_longitude'] = $data['longitude'];
        }

        $visit->update($updateData);

        return response()->json($visit);
    }

    public function recordLocation(Request $request, $id)
    {
        $visit = CustomerVisit::findOrFail($id);
        
        /** @var User $user */
        $user = Auth::user();

        if ($user->id !== $visit->agent_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($visit->status !== 'in_progress') {
            return response()->json(['message' => 'Visit is not in progress'], 400);
        }

        $data = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
            'battery_level' => ['nullable', 'integer'],
            'provider' => ['nullable', 'string'],
            'speed' => ['nullable', 'numeric'],
            'heading' => ['nullable', 'numeric'],
            'altitude' => ['nullable', 'numeric'],
            'network_type' => ['nullable', 'string'],
        ]);

        // Logică complexă de detecție anomalii
        $isMocked = false;
        
        // 1. Verificare acuratețe extremă (suspicioasă pentru GPS mobil, dar posibilă) sau foarte proastă
        if (isset($data['accuracy'])) {
             // Acuratețe suspect de perfectă (0) sau foarte proastă (>1000m)
             if ($data['accuracy'] === 0) {
                 // Unele dispozitive raportează 0 pe fake GPS
                 // $isMocked = true; // (Comentat pentru a evita false positives, dar e un indicator)
             }
        }

        // 2. Verificare viteză excesivă în timpul vizitei
        // Dacă viteza e > 30km/h (8.3 m/s) în timpul unei vizite "in_progress", agentul probabil a plecat
        if (isset($data['speed']) && $data['speed'] > 8.3) {
            // Putem marca vizita ca "moving_away" sau similar în viitor
            // Momentan doar logăm viteza
        }

        $visit->locationLogs()->create([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'battery_level' => $data['battery_level'] ?? null,
            'provider' => $data['provider'] ?? null,
            'speed' => $data['speed'] ?? null,
            'heading' => $data['heading'] ?? null,
            'altitude' => $data['altitude'] ?? null,
            'network_type' => $data['network_type'] ?? null,
            'is_mocked' => $isMocked,
        ]);

        // Actualizăm și ultima locație cunoscută pe vizită pentru acces rapid
        // Nu actualizăm latitude/longitude pe vizita părinte pentru a păstra punctul de start intact
        // Dar am putea folosi 'end_latitude' temporar sau câmpuri noi 'current_latitude'
        // Pentru moment, rapoartele citesc din logs (ceea ce e corect).
        
        // Totuși, putem verifica distanța față de client live
        if ($visit->customer) {
             $dist = $this->calculateDistance(
                 $data['latitude'], 
                 $data['longitude'], 
                 $visit->customer->latitude, 
                 $visit->customer->longitude
             );
             
             // Dacă e la mai mult de 1km, e clar "Off-site"
             if ($dist > 1000 && !$visit->is_off_site) {
                 $visit->update(['is_off_site' => true, 'distance_deviation' => $dist]);
             }
        }

        return response()->json(['status' => 'ok']);
    }
    
    /**
     * Calculate distance between two points in meters using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c);
    }
}
