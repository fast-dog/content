<?php

namespace FastDog\Content\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Добавление комментария
 *
 * @package FastDog\Content\Http\Request
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class AddContentComment extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!\Auth::guest()) {
                return true;
            }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //'item_id' => 'required',
            //'email' => 'required|email',
            'text' => 'required',
            //'g-recaptcha-response' => 'required|captcha',
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
//            'text.required' => 'Поле "Комментарий" обязательно для заполнения.',
//            'g-recaptcha-response.captcha' => 'Поле "Я не робот" обязательно для заполнения.',
        ];
    }

}
