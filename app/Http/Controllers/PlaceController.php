<?php

namespace App\Http\Controllers;

use App\Services\PlaceService;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    protected $placeService;

    public function __construct(PlaceService $placeService)
    {
        $this->placeService = $placeService;
    }

    public function search(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'category' => 'nullable|string',
            'query' => 'nullable|string'
        ]);

        $places = $this->placeService->search(
            $request->lat,
            $request->lng,
            $request->category,
            $request->input('query')
        );

        return response()->json($places);
    }
    
    // Use the same search logic for popular/high-rated but filtered differently
        public function popular(Request $request)
    {
        $request->validate(['lat' => 'required', 'lng' => 'required']);
        // Use the dedicated service method
        return response()->json($this->placeService->getPopular($request->lat, $request->lng));
    }

    public function highRated(Request $request)
    {
        $request->validate(['lat' => 'required', 'lng' => 'required']);
        // Use the dedicated service method
        return response()->json($this->placeService->getHighRated($request->lat, $request->lng));
    }

        public function reverseGeocode(Request $request)
    {
        $request->validate(['lat' => 'required', 'lng' => 'required']);
        $address = $this->placeService->reverseGeocode($request->lat, $request->lng);
        return response()->json(['address' => $address]);
    }

     public function photo(Request $request)
    {
        $reference = $request->query('ref');
        if (!$reference) abort(404);

        // We use the PlaceService to fetch the raw image data
        return $this->placeService->getPhoto($reference);
    }

        public function autocomplete(Request $request)
    {
        $request->validate(['query' => 'required|min:2', 'lat' => 'required', 'lng' => 'required']);
        return response()->json($this->placeService->autocomplete(
            $request->input('query'), 
            $request->lat, 
            $request->lng
        ));
    }

        public function recommended(Request $request)
    {
        $request->validate(['lat' => 'required', 'lng' => 'required']);
        return response()->json(
            $this->placeService->getRecommended($request->lat, $request->lng, $request->user()->id)
        );
    }

}
