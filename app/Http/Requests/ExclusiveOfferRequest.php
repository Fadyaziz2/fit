<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExclusiveOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $method = strtolower($this->method());

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'button_text' => 'nullable|string|max:150',
            'button_url' => 'nullable|url',
            'status' => 'required|in:active,inactive',
        ];

        $imageRule = 'nullable';
        if ($method === 'post') {
            $imageRule = 'required';
        }

        $rules['offer_image'] = $imageRule . '|image|mimes:jpg,jpeg,png,webp|max:5120';

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => true,
            'message' => $validator->errors()->first(),
            'all_message' => $validator->errors(),
        ];

        if (request()->is('api*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
