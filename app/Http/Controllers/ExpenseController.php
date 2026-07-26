<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index($tripId)
    {
        $expenses = DB::table('expenses')
            ->where('trip_id', $tripId)
            ->get();
        return response()->json($expenses);
    }

    public function store(Request $request, $tripId)
    {
        $request->validate([
            'category' => 'required|string',
            'description' => 'required|string',
            'amount' => 'required|numeric',
            'date' => 'required|date',
        ]);

        $id = DB::table('expenses')->insertGetId([
            'trip_id' => $tripId,
            'user_id' => $request->user()->id,
            'category' => $request->category,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency ?? 'USD',
            'date' => $request->date,
            'split_among' => $request->has('split_among') ? json_encode($request->split_among) : null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $expense = DB::table('expenses')->where('id', $id)->first();
        return response()->json($expense, 201);
    }

    public function destroy($id)
    {
        DB::table('expenses')->where('id', $id)->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
