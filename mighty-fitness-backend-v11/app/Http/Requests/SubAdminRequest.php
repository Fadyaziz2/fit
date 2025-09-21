<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class SubAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];
       
        $method = strtolower($this->method());
        $user_id = $this->route()->subadmin;

        switch ($method) {
            case 'post':
                $rules = [
                    'username' => 'required|unique:users,username',
                    'email' => 'required|max:191|email|unique:users',
                    'phone_number' => 'nullable|max:20|unique:users,phone_number',
                ];
            break;
            case 'patch':
                $rules = [
                    'username' => 'required|unique:users,username,'.$user_id,
                    'email' => 'required|max:191|email|unique:users,email,'.$user_id,
                    'phone_number' => 'nullable|max:20|unique:users,phone_number,'.$user_id,
                ];
            break;
        }

        return $rules;
    }

    public function messages()
    {
        return [
            
        ];
    }

     /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator){
        $data = [
            'status' => true,
            'message' => $validator->errors()->first(),
            'all_message' =>  $validator->errors()
        ];

        if ( request()->is('api*')){
           throw new HttpResponseException( response()->json($data,422) );
        }
        if ($this->ajax()) {
            throw new HttpResponseException(response()->json($data,422));
        } else {
            throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
        }
    }


}
