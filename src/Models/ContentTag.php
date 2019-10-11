<?php

namespace FastDog\Content\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Теги материалов
 *
 * Реализация тегов для поиска по материалам
 *
 * @package FastDog\Content\Models
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentTag extends Model
{
    /**
     * Идентификатор материала
     * @const string
     */
    const ITEM_ID = 'item_id';

    /**
     * Тег
     * @const string
     */
    const TEXT = 'text';

    /**
     * Назнвание таблицы
     *
     * @var string $table
     */
    public $table = 'content_tag';

    /**
     * Массив полей автозаполнения
     *
     * @var array $fillable
     */
    public $fillable = [self::ITEM_ID, self::TEXT];

    /**
     * Использование полей дата-время
     *
     * @var bool $timestamps
     */
    public $timestamps = false;
}