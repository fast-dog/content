<?php

namespace FastDog\Content\Models;

use Baum\Node;

/**
 * Комментарии
 *
 * @package FastDog\Content\Models
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentComments extends Node
{
    /**
     * Идентификатор материала
     * @const string
     */
    const ITEM_ID = 'item_id';

    /**
     * Состояние
     * @const string
     */
    const STATE = 'state';

    /**
     * Текст
     * @const string
     */
    const TEXT = 'text';

    /**
     * Код сайта
     * @const string
     */
    const SITE_ID = 'site_id';

    /**
     * Дополнительные параметры
     * @const string
     */
    const DATA = 'data';

    /**
     * Идентификатор пользователя
     *
     * @const string
     */
    const USER_ID = 'user_id';

    /**
     * Поле объединения дерева в режиме мультисайта
     * @var array $scoped
     */
    protected $scoped = [self::ITEM_ID];

    /**
     * Массив полей преобразования даты-времени
     * @var array $dates
     */
    public $dates = ['deleted_at'];

    /**
     * Массив полей автозаполнения
     *
     * @var array $fillable
     */
    public $fillable = [self::ITEM_ID, self::SITE_ID, self::TEXT, self::DATA, self::STATE, self::USER_ID];

    /**
     * Отношение к пользователю
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne('App\Modules\Users\Models\User', 'id', 'user_id');
    }
}