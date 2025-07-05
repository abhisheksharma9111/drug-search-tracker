<?php

namespace App\Http\Controllers;

use App\Services\RxNormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class DrugSearchController extends Controller
{
    protected $rxNormService;

    public function __construct(RxNormService $rxNormService)
    {
        $this->rxNormService = $rxNormService;
    }

    public function search(Request $request)
    {
        $executed = RateLimiter::attempt(
            'drug-search:' . $request->ip(),
            10,
            function () {},
            60
        );

        if (!$executed) {
            return response()->json(['message' => 'Too many requests'], 429);
        }

        $request->validate([
            'drug_name' => 'required|string|min:3'
        ]);

        $results = $this->rxNormService->searchDrugs($request->drug_name);


        if (!$results) {
            return response()->json(['message' => 'Failed to fetch drug information'], 500);
        }


        if ($results->isEmpty()) {
            return response()->json(['message' => 'No drugs found matching your search'], 404);
        }

        return response()->json($results);
    }
}
