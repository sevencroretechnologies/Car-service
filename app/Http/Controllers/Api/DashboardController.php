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
        $user = auth()->user();

        // Helper to apply filters
        $applyFilters = function ($query) use ($user) {
            if ($user->org_id) {
                $query->where('org_id', $user->org_id);
            }
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
            return $query;
        };

        // General stats
        $totalCustomers = $applyFilters(Customer::query())->count();
        $totalVehicles = $applyFilters(CustomerVehicle::query())->count();
        $totalServices = $applyFilters(Service::query())->count();
        
        // Branch logic: strict filter if branch_id is set
        $branchQuery = Branch::query();
        if ($user->org_id) {
            $branchQuery->where('org_id', $user->org_id);
        }
        if ($user->branch_id) {
            $branchQuery->where('id', $user->branch_id);
        }
        $totalBranches = $branchQuery->count();

        // Recent activity (Last 5 registered customers)
        $recentCustomers = $applyFilters(Customer::query())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'name', 'created_at']);

        // Service distribution for charts (optional/future)
        // Groups vehicles by type
        $vehiclesByType = $applyFilters(CustomerVehicle::query())
             ->select('vehicle_type_id', DB::raw('count(*) as total'))
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
                    'new_this_month' => $applyFilters(Customer::query())->whereMonth('created_at', now()->month)->count(),
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
