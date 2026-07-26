<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function show($tripId)
    {
        $budget = DB::table('trip_budgets')
            ->where('trip_id', $tripId)
            ->first();
            
        if (!$budget) {
            return response()->json([
                'total_budget' => 0,
                'currency' => 'USD',
                'categories' => []
            ]);
        }
        
        $budget->categories = $budget->categories ? json_decode($budget->categories) : [];
        return response()->json($budget);
    }

    public function update(Request $request, $tripId)
    {
        $request->validate([
            'total_budget' => 'required|numeric',
        ]);

        DB::table('trip_budgets')->updateOrInsert(
            ['trip_id' => $tripId],
            [
                'total_budget' => $request->total_budget,
                'currency' => $request->currency ?? 'USD',
                'categories' => $request->has('categories') ? json_encode($request->categories) : null,
                'updated_at' => now(),
            ]
        );

        return $this->show($tripId);
    }
}
