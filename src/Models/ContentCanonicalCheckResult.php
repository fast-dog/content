<?php

namespace FastDog\Content\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Проверка канонических ссылок
 *
 * @package FastDog\Content\Models
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCanonicalCheckResult extends Model
{
    /**
     * Идентификатор ссылки
     * @const string
     */
    const ITEM_ID = 'item_id';

    /**
     * Код сайта
     * @const string
     */
    const SITE_ID = 'site_id';

    /**
     * Код HTTP ответа
     * @const string
     */
    const CODE = 'code';

    /**
     * Название таблицы
     * @var string $table
     */
    public $table = 'content_canonical_check_result';

    /**
     * Массив полей автозаполнения
     * @var array $fillable
     */
    public $fillable = [self::ITEM_ID, self::SITE_ID, self::CODE];

}