<?php

namespace FastDog\Content\Http\Request;

use FastDog\Content\Entity\Content;
use FastDog\Content\Entity\ContentCategory;
use App\Modules\Users\Entity\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Копирование категории
 *
 * @package FastDog\Content\Http\Request
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCategoryReplicate extends FormRequest
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
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Поле "Название" обязательно для заполнения.',
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
                $validator->errors()->add('instance', 'Новую категорию нельзя копировать, сохраните и попробуйте снова.');
            } else {
                $item = ContentCategory::find($input['id']);
                if (!$item) {
                    $validator->errors()->add('instance', 'Не удалось найти категорию для копирования, обновите страницу и попробуйте снова.');
                }
            }
        });
        return $validator;
    }
}
