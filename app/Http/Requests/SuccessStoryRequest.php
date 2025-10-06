<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SuccessStoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $method = strtolower($this->method());

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ];

        if ($method === 'post') {
            $rules['before_image'] = 'required';
            $rules['after_image'] = 'required';
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => true,
            'message' => $validator->errors()->first(),
            'all_message' => $validator->errors(),
        ];

        if (request()->is('api*') || $this->ajax()) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
