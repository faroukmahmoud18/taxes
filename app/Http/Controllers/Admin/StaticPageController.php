<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use App\Http\Requests\Admin\StoreStaticPageRequest;
use App\Http\Requests\Admin\UpdateStaticPageRequest;

class StaticPageController extends Controller
{
    public function index()
    {
        $pages = StaticPage::latest()->paginate(10);
        return view('admin.static-pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.static-pages.create');
    }

    public function store(StoreStaticPageRequest $request)
    {
        $validatedData = $request->validated();
        $page = new StaticPage();
        $page->slug = $validatedData['slug'];
        // Handle boolean is_published (checkboxes are not sent if unchecked)
        $page->is_published = $request->has('is_published'); 

        foreach (['en', 'de', 'ar'] as $locale) {
            $page->setTranslation('title', $locale, $validatedData['title'][$locale]);
            $page->setTranslation('content', $locale, $validatedData['content'][$locale]);
            if (isset($validatedData['meta_keywords'][$locale])) {
                $page->setTranslation('meta_keywords', $locale, $validatedData['meta_keywords'][$locale]);
            }
            if (isset($validatedData['meta_description'][$locale])) {
                $page->setTranslation('meta_description', $locale, $validatedData['meta_description'][$locale]);
            }
        }
        $page->save();
        return redirect()->route('admin.static-pages.index')->with('success', 'Static page created successfully.');
    }

    public function show(StaticPage $staticPage) 
    {
        return view('admin.static-pages.show', compact('staticPage'));
    }

    public function edit(StaticPage $staticPage)
    {
        return view('admin.static-pages.edit', compact('staticPage'));
    }

    public function update(UpdateStaticPageRequest $request, StaticPage $staticPage)
    {
        $validatedData = $request->validated();
        $staticPage->slug = $validatedData['slug'];
        $staticPage->is_published = $request->has('is_published');

        foreach (['en', 'de', 'ar'] as $locale) {
            $staticPage->setTranslation('title', $locale, $validatedData['title'][$locale]);
            $staticPage->setTranslation('content', $locale, $validatedData['content'][$locale]);
            
            $staticPage->setTranslation('meta_keywords', $locale, $validatedData['meta_keywords'][$locale] ?? null);
            $staticPage->setTranslation('meta_description', $locale, $validatedData['meta_description'][$locale] ?? null);
        }
        $staticPage->save();
        return redirect()->route('admin.static-pages.index')->with('success', 'Static page updated successfully.');
    }

    public function destroy(StaticPage $staticPage)
    {
        $staticPage->delete(); // StaticPage model does not have SoftDeletes by default
        return redirect()->route('admin.static-pages.index')->with('success', 'Static page deleted successfully.');
    }
}
