<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:id,su,en'],
        ]);

        $request->session()->put('locale', $validated['locale']);
        App::setLocale($validated['locale']);

        $referer = $request->headers->get('referer');
        if ($referer) {
            $refererUrl = parse_url($referer);
            if (
                isset($refererUrl['host'])
                && strcasecmp($refererUrl['host'], $request->getHost()) === 0
            ) {
                $path = $refererUrl['path'] ?? '/';
                $query = isset($refererUrl['query']) ? '?'.$refererUrl['query'] : '';

                return redirect()->to($path.$query);
            }
        }

        return redirect()->route('home');
    }
}
