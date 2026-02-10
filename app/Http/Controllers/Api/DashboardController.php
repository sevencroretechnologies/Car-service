<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // General stats
        $totalCustomers = Customer::count();
        $totalVehicles = CustomerVehicle::count();
        $totalServices = Service::count();
        $totalBranches = Branch::count();

        // Recent activity (Last 5 registered customers)
        $recentCustomers = Customer::orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'name', 'created_at']);

        // Service distribution for charts (optional/future)
        // Groups vehicles by type
        $vehiclesByType = CustomerVehicle::select('vehicle_type_id', DB::raw('count(*) as total'))
             ->with('vehicleType:id,name') // Assuming relationship exists
             ->groupBy('vehicle_type_id')
             ->get();
        
        // Transform for frontend chart
        $vehicleTypeStats = $vehiclesByType->map(function ($item) {
            return [
                'name' => $item->vehicleType ? $item->vehicleType->name : 'Unknown',
                'value' => $item->total,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'employees' => [ // Keeping structure compatible for now, or we can change frontend interface
                    'total' => $totalCustomers, // Repurposing 'employees.total' -> 'customers.total' in frontend logic
                    'new_this_month' => Customer::whereMonth('created_at', now()->month)->count(),
                ],
                // New specialized data
                'stats' => [
                    'total_customers' => $totalCustomers,
                    'total_vehicles' => $totalVehicles,
                    'total_services' => $totalServices,
                    'total_branches' => $totalBranches,
                ],
                'recent_customers' => $recentCustomers,
                'vehicle_type_distribution' => $vehicleTypeStats,
            ]
        ]);
    }
}
