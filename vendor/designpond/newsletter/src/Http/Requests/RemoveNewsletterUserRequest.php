<?php

namespace designpond\newsletter\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Validator;

class RemoveNewsletterUserRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (\Auth::check())
        {
            return true;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email'      => 'required|email',
            'activation' => 'required_with:newsletter_id'
        ];
    }

}
