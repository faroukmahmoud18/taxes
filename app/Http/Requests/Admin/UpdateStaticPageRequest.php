<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UpdateStaticPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->is_admin;
    }

    protected function prepareForValidation()
    {
        if (empty($this->slug) && !empty($this->input('title.en'))) {
            $this->merge([
                'slug' => Str::slug($this->input('title.en')),
            ]);
        } elseif (!empty($this->slug)) {
             $this->merge(['slug' => Str::slug($this->slug)]);
        }
    }

    public function rules(): array
    {
        // $this->route('static_page') will be the StaticPage model instance
        $pageId = $this->route('static_page')->id; 

        return [
            'title.en' => 'required|string|max:255',
            'title.de' => 'required|string|max:255',
            'title.ar' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:static_pages,slug,' . $pageId,
            'content.en' => 'required|string',
            'content.de' => 'required|string',
            'content.ar' => 'required|string',
            'is_published' => 'sometimes|boolean',
            'meta_keywords.en' => 'nullable|string|max:255',
            'meta_keywords.de' => 'nullable|string|max:255',
            'meta_keywords.ar' => 'nullable|string|max:255',
            'meta_description.en' => 'nullable|string|max:255',
            'meta_description.de' => 'nullable|string|max:255',
            'meta_description.ar' => 'nullable|string|max:255',
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.en.required' => 'The English title is required.',
            'title.de.required' => 'The German title is required.',
            'title.ar.required' => 'The Arabic title is required.',
            'content.en.required' => 'English content is required.',
            'content.de.required' => 'German content is required.',
            'content.ar.required' => 'Arabic content is required.',
            'slug.unique' => 'This slug is already taken. Please choose another.',
        ];
    }
}
