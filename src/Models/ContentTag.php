<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 31.01.2017
 * Time: 14:46
 */

namespace FastDog\Content\Entity;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Теги материалов
 *
 * Реализация тегов для поиска по материалам
 *
 * @package FastDog\Content\Entity
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

    /**
     * Создание таблицы базы данных
     *
     * Будут созданы таблицы и триггеры:
     * <pre>

     * </pre>
     *
     * @return void
     */
    public static function createDbSchema()
    {
        if (!Schema::hasTable('content_tag')) {
            Schema::create('content_tag', function (Blueprint $table) {
                $table->increments('id');
                $table->string('text');
                $table->integer('item_id');

            });
            DB::statement("ALTER TABLE `content_tag` comment 'Теги материалов'");
        }
    }
}