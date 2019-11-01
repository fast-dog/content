<?php
namespace FastDog\Content\Models;


use FastDog\User\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Канонические ссылки
 *
 * @package FastDog\Content\Models
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
     * @var array $fillable
     */
    public $fillable = [self::ITEM_ID, self::TYPE, self::LINK, self::IS_INDEX, self::SITE_ID];

    /**
     * Отношение к результату проверки
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function check()
    {
        return $this->hasOne('FastDog\Content\Models\ContentCanonicalCheckResult', 'item_id', 'id');
    }

    /**
     * Отношение к материалу
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function content()
    {
        return $this->hasOne('FastDog\Content\Models\Content', 'id', 'item_id');
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
}