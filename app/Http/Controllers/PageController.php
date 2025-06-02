<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use Illuminate\Http\Request; // Request is used, so keep it.
// use Illuminate\Support\Facades\App; // Not strictly needed for this version

class PageController extends Controller
{
    /**
     * Display the specified static page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StaticPage  $staticPage  (Route model binding by slug)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request, StaticPage $staticPage)
    {
        // Check if the page is published
        if (!$staticPage->is_published) {
            abort(404); 
        }

        // The HasTranslations trait on StaticPage model will handle returning
        // translations for the current application locale automatically
        // when accessing $staticPage->title, $staticPage->content etc.

        return view('pages.show', compact('staticPage'));
    }
}
