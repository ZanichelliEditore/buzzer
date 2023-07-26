<?php

namespace App\Http\Requests;

use App\Constants\Authentication;
use Illuminate\Foundation\Http\FormRequest;

class ChannelSubscribeRequest extends FormRequest
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
        $authenticationType = "" . Authentication::BASIC . "," . Authentication::OAUTH2 . "," . Authentication::NONE;

        return [
            'channel_id' => 'required|int',
            'endpoint' => 'required|string|max:50',
            'authentication' => 'required|string|in:' . $authenticationType,
            'username' => 'nullable|string|max:50|required_if:authentication,BASIC,OAUTH2',
            'password' => 'nullable|string|max:50|required_if:authentication,BASIC,OAUTH2'
        ];
    }
}
