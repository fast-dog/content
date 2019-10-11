<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 14.02.2017
 * Time: 23:36
 */

namespace FastDog\Content\Entity;


use App\Modules\Users\Entity\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Канонические ссылки
 *
 * @package FastDog\Content\Entity
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCanonical extends Model
{
    /**
     * Идентификатор материала
     * @const string
     */
    const ITEM_ID = 'item_id';
    /**
     * Тип
     * @const string
     */
    const TYPE = 'type';
    /**
     * Ссылка
     * @const string
     */
    const LINK = 'link';
    /**
     * Главная
     * @const string
     * @deprecated
     */
    const IS_INDEX = 'is_index';
    /**
     * Код сайта
     * @const string
     */
    const SITE_ID = 'site_id';
    /**
     * Тип: категрия
     * @const int
     */
    const TYPE_MENU_CATEGORY_BLOG = 1;
    /**
     * Тип: страница материала
     * @const int
     */
    const TYPE_MENU_CONTENT = 2;
    /**
     * Название таблицы
     * @var string $table
     */
    public $table = 'content_canonical';

    /**
     * Массив полей автозаполнения
     *
     * @var array $fillable
     */
    public $fillable = [self::ITEM_ID, self::TYPE, self::LINK, self::IS_INDEX, self::SITE_ID];

    /**
     * Отношение к результату проверки
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function check()
    {
        return $this->hasOne('FastDog\Content\Entity\ContentCanonicalCheckResult', 'item_id', 'id');
    }

    /**
     * Отношение к материалу
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function content()
    {
        return $this->hasOne('FastDog\Content\Entity\Content', 'id', 'item_id');
    }

    /**
     * Обновление канонической сылки
     *
     * Обновление канонической сылки при изменение параметров пунктов меню с типом: content_blog
     *
     * @param ContentCategory $item
     * @param Content $contentItem
     * @param string $newLink
     *
     * @return array
     */
    public static function ContentCanonicalCheckCategoryBlog(ContentCategory $item, Content $contentItem, $newLink)
    {
        /**
         * @var $user User
         */
        $user = \Auth::getUser();
        $canonicalAction = [
            'update' => false,
            'create' => false,
        ];
        $check = ContentCanonical::where([
            ContentCanonical::TYPE => ContentCanonical::TYPE_MENU_CATEGORY_BLOG,
            ContentCanonical::ITEM_ID => $contentItem->id,
            ContentCanonical::LINK => $newLink,
        ])->first();

        if ($check) {
            if ($check->{ContentCanonical::LINK} !== $newLink) {
                ContentCanonical::where([
                    ContentCanonical::TYPE => ContentCanonical::TYPE_MENU_CATEGORY_BLOG,
                    ContentCanonical::ITEM_ID => $contentItem->id,
                ])->update([
                    ContentCanonical::LINK => $newLink,
                ]);
                $canonicalAction['update'] = true;
            }
        } else {
            ContentCanonical::create([
                ContentCanonical::TYPE => ContentCanonical::TYPE_MENU_CATEGORY_BLOG,
                ContentCanonical::ITEM_ID => $contentItem->id,
                ContentCanonical::LINK => $newLink,
                ContentCanonical::SITE_ID => $contentItem->{ContentCanonical::SITE_ID},
            ]);
            $canonicalAction['create'] = true;
        }

        /**
         * Дополнительная проверка, необходимо обновить данные в объекте материала если ссылка установлена как активная
         */
        $data = $contentItem->getData();

        if (!isset($data['data']->canonical)) {
            $data['data'] = (object)$data['data'];
            $data['data']->canonical = '';
        }
        if ($data['data']->canonical !== $newLink) {
            $data['data']->canonical = $newLink;
            Content::where('id', $contentItem->id)->update([
                Content::DATA => json_encode($data['data']),
            ]);
        }

        return $canonicalAction;
    }

    /**
     * Обновление канонической сылки
     *
     * Обновление канонической сылки при изменение параметров пунктов меню с типом: content_blog
     * @param Content $contentItem
     * @param $newLink
     * @return array
     */
    public static function ContentCanonicalCheckContentItem(Content $contentItem, $newLink)
    {
        /**
         * @var $user User
         */
        $user = \Auth::getUser();
        $canonicalAction = [
            'update' => false,
            'create' => false,
        ];
        $check = ContentCanonical::where([
            ContentCanonical::TYPE => ContentCanonical::TYPE_MENU_CONTENT,
            ContentCanonical::ITEM_ID => $contentItem->id,
        ])->first();

        if ($check) {
            if ($check->{ContentCanonical::LINK} !== $newLink) {
                ContentCanonical::where([
                    ContentCanonical::TYPE => ContentCanonical::TYPE_MENU_CONTENT,
                    ContentCanonical::ITEM_ID => $contentItem->id,
                ])->update([
                    ContentCanonical::LINK => $newLink,
                ]);
                $canonicalAction['update'] = true;
            }
        } else {
            ContentCanonical::create([
                ContentCanonical::TYPE => ContentCanonical::TYPE_MENU_CONTENT,
                ContentCanonical::ITEM_ID => $contentItem->id,
                ContentCanonical::LINK => $newLink,
                ContentCanonical::SITE_ID => $contentItem->{ContentCanonical::SITE_ID},
            ]);
            $canonicalAction['create'] = true;
        }

        /**
         * Дополнительная проверка, необходимо обновить данные в объекте материала если ссылка установлена как активная
         */
        $data = $contentItem->getData();
        if (!isset($data['data']->canonical)) {
            $data['data']->canonical = '';
        }
        if ($data['data']->canonical !== $newLink) {
            $data['data']->canonical = $newLink;
            Content::where('id', $contentItem->id)->update([
                Content::DATA => json_encode($data['data']),
            ]);
        }

        return $canonicalAction;
    }

    /**
     * Создание таблицы базы данных
     *
     * Будут созданы таблицы и триггеры:
     * <pre>
     * CREATE TABLE test_migration.content_canonical (
     *          id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     *          is_index tinyint(4) NOT NULL,
     *          item_id int(11) NOT NULL,
     *          type tinyint(4) NOT NULL,
     *          link varchar(255) NOT NULL,
     *          site_id char(3) NOT NULL DEFAULT '000',
     *          created_at timestamp NULL DEFAULT NULL,
     *          updated_at timestamp NULL DEFAULT NULL,
     *          deleted_at timestamp NULL DEFAULT NULL,
     *          PRIMARY KEY (id),
     *          UNIQUE INDEX UK_content_canonical (item_id, type, link)
     * )
     * COMMENT = 'Канонические ссылки' ;
     * </pre>
     *
     * @return void
     */
    public static function createDbSchema()
    {
        if (!Schema::hasTable('content_canonical')) {
            Schema::create('content_canonical', function (Blueprint $table) {
                $table->increments('id');
                $table->tinyInteger(self::IS_INDEX);
                $table->integer(self::ITEM_ID);
                $table->tinyInteger(self::TYPE);
                $table->string(self::LINK);
                $table->char(Content::SITE_ID, 3)->default('000');
                $table->timestamps();
                $table->softDeletes();
                $table->unique([self::ITEM_ID, self::TYPE, self::LINK], 'UK_content_canonical');
            });
            DB::statement("ALTER TABLE `content_canonical` comment 'Канонические ссылки'");

            DB::unprepared("DROP FUNCTION IF EXISTS selectCountContentCanonical");
            DB::unprepared("
CREATE FUNCTION selectCountContentCanonical(item_id INT)
  RETURNS int(11)
  DETERMINISTIC
  SQL SECURITY INVOKER
BEGIN
RETURN (SELECT COUNT(*) FROM content_canonical cc WHERE cc.item_id = item_id);
END
        ");
        }
    }
}