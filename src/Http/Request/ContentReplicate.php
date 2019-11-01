<?php

namespace FastDog\Content\Http\Request;

use FastDog\Content\Models\Content;
use FastDog\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Копирование материалов
 *
 * @package FastDog\Content\Http\Request
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentReplicate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!\Auth::guest()) {
            $user = \Auth::getUser();
            if ($user->type == User::USER_TYPE_ADMIN) {
                return true;
            }
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
            'name' => 'required',
            'alias' => 'required',
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Поле "Название" обязательно для заполнения.',
            'alias.required' => 'Поле "Псевдоним" обязательно для заполнения.',
        ];
    }

    /**
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();
        $validator->after(function () use ($validator) {
            $input = $this->all();
            if (!$input['id']) {
                $validator->errors()->add('instance', 'Новый материал нельзя копировать, сохраните и попробуйте снова.');
            } else {
                $item = Content::find($input['id']);
                if (!$item) {
                    $validator->errors()->add('instance', 'Не удалось найти материал для копирования, обновите страницу и попробуйте снова.');
                }
            }
        });
        return $validator;
    }
}
