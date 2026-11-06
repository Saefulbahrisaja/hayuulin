<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\DestinationVisitMetric;
use App\Services\TourismMlService;
use Illuminate\Http\Request;

class AdminMlController extends Controller
{
    public function index(TourismMlService $ml)
    {
        $destinations = Destination::where('is_verified', true)->orderBy('name')->get();
        $metrics = DestinationVisitMetric::with('destination')->latest('visited_on')->limit(30)->get();
        $visitorRows = DestinationVisitMetric::count();
        $actualCostRows = \App\Models\Trip::whereNotNull('actual_cost')->count();
        $costTrainingRows = $ml->costPredictor()['count'];

        return view('admin.machine-learning', compact(
            'destinations',
            'metrics',
            'visitorRows',
            'actualCostRows',
            'costTrainingRows'
        ));
    }

    public function storeVisitMetric(Request $request)
    {
        $validated = $request->validate([
            'destination_id' => ['required', 'integer', 'exists:destinations,id'],
            'visited_on' => ['required', 'date', 'before_or_equal:today'],
            'visitor_count' => ['required', 'integer', 'min:0', 'max:10000000'],
            'source' => ['required', 'string', 'max:160'],
            'is_holiday' => ['sometimes', 'boolean'],
        ]);
        $destination = Destination::findOrFail($validated['destination_id']);
        abort_unless($destination->is_verified, 422, 'Catat kunjungan hanya untuk destinasi terverifikasi.');

        DestinationVisitMetric::updateOrCreate(
            [
                'destination_id' => $destination->id,
                'visited_on' => $validated['visited_on'],
            ],
            [
                'recorded_by' => $request->user()->id,
                'visitor_count' => $validated['visitor_count'],
                'source' => $validated['source'],
                'is_holiday' => $request->boolean('is_holiday'),
            ]
        );

        return redirect()->route('admin.machine-learning.index')
            ->with('status', 'Data kunjungan tersimpan untuk pelatihan dan prediksi.');
    }
}
