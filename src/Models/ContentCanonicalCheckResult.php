<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 17.02.2017
 * Time: 12:25
 */

namespace FastDog\Content\Entity;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Проверка канонических ссылок
 *
 * @package FastDog\Content\Entity
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
     *
     * @var array $fillable
     */
    public $fillable = [self::ITEM_ID, self::SITE_ID, self::CODE];

    /**
     * Создание таблицы базы данных
     *
     * Будут созданы таблицы и триггеры:
     * <pre>
     * CREATE TABLE content_canonical_check_result (
     *          id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     *          item_id int(11) NOT NULL,
     *          code varchar(255) NOT NULL,
     *          site_id char(3) NOT NULL DEFAULT '000',
     *          created_at timestamp NULL DEFAULT NULL,
     *          updated_at timestamp NULL DEFAULT NULL,
     *          PRIMARY KEY (id),
     *          INDEX IDX_content_canonical_check_re (item_id, site_id),
     *          INDEX IDX_content_canonical_check_site_id (site_id)
     * )
     * COMMENT = 'Проверка доступности канонических ссылок' ;
     * </pre>
     *
     * @return void
     */
    public static function createDbSchema()
    {
        if (!Schema::hasTable('content_canonical_check_result')) {
            Schema::create('content_canonical_check_result', function (Blueprint $table) {
                $table->increments('id');
                $table->integer(self::ITEM_ID);
                $table->string(self::CODE);
                $table->char(Content::SITE_ID, 3)->default('000');
                $table->timestamps();
                $table->index(self::SITE_ID, 'IDX_content_canonical_check_site_id');
                $table->index([self::ITEM_ID, self::SITE_ID], 'IDX_content_canonical_check_re');
            });
            DB::statement("ALTER TABLE `content_canonical_check_result` comment 'Проверка доступности канонических ссылок'");
        }
    }
}