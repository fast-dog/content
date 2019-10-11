<?php

namespace FastDog\Content\Entity;


use App\Core\Media\Traits\MediaTraits;
use App\Core\Properties\Interfases\PropertiesInterface;
use App\Core\Properties\Traits\PropertiesTrait;
use App\Core\Table\Filters\BaseFilter;
use App\Core\Table\Filters\Operator\BaseOperator;
use App\Core\Table\Interfaces\TableModelInterface;
use App\Core\Traits\StateTrait;
use App\Modules\Config\Entity\DomainManager;
use FastDog\Content\Events\Category\ContentCategoryAdminPrepare;
use Baum\Node;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Категории
 *
 * @package FastDog\Content\Entity
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCategory extends Node implements TableModelInterface, PropertiesInterface
{
    use StateTrait, PropertiesTrait, MediaTraits;

    /**
     * Состояние модели
     *
     * Возможные значения self::STATE_PUBLISHED, self::STATE_NOT_PUBLISHED, self::STATE_IN_TRASH
     *
     * @const int
     */
    const STATE = 'state';

    /**
     * Состояние: Опубликовано
     *
     * @const int
     */
    const STATE_PUBLISHED = 1;

    /**
     * Состояние: Не опубликовано
     *
     * @const int
     */
    const STATE_NOT_PUBLISHED = 0;

    /**
     * Состояние: В корзине
     *
     * @const int
     */
    const STATE_IN_TRASH = 2;

    /**
     * Идентификатор родительской категории
     *
     * @const string
     */
    const PARENT_ID = 'parent_id';

    /**
     * Глубина в дереве
     * @const string
     */
    const DEPTH = 'depth';

    /**
     * Код сайта
     * @const string
     */
    const SITE_ID = 'site_id';

    /**
     * Псевдоним
     * @const string
     */
    const ALIAS = 'alias';

    /**
     * Название
     * @const string
     */
    const NAME = 'name';
    /**
     * Дополнительные параметры
     * @const string
     */
    const DATA = 'data';

    /**
     * Название таблицы
     * @var string $table
     */
    public $table = 'content_category';

    /**
     * @var array $scoped
     */
    protected $scoped = [self::SITE_ID];

    /**
     * Массив полей автозаполнения
     *
     * @var array $fillable
     */
    public $fillable = [self::NAME, self::DATA, self::ALIAS, self::SITE_ID, self::STATE];

    /**
     * Список категорий
     *
     * для выпадающих списков
     *
     * @return array
     */
    public static function getList()
    {
        $result = [
            [
                'id' => '0', self::NAME => '-----------------',
            ],
        ];
        $scope = 'active';
        $fullName = DomainManager::checkIsDefault();
        $items = self::$scope()->get();
        foreach ($items as $item) {
            array_push($result, [
                'id' => $item->id,
                self::NAME => ($fullName) ? $item->{self::NAME} . ' (#'
                    . $item->{self::SITE_ID} . ')' : $item->{self::NAME},
            ]);
        }

        return $result;
    }

    /**
     * Возвращает детальную информацию о объекте
     *
     * @param bool $fireEvent
     * @return array
     */
    public function getData($fireEvent = true)
    {
        if (is_string($this->{self::DATA})) {
            $this->{self::DATA} = json_decode($this->{self::DATA});
        }
        $data = [
            'id' => $this->id,
            'lft' => $this->lfr,
            'rgt' => $this->rgt,
            self::NAME => $this->{self::NAME},
            self::DEPTH => $this->{self::DEPTH},
            self::PARENT_ID => $this->{self::PARENT_ID},
            self::ALIAS => $this->{self::ALIAS},
            self::STATE => $this->{self::STATE},
            'introtext' => (isset($this->{self::DATA}->{'introtext'})) ? $this->{self::DATA}->{'introtext'} : '',
            'fulltext' => (isset($this->{self::DATA}->{'fulltext'})) ? $this->{self::DATA}->{'fulltext'} : '',
            self::DATA => $this->{self::DATA},
            self::SITE_ID => $this->{self::SITE_ID},
            'extra' => trans('app.Псевдоним') . ': ' . $this->{self::ALIAS},
            'created_at' => ($this->created_at !== null) ? $this->created_at->format('Y-m-d') : '',
            'updated_at' => ($this->updated_at !== null) ? $this->updated_at->format('Y-m-d') : '',
            'published_at' => ($this->published_at !== null) ? $this->published_at->format('Y-m-d') : '',
        ];


        return $data;
    }

    /**
     * Кол-во позиций по сотояниям
     *
     * Метод возвращает статистическую информацию по модели
     *
     * <pre>
     *      [
     *          'total' => 'Общее количество записей',
     *          'published' => 'Опубликовано',
     *          'published_percent' => 'Опубликовано в процентном соотношение от общего количества',
     *          'not_published' => 'Не опубликовано',
     *          'not_published_percent' => 'Не опубликовано в процентном соотношение от общего количества',
     *          'in_trash' => 'В корзине',
     *          'in_trash_percent' => 'В корзине',
     *          'deleted' => 'Удалено',
     *          'deleted_percent' =>  'Удалено в процентном соотношение от общего количества',
     *          'cache_tags' => 'Поддержка тегов при кеширование'
     *      ];
     * </pre>
     * @return array
     */
    public static function getStatistic($fire_event = true)
    {
        $countPublished = self::where(function ($query) {
            $query->where(self::STATE, self::STATE_PUBLISHED);
        })->count();

        $countNotPublished = self::where(function ($query) {
            $query->where(self::STATE, self::STATE_NOT_PUBLISHED);
        })->count();

        $countInTrash = self::where(function ($query) {
            $query->where(self::STATE, self::STATE_IN_TRASH);
        })->count();

        $countDeleted = self::where(function ($query) {
            $query->where(self::SITE_ID, DomainManager::getSiteId());
        })->whereNotNull('deleted_at')->count();

        $total = self::where(function ($query) {

        })->count();

        $result = [
            'total' => $total,
            'published' => $countPublished,
            'published_percent' => ($total > 0) ? round((($countPublished * 100) / $total), 2) : 0,
            'not_published' => $countNotPublished,
            'not_published_percent' => ($total > 0) ? round((($countNotPublished * 100) / $total), 2) : 0,
            'in_trash' => $countInTrash,
            'in_trash_percent' => ($total > 0) ? round((($countInTrash * 100) / $total), 2) : 0,
            'deleted' => $countDeleted,
            'deleted_percent' => ($total > 0) ? round((($countDeleted * 100) / $total), 2) : 0,
            'cache_tags' => (env('CACHE_DRIVER') === 'redis') ? 'Y' : 'N',
        ];

        return $result;
    }

    /**
     * Пустой объект
     *
     * @param $fireEvent
     * @return array
     */
    public function getBlankData($fireEvent = true)
    {
        $data = [
            'id' => 0,
            self::NAME => '',
            self::DATA => [],
        ];

        return $data;
    }

    /**
     * Подготовка метаданных
     *
     * Подготовка метаданных для использования в публичной части
     *
     * @param array $data
     * @return array
     */
    public static function prepareMetadata(array $data)
    {
        $result = [
            'title' => (isset($data['title'])) ? $data['title'] : '',
        ];
        $media = $data['media'];
        if (isset($data['data'])) {
            $result['title'] = (isset($data['data']->meta_title)) ? $data['data']->meta_title : $data['name'];
            if (isset($data['data']->meta_description)) {
                $result['description'] = $data['data']->meta_description;
            } else {
                $result['description'] = str_limit(strip_tags($data['introtext']), 200);
            }
            if (isset($data['data']->meta_keywords)) {
                $result['keywords'] = $data['data']->meta_keywords;
            } else {
                $result['keywords'] = Content::top_words(strip_tags($data['introtext']) . strip_tags($data['fulltext']));
                $result['keywords'] = implode(', ', $result['keywords']);
            }
            $result['og'] = [
                'title' => $result['title'],
                'type' => 'blog',
                'url' => \Request::url(),
            ];
            if ($media) {
                $image = $media->where('type', 'file')->first();
                if ($image) {
                    $result['og']['image'] = $image['value'];
                    $result['image_src'] = $image['value'];
                }
            }
        }

        return $result;
    }

    /**
     * Создание таблицы базы данных
     *
     * Будут созданы таблицы и триггеры:
     * <pre>
     * CREATE TABLE  content_category (
     *          id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     *          name varchar(255) NOT NULL COMMENT 'Название',
     *          alias varchar(255) NOT NULL COMMENT 'Псевдоним',
     *          data json NOT NULL COMMENT 'Дополнительные параметры',
     *          state tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Состояние',
     *          site_id char(3) NOT NULL DEFAULT '000' COMMENT 'Код сайта',
     *          created_at timestamp NULL DEFAULT NULL,
     *          updated_at timestamp NULL DEFAULT NULL,
     *          deleted_at timestamp NULL DEFAULT NULL,
     *          PRIMARY KEY (id),
     *          INDEX IDX_content_category_site_id (site_id),
     *          UNIQUE INDEX UK_content_category_alias (alias, site_id)
     * )
     * COMMENT = 'Категории материалов';
     * </pre>
     *
     * @return void
     * @deprecated
     */
    public static function createDbSchema()
    {
        if (!Schema::hasTable('content_category')) {
            Schema::create('content_category', function (Blueprint $table) {
                $table->increments('id');
                $table->string(ContentCategory::NAME)->comment('Название');
                $table->string(ContentCategory::ALIAS)->comment('Псевдоним');
                $table->json(ContentCategory::DATA)->comment('Дополнительные параметры');
                $table->tinyInteger(ContentCategory::STATE)->default(ContentCategory::STATE_PUBLISHED)->comment('Состояние');
                $table->char(ContentCategory::SITE_ID, 3)->default('000')->comment('Код сайта');
                $table->index(ContentCategory::SITE_ID, 'IDX_content_category_site_id');
                $table->unique([ContentCategory::ALIAS, ContentCategory::SITE_ID], 'UK_content_category_alias');
                $table->timestamps();
                $table->softDeletes();

            });
            DB::statement("ALTER TABLE `content_category` comment 'Категории материалов'");
        }
    }

    /**
     * Список категорий
     *
     * Возвращает категории каталога для выпадающий списков с
     * учетом прав доступа пользователя
     *
     * @param bool $is_admin
     * @return array
     */
    public static function getCategoryList($is_admin = false)
    {
        $result = [];
        $scope = ($is_admin) ? 'defaultAdmin' : 'defaultSite';
        $roots = self::$scope()->where('lft', 1)->get();
        /**
         * @var $root self
         */
        foreach ($roots as $root) {
            if ($root) {
                $items = $root->descendantsAndSelf()->where(function ($query) use (&$scope) {

                })->$scope()->get();
                /**
                 * @var $item self
                 */
                foreach ($items as $item) {
                    $depth = (int)$item->getDepth();
                    $item->{self::NAME} = str_repeat('┊  ', $depth) . ' – ' .
                        $item->{self::NAME};
                    if ($item->getDepth() == 0 && DomainManager::checkIsDefault()) {
                        $item->{self::NAME} .= ' ( #' . $item->{self::SITE_ID} . ' )';
                    }
                    $children = [];
                    foreach ($item->children as $child) {
                        array_push($children, $child);
                    }
                    array_push($result, [
                        'id' => $item->id,
                        'name' => $item->{self::NAME},
                        'item' => $item,
                        'children' => $children,
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getChildrenIds()
    {
        $result = [];
        $items = $this->getDescendantsAndSelf();
        $items->each(function (self $item) use (&$result) {
            array_push($result, $item->id);
        });

        return $result;
    }

    /**
     * Возвращает описание доступных полей для вывода в колонки...
     *
     * ... метод используется для первоначального конфигурирования таблицы,
     * дальнейшие типы, порядок колонок и т.д. будут храниться в обхекте BaseTable
     *
     * @return array
     */
    public function getTableCols(): array
    {
        return [
            [
                'name' => trans('app.Название'),
                'key' => self::NAME,
                'domain' => true,
                'link' => 'category_item',
                'extra' => true,
            ],
            [
                'name' => trans('app.Дата'),
                'key' => 'created_at',
                'width' => 150,
                'link' => null,
                'class' => 'text-center',
            ],
            [
                'name' => '#',
                'key' => 'id',
                'link' => null,
                'width' => 80,
                'class' => 'text-center',
            ],
        ];
    }

    /**
     * Набор доступных фильтров при выводе в таблице раздела администрирования
     * @return array
     */
    public function getAdminFilters(): array
    {
        $default = [
            [
                [
                    BaseFilter::NAME => ContentCategory::NAME,
                    BaseFilter::PLACEHOLDER => trans('app.Название'),
                    BaseFilter::TYPE => BaseFilter::TYPE_TEXT,
                    BaseFilter::DISPLAY => false,
                    BaseFilter::OPERATOR => (new BaseOperator('LIKE', 'LIKE'))->getOperator(),

                ],
            ],
        ];

        return $default;
    }

    /**
     * Возвращает имя события вызываемого при обработке данных при передаче на клиент в разделе администрирования
     * @return string
     */
    public function getEventAdminPrepareName(): string
    {
        return ContentCategoryAdminPrepare::class;
    }

    /**
     * Возвращает ключ доступа к ACL
     * @param string $type
     * @return string
     */
    public function getAccessKey($type = 'guest'): string
    {
        return strtolower(\FastDog\Content\Content::class) . '::' . DomainManager::getSiteId() . '::' . $type;
    }
}
