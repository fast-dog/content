<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 26.02.2017
 * Time: 1:09
 */

namespace FastDog\Content\Entity;

use Baum\Node;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Комментарии
 *
 * @package FastDog\Content\Entity
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
        return $this->hasOne('App\Modules\Users\Entity\User', 'id', 'user_id');
    }

    /**
     * Создание таблицы базы данных
     *
     * Будут созданы таблицы и триггеры:
     * <pre>
     * CREATE TABLE content_comments (
     *          id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     *          item_id int(11) NOT NULL COMMENT 'Идентификатор материала',
     *          lft int(11) NOT NULL,
     *          rft int(11) NOT NULL,
     *          depth int(11) NOT NULL,
     *          parent_id int(11) NOT NULL,
     *          site_id char(3) NOT NULL DEFAULT '000',
     *          state tinyint(4) NOT NULL DEFAULT 0,
     *          created_at timestamp NULL DEFAULT NULL,
     *          updated_at timestamp NULL DEFAULT NULL,
     *          deleted_at timestamp NULL DEFAULT NULL,
     *          text text NOT NULL,
     *          data json NOT NULL,
     *          PRIMARY KEY (id),
     *          INDEX IDX_content_site_id (site_id)
     * )
     * COMMENT = 'Комментарии материалов' ;
     * </pre>
     *
     * @return void
     */
    public static function createDbSchema()
    {
        if (!Schema::hasTable('content_comments')) {
            Schema::create('content_comments', function (Blueprint $table) {
                $table->increments('id');
                $table->integer(self::ITEM_ID)->comment('Идентификатор материала');
                $table->integer('lft');
                $table->integer('rgt');
                $table->integer('depth');
                $table->integer('parent_id');
                $table->char(ContentComments::SITE_ID, 3)->default('000');
                $table->tinyInteger(ContentComments::STATE)->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->text('text');
                $table->json(ContentComments::DATA);
                $table->index(ContentComments::SITE_ID, 'IDX_content_site_id');
            });
            DB::statement("ALTER TABLE `content_comments` comment 'Комментарии материалов'");

            DB::unprepared("DROP TRIGGER IF EXISTS content_comment_after_update");

            $user = config('database.connections.mysql.username');
            $host = config('database.connections.mysql.host');

            DB::unprepared("
CREATE 
	DEFINER = '{$user}'@'{$host}'
TRIGGER content_comment_after_update
	AFTER UPDATE
	ON content_comments
	FOR EACH ROW
BEGIN

IF(NEW.lft = 1) THEN
set @count = (NEW.rgt - NEW.lft - 1) /2;
UPDATE content set count_comment=@count WHERE id = NEW.item_id LIMIT 1;
END IF;

END
            ");
        }
    }
}