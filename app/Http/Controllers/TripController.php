<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trip;
use Illuminate\Support\Facades\Validator;

class TripController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $query = auth()->user()->trips();
        
        // Recherche par date
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
        }
        
        // Pagination
        $perPage = $request->get('per_page', 10);
        $trips = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json($trips);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'destination' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'comment' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $trip = Trip::create(array_merge(
            $validator->validated(),
            ['user_id' => auth()->id()]
        ));

        return response()->json([
            'message' => 'Trip successfully created',
            'trip' => $trip
        ], 201);
    }

    public function show($id)
    {
        $trip = Trip::where('user_id', auth()->id())->find($id);
        
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        return response()->json($trip);
    }

    public function update(Request $request, $id)
    {
        $trip = Trip::where('user_id', auth()->id())->find($id);
        
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'destination' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'comment' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $trip->update($validator->validated());

        return response()->json([
            'message' => 'Trip successfully updated',
            'trip' => $trip
        ]);
    }

    public function destroy($id)
    {
        $trip = Trip::where('user_id', auth()->id())->find($id);
        
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $trip->delete();

        return response()->json(['message' => 'Trip successfully deleted']);
    }
}