<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function updateDraft(Request $request)
    {
        $draft = $request->session()->get('trip_draft');
        abort_unless(
            is_array($draft) && ($draft['version'] ?? null) === 1 && ! empty($draft['plan']['stops']),
            422,
            'Buat rencana perjalanan terlebih dahulu.'
        );

        $validated = $request->validate([
            'durations' => ['required', 'array'],
            'durations.*' => ['required', 'integer', 'between:15,480'],
        ]);
        $stopIds = array_map(fn (array $stop) => (string) $stop['id'], $draft['plan']['stops']);
        if (array_diff(array_keys($validated['durations']), $stopIds) || array_diff($stopIds, array_keys($validated['durations']))) {
            abort(422, 'Daftar durasi tidak sesuai dengan itinerary.');
        }

        $visitMinutes = 0;
        $dailyVisitMinutes = [];
        foreach ($draft['plan']['stops'] as &$stop) {
            $stop['visit_duration_minutes'] = (int) $validated['durations'][$stop['id']];
            $visitMinutes += $stop['visit_duration_minutes'];
            $dailyVisitMinutes[$stop['day']] = ($dailyVisitMinutes[$stop['day']] ?? 0) + $stop['visit_duration_minutes'];
        }
        unset($stop);

        $dailyMinutes = [];
        foreach ($dailyVisitMinutes as $day => $duration) {
            $dailyMinutes[$day] = $duration + ($draft['plan']['daily_travel_minutes'][$day] ?? 0);
            if ($dailyMinutes[$day] > 8 * 60) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'durations' => 'Durasi hari '.$day.' melebihi batas itinerary 8 jam termasuk waktu perjalanan.',
                ]);
            }
        }

        $draft['plan']['visit_minutes'] = $visitMinutes;
        $draft['plan']['daily_minutes'] = $dailyMinutes;
        $draft['plan']['total_minutes'] = $visitMinutes + array_sum($draft['plan']['daily_travel_minutes']);
        $request->session()->put('trip_draft', $draft);

        return redirect()->to(route('home').'#rencana')->with('status', 'Durasi kunjungan itinerary berhasil diperbarui.');
    }

    public function index(Request $request)
    {
        $trips = $request->user()->trips()->withCount('stops')->latest()->paginate(10);

        return view('trips.index', compact('trips'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
        ]);
        $draft = $request->session()->get('trip_draft');
        abort_unless(
            is_array($draft) && ($draft['version'] ?? null) === 1 && ! empty($draft['plan']['stops']),
            422,
            'Buat rencana perjalanan terlebih dahulu.'
        );

        $trip = DB::transaction(function () use ($request, $validated, $draft) {
            $trip = $request->user()->trips()->create([
                'title' => $validated['title'],
                'origin_name' => $draft['origin']['name'],
                'origin_latitude' => $draft['origin']['latitude'],
                'origin_longitude' => $draft['origin']['longitude'],
                'days' => $draft['form']['days'],
                'travelers' => $draft['form']['travelers'],
                'budget_per_person' => $draft['form']['budget'],
                'total_cost' => $draft['plan']['costs']['group_total'],
                'start_date' => $draft['form']['travel_date'] ?? null,
                'total_distance_km' => $draft['plan']['distance_km'],
                'total_duration_minutes' => $draft['plan']['total_minutes'],
                'transport_name' => $draft['plan']['transport']['name'],
                'accommodation_name' => $draft['plan']['accommodation']['name'] ?? null,
                'cost_breakdown' => $draft['plan']['costs'],
                'preferences' => [
                    'category_id' => $draft['form']['category_id'] ?? null,
                    'region' => $draft['form']['region'] ?? null,
                    'preference' => $draft['form']['preference'] ?? 'balanced',
                    'transport_option' => $draft['plan']['transport']['name'] ?? null,
                    'transport_type' => $draft['plan']['transport']['type'] ?? '',
                    'accommodation' => $draft['plan']['accommodation']['name'] ?? null,
                ],
            ]);

            foreach ($draft['plan']['stops'] as $stop) {
                $trip->stops()->create([
                    'destination_id' => $stop['id'],
                    'day' => $stop['day'],
                    'sequence' => $stop['sequence'],
                    'destination_name' => $stop['name'],
                    'region' => $stop['region'],
                    'latitude' => $stop['latitude'],
                    'longitude' => $stop['longitude'],
                    'admission_fee' => $stop['admission_fee'],
                    'visit_duration_minutes' => $stop['visit_duration_minutes'],
                    'rating' => $stop['rating'],
                    'leg_distance_km' => $stop['leg_distance_km'],
                    'leg_minutes' => $stop['leg_minutes'],
                ]);
            }

            return $trip;
        });

        $request->session()->forget('trip_draft');

        return redirect()->route('trips.show', $trip)->with('status', 'Rencana perjalanan berhasil disimpan.');
    }

    public function show(Request $request, Trip $trip)
    {
        abort_unless($trip->user_id === $request->user()->id, 403);
        $trip->load('stops');

        return view('trips.show', compact('trip'));
    }

    public function updateActualCost(Request $request, Trip $trip)
    {
        abort_unless($trip->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'actual_cost' => ['required', 'integer', 'min:1', 'max:1000000000'],
        ]);
        $trip->actual_cost = $validated['actual_cost'];
        $trip->save();

        return back()->with('status', 'Biaya aktual tersimpan sebagai data pelatihan model prediksi.');
    }

    public function destroy(Request $request, Trip $trip)
    {
        abort_unless($trip->user_id === $request->user()->id, 403);
        $trip->delete();

        return redirect()->route('trips.index')->with('status', 'Rencana perjalanan dihapus.');
    }
}
