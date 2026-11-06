<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Services\NaiveBayesSentimentClassifier;
use Illuminate\Http\Request;

class DestinationReviewController extends Controller
{
    public function store(Request $request, Destination $destination, NaiveBayesSentimentClassifier $classifier)
    {
        abort_unless($destination->is_verified, 404);
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $sentiment = $classifier->classify($validated['body']);

        $destination->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'body' => $validated['body'],
                'sentiment' => $sentiment['sentiment'],
                'sentiment_confidence' => $sentiment['confidence'],
            ]
        );

        return redirect()->to(route('home').'#destinasi')
            ->with('review_status', 'Ulasan tersimpan. Analisis awal: '.$sentiment['sentiment'].' (keyakinan '.number_format($sentiment['confidence'] * 100, 1, ',', '.').'%).');
    }
}
