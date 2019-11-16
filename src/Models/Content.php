<?php

namespace FastDog\Content\Models;

use FastDog\Content\Events\ContentAdminPrepare;
use DOMDocument;
use DOMElement;
use FastDog\Core\Media\Interfaces\MediaInterface;
use FastDog\Core\Media\Traits\MediaTraits;
use FastDog\Core\Models\BaseModel;
use FastDog\Core\Models\Cache;
use FastDog\Core\Models\DomainManager;
use FastDog\Core\Models\Notifications;
use FastDog\Core\Properties\BaseProperties;
use FastDog\Core\Properties\Interfases\PropertiesInterface;
use FastDog\Core\Properties\Traits\PropertiesTrait;
use FastDog\Core\Table\Filters\BaseFilter;
use FastDog\Core\Table\Filters\Operator\BaseOperator;
use FastDog\Core\Table\Interfaces\TableModelInterface;
use FastDog\Menu\Models\Menu;
use FastDog\User\User;
use Illuminate\Support\Collection;

/**
 * Материалы
 *
 * Модель материалов, модель реализует следующие интерфейсы:
 *
 * SearchResult - поддержка поиска, данная модель может быть вызвана в модуле Search в результатх поиска
 * TableModelInterface - поддержка таблицы в разделе администрирования, пара методов предоставляющих данные для
 * построения интерфейса таблиц
 * PropertiesInterface - поддержка дополнительных, произвольных, парамтеров модели
 *
 * @package FastDog\Content\Models
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class Content extends BaseModel implements /*SearchResult,*/
    TableModelInterface, PropertiesInterface, MediaInterface
{
    use PropertiesTrait, MediaTraits;

    /**
     * Название
     * @const string
     */
    const TITLE = 'name';

    /**
     * Псевдоним
     * @const string
     */
    const ALIAS = 'alias';

    /**
     * В изббранном
     * @const string
     */
    const FAVORITES = 'favorites';

    /**
     * Идентификатор категории
     * @const string
     */
    const CATEGORY_ID = 'category_id';

    /**
     * Вступительный текст
     * @const string
     */
    const INTROTEXT = 'introtext';

    /**
     * Полный текст
     * @const string
     */
    const FULLTEXT = 'fulltext';

    /**
     * Состояние
     * @const string
     */
    const PUBLISHED = 'state';

    /**
     * Дата публикации
     * @const string
     */
    const PUBLISHED_AT = 'published_at';

    /**
     * Дополнительные параметры
     * @const string
     */
    const DATA = 'data';

    /**
     * Кол-во просмотров
     * @const string
     */
    const VIEW_COUNTER = 'view_counter';

    /**
     * Кол-во комментариев
     * @const string
     */
    const COUNT_COMMENT = 'count_comment';

    /**
     * Название таблицы
     * @var string $table
     */
    protected $table = 'content';

    /**
     * Массив полей автозаполнения
     *
     * @var array $fillable
     */
    protected $fillable = [self::TITLE, self::ALIAS, self::FAVORITES, self::CATEGORY_ID, self::STATE,
        self::INTROTEXT, self::FULLTEXT, self::PUBLISHED, self::DATA, self::VIEW_COUNTER, self::SITE_ID, 'created_at'];
    /**
     * Массив полей преобразования даты-времени
     *
     * @var array $dates
     */
    public $dates = ['deleted_at', 'published_at'];

    /**
     * Возвращает детальную информацию о объекте
     * @return array
     */
    public function getData(): array
    {
        if (!empty($this->{self::DATA}) && is_string($this->{self::DATA})) {
            $this->{self::DATA} = json_decode($this->{self::DATA});
        }

        $data = [
            'id' => $this->id,
            self::TITLE => $this->{self::TITLE},
            self::ALIAS => $this->{self::ALIAS},
            self::FAVORITES => $this->{self::FAVORITES},
            self::CATEGORY_ID => $this->{self::CATEGORY_ID},
            self::INTROTEXT => $this->{self::INTROTEXT},
            self::FULLTEXT => $this->{self::FULLTEXT},
            self::PUBLISHED => $this->{self::PUBLISHED},
            self::SITE_ID => $this->{self::SITE_ID},
            self::DATA => $this->{self::DATA},
            self::STATE => $this->{self::STATE},
            self::COUNT_COMMENT => $this->{self::COUNT_COMMENT},
        ];

        return $data;
    }

    /**
     * Отношение к категории
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function category()
    {
        return $this->hasOne(ContentCategory::class, 'id', self::CATEGORY_ID);
    }

    /**
     * Отношение к канонической ссылке
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function canonical()
    {
        return $this->hasMany(ContentCanonical::class, 'item_id', 'id');
    }

    /**
     * @var array $incrementStyles
     */
    protected static $incrementStyles = [
        'dash' => [
            '#-(\d+)$#',
            '-%d',
        ],
        'default' => [
            ['#\((\d+)\)$#', '#\(\d+\)$#'],
            [' (%d)', '(%d)'],
        ],
    ];

    /**
     * Обработка строки
     *
     * @param $string
     * @param string $style
     * @param int $n
     * @return mixed|string
     */
    public static function incrementText($string, $style = 'default', $n = 0)
    {
        $styleSpec = isset(static::$incrementStyles[$style]) ?
            static::$incrementStyles[$style] : static::$incrementStyles['default'];

        // Regular expression search and replace patterns.
        if (is_array($styleSpec[0])) {
            $rxSearch = $styleSpec[0][0];
            $rxReplace = $styleSpec[0][1];
        } else {
            $rxSearch = $rxReplace = $styleSpec[0];
        }

        // New and old (existing) sprintf formats.
        if (is_array($styleSpec[1])) {
            $newFormat = $styleSpec[1][0];
            $oldFormat = $styleSpec[1][1];
        } else {
            $newFormat = $oldFormat = $styleSpec[1];
        }

        // Check if we are incrementing an existing pattern, or appending a new one.
        if (preg_match($rxSearch, $string, $matches)) {
            $n = empty($n) ? ($matches[1] + 1) : $n;
            $string = preg_replace($rxReplace, sprintf($oldFormat, $n), $string);
        } else {
            $n = empty($n) ? 2 : $n;
            $string .= sprintf($newFormat, $n);
        }

        return $string;
    }

    /**
     * Получение пустого объекта
     *
     * @param $fireEvent
     * @return array
     */
    public function getBlankData($fireEvent = true)
    {
        $data = [
            'id' => 0,
            self::TITLE => '',
            self::ALIAS => '',
            self::FAVORITES => 0,
            self::DATA => [
                'page_id' => 0,
                'page_title' => 0,
            ],
            'el_finder' => [
                'parent_type',
            ],
        ];

        return $data;
    }

    /**
     * Статистика модели
     *
     * Метод возвращает статистическую информацию по модели
     *
     * <pre>
     * [
     *  'total' => 'Общее количество записей',
     *  'published' => 'Опубликовано',
     *  'published_percent' => 'Опубликовано в процентном соотношение от общего количества',
     *  'not_published' => 'Не опубликовано',
     *  'not_published_percent' => 'Не опубликовано в процентном соотношение от общего количества',
     *  'in_trash' => 'В корзине',
     *  'in_trash_percent' => 'В корзине',
     *  'deleted' => 'Удалено',
     *  'deleted_percent' =>  'Удалено в процентном соотношение от общего количества',
     *  'cache_tags' => 'Поддержка тегов при кеширование'
     * ];
     * </pre>
     * @param $fire_event
     * @return array
     */
    public static function getStatistic($fire_event = true)
    {
        return parent::getStatistic();
    }

    /**
     * Подготовка метаданных
     *
     * @param array $data
     * @return array
     */
    public static function prepareMetadata(array $data)
    {
        $result = [];

        /**
         * @var $config ContentConfig
         */
        $config = $data['config'];
        $media = $data['media'];
        $data = $data['item'];
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
                $result['keywords'] = self::top_words(strip_tags($data['introtext']) . strip_tags($data['fulltext']));
                $result['keywords'] = implode(', ', $result['keywords']);
            }

            if (isset($data['data']->meta_robots)) {
                $result['robots'] = implode(',', $data['data']->meta_robots);
            } else {
                $result['robots'] = 'index,follow';
            }


            $result['og'] = [
                'title' => $result['title'],
                'type' => 'article',
                'url' => \Request::url(),
            ];

            if ($media) {
                $image = $media->where('type', 'file')->first();
                if ($image) {
                    $result['og']['image'] = $image['value'];
                    $result['image_src'] = $image['value'];
                }
            }
            if (!$config->can('generate_og')) {
                unset($result['og']);
            }

            if (isset($data['data']->canonical)) {
                $result['canonical'] = $data['data']->canonical;
            }
        }

        return $result;
    }

    /**
     * Выделение часто встречающихся слов
     *
     * @param $str
     * @param int $limit
     * @param string $ignore
     * @return array
     */
    public static function top_words($str, $limit = 25, $ignore = "")
    {
        $ignore_arr = explode(" ", $ignore);
        $arr = [];
        $str = trim(str_replace(["\n", "\t"], " ", $str));

        $arraw = explode(" ", $str);

        foreach ($arraw as $v) {
            $v = trim(str_replace(['.', ',', ':', '?', '=', '-', '(', ')', '"', "'", '/', '%', '@', '!',
            ], ' ', $v));
            if (mb_strlen($v) > 3) {
                if (!isset($arr[$v])) {
                    $arr[$v] = 1;
                }
                $arr[$v]++;
            }
        }
        arsort($arr);

        return array_keys(array_slice($arr, 0, $limit));
    }

    /**
     * Ссылка при просмотре категории
     *
     * @param Menu $item
     * @param bool $url
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function getCategoryUrl(Menu $item, $url = true)
    {
        $template = $this->id . '-' . $this->{self::ALIAS} . '.html';

        return ($url) ? url($item->getRoute() . '/' . $template) : $item->getRoute() . '/' . $template;
    }

    /**
     * Параметры публичной части
     *
     * @return ContentConfig
     */
    public function getPublicConfig()
    {
        return app()->make(Cache::class)->get(__METHOD__ . '::' . DomainManager::getSiteId() . '::module-content-public', function() {
            return ContentConfig::where(ContentConfig::ALIAS, ContentConfig::CONFIG_PUBLIC)->first();
        }, ['config']);
    }

    /**
     * Обновление модели
     *
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        if (!$this->exists) {
            return false;
        }
        if (isset($attributes[self::STATE])) {
            switch ($attributes[self::STATE]) {
                case self::STATE_IN_TRASH:
                    break;
            }
        }

        return $this->fill($attributes)->save($options);
    }

    /**
     * Удаление модели
     *
     * @return bool|null
     * @throws \Exception
     */
    public function delete()
    {
        /**
         * @var  $user User
         */
        $user = \Auth::getUser();

        /**
         * Проверка пунктов меню при удаление материалов...
         */
        $menuItems = Menu::getAllItemByFilterData([
            'page_id' => $this->id,
            'type' => 'content_item',
        ]);

        /**
         * ...если найдены пункты меню привязанные к материалу,
         * отключаем их и создаем уведомление
         */
        if (count($menuItems) >= 1) {
            /**
             * @var $menuItem Menu
             */
            foreach ($menuItems as $menuItem) {
                $data = $menuItem->getData();
                if (isset($data['data']->page_id)) {
                    $data['data']->page_id = 0;
                }
                if (isset($data['data']->page_title)) {
                    $data['data']->page_title = '';
                }

                Menu::where('id', $menuItem->id)->update([
                    Menu::STATE => Menu::STATE_NOT_PUBLISHED,
                    Menu::DATA => json_encode($data['data']),
                ]);
                Notifications::add([
                    Notifications::TYPE => Notifications::TYPE_MENU_DISABLED,
                    'message' => 'При удаление материала #' . $this->id . ' меню ' .
                        '<a href="/{ADMIN}/#!/menu/' . $menuItem->id . '" target="_blank">#' .
                        $menuItem->id . '</a> переведено в состояние: "Не опубликовано".',
                ]);
            }
        }

        return parent::delete();
    }


    /**
     * Возвращает ссылку на результат поиска
     *
     * @return string
     */
    public function getUrl()
    {
        if (is_string($this->{self::DATA})) {
            $this->{self::DATA} = json_decode($this->{self::DATA});
        }

        $data = $this->{self::DATA};

        $link = (isset($this->{self::DATA}->canonical->value)) ? $this->{self::DATA}->canonical->value : false;
        if ($link === false) {
            $canonical = ContentCanonical::where(ContentCanonical::ITEM_ID, $this->id)->first();

            if ($canonical) {
                $data->canonical = (object)[
                    'id' => $canonical->id,
                    'value' => $canonical->{ContentCanonical::LINK},
                ];

                (new Content())->where('id', $this->id)->update([
                    self::DATA => json_encode($data),
                ]);
                if (isset($data->canonical->value)) {
                    $link = $data->canonical->value;
                }
            }
        }

        return $link;
    }

    /**
     * Название
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->{self::NAME};
    }

    /**
     * Вступительный текст
     *
     * @return mixed
     */
    public function getText()
    {
        return $this->{self::INTROTEXT};
    }

    /**
     * Кол-во комментариев
     *
     * @return int
     */
    public function getCommentCount()
    {
        return $this->{self::COUNT_COMMENT};
    }


    /**
     * @param $data
     * @param array $textFields
     * @param BaseModel $item
     * @return mixed
     * todo: пенерести этот метод в событие обработки материалов при выводе на сайт
     */
    public static function prepareText(&$data, array $textFields, $item)
    {
        libxml_use_internal_errors(true);

        $defaultWidth = $item->getParameterByFilterData(['name' => 'DEFAULT_WIDTH'], 250);
        $defaultHeight = $item->getParameterByFilterData(['name' => 'DEFAULT_HEIGHT'], 250);
        $allowImageResize = ($item->getParameterByFilterData(['name' => 'ALLOW_AUTO_RESIZE_IMAGE'], 'N') == 'Y');
        $allowWrap = ($item->getParameterByFilterData(['name' => 'WRAP_IMAGES_FANCYBOX'], 'N') == 'Y');

        foreach ($textFields as $name) {
            $domd = new DOMDocument();

            $domd->loadHTML('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $data[$name]);

            $images = $domd->getElementsByTagName("img");
            if ($images->length) {
                /**
                 * @var $image DOMElement
                 */
                foreach ($images as $image) {
                    $src = $image->getAttribute('src');

                    /**
                     * Масштабирование изображений
                     */
                    if ($allowImageResize) {
                        $src = str_replace(url('/'), '', $src);
                        $file = $_SERVER['DOCUMENT_ROOT'] . '/' . $src;

                        if (file_exists($file)) {
                            $style = $image->getAttribute('style');

                            if ($style !== '') {
                                $results = [];
                                preg_match_all("/([\w-]+)\s*:\s*([^;]+)\s*;?/", $style, $matches, PREG_SET_ORDER);
                                foreach ($matches as $match) {
                                    $results[$match[1]] = str_replace('px', '', $match[2]);
                                }
                                if (isset($results['width']) && $results['width'] > 0) {
                                    $defaultWidth = $results['width'];
                                }
                                if (isset($results['height']) && $results['height'] > 0) {
                                    $defaultHeight = $results['height'];
                                }
                            }
                            $width = $image->getAttribute('width');
                            if ($width) {
                                $defaultWidth = $width;
                            }
                            $height = $image->getAttribute('width');
                            if ($height) {
                                $defaultHeight = $height;
                            }
                            $result = Gallery::getPhotoThumb($src, $defaultWidth, $defaultHeight);

                            if ($result['exist']) {
                                $image->setAttribute('src', url($result['file']));
                                $image->removeAttribute('width');
                                $image->removeAttribute('height');
                                $image->removeAttribute('style');
                            }
                        }
                    }
                    /**
                     * Обертка для галерей
                     */
                    if ($allowWrap && $image->parentNode->nodeName !== 'a') {
                        $a = $domd->createElement('a');
                        $a->setAttribute('href', url($src));
                        $a->setAttribute('class', 'fancybox');
                        $image->parentNode->replaceChild($a, $image);
                        if ($image->getAttribute('data-fancybox') === 'group') {
                            $a->setAttribute('data-fancybox', 'group');
                        }
                        $a->appendChild($image);
                    }
                }
                $data[$name] = $domd->saveHTML($domd->documentElement);
                $data[$name] = str_replace(['<html>', '<body>', '</html>', '</body>'], '', $data[$name]);
            }
        }
        libxml_use_internal_errors(false);

        return $data;
    }

    /**
     * Метод возвращает поля доступные для вывода в таблице администрирования
     *
     * @return array
     */
    public function getTableCols(): array
    {
        return [
            [
                'name' => trans('content::interface.name'),
                'key' => BaseModel::NAME,
                'domain' => true,
                'link' => 'content_item',
            ],
            [
                'name' => trans('content::interface.category'),
                'key' => Content::CATEGORY_ID,
                'width' => 150,
                'link' => null,
                'class' => 'text-center',
                'related' => 'category:' . ContentCategory::NAME,
            ],
            [
                'name' => trans('content::interface.created_at'),
                'key' => Content::PUBLISHED_AT,
                'width' => 150,
                'link' => null,
                'class' => 'text-center',
            ],
            [
                'name' => trans('content::interface.view_count'),
                'key' => Content::VIEW_COUNTER,
                'width' => 80,
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
                    BaseFilter::NAME => Content::NAME,
                    BaseFilter::PLACEHOLDER => trans('content::interface.name'),
                    BaseFilter::TYPE => BaseFilter::TYPE_TEXT,
                    BaseFilter::DISPLAY => false,
                    BaseFilter::OPERATOR => (new BaseOperator('LIKE', 'LIKE'))->getOperator(),

                ],
                BaseFilter::getLogicAnd(),
                [
                    BaseFilter::TYPE => BaseFilter::TYPE_TEXT,
                    BaseFilter::NAME => Content::ALIAS,
                    BaseFilter::DISPLAY => true,
                    BaseFilter::PLACEHOLDER => trans('content::interface.alias'),
                    BaseFilter::OPERATOR => (new BaseOperator())->getOperator(),
                    BaseFilter::VALIDATE => 'required|min:5',
                ],
                BaseFilter::getLogicAnd(),
                [
                    BaseFilter::TYPE => BaseFilter::TYPE_DATETIME,
                    BaseFilter::NAME => Content::PUBLISHED_AT,
                    BaseFilter::DISPLAY => true,
                    BaseFilter::PLACEHOLDER => trans('content::interface.published_at'),
                    BaseFilter::OPERATOR => (new BaseOperator('BETWEEN', 'BETWEEN'))->getOperator(
                        [['id' => 'BETWEEN', 'name' => 'BETWEEN']]
                    ),
                ],
            ],
            [
                BaseFilter::getLogicAnd(),
                [
                    BaseFilter::TYPE => BaseFilter::TYPE_SELECT,
                    BaseFilter::NAME => Content::STATE,
                    BaseFilter::DISPLAY => true,
                    BaseFilter::PLACEHOLDER => trans('content::interface.state'),
                    BaseFilter::DATA => Content::getStatusList(),
                    BaseFilter::OPERATOR => (new BaseOperator())->getOperator(),
                ],
                BaseFilter::getLogicAnd(),
                [
                    BaseFilter::TYPE => BaseFilter::TYPE_SELECT,
                    BaseFilter::NAME => Content::CATEGORY_ID,
                    BaseFilter::DISPLAY => true,
                    BaseFilter::PLACEHOLDER => trans('content::interface.category'),
                    BaseFilter::DATA => ContentCategory::getList(),
                    BaseFilter::OPERATOR => (new BaseOperator('IN', 'IN'))->getOperator(
                        [['id' => 'IN', 'name' => 'IN'], ['id' => 'NOT IN', 'name' => 'NOT IN']]
                    ),
                ],
            ],
            [
                BaseFilter::getLogicAnd(),
                [
                    BaseFilter::TYPE => BaseFilter::TYPE_DATETIME,
                    BaseFilter::NAME => Content::CREATED_AT,
                    BaseFilter::DISPLAY => true,
                    BaseFilter::PLACEHOLDER => trans('content::interface.created_at'),
                    BaseFilter::OPERATOR => (new BaseOperator('BETWEEN', 'BETWEEN'))->getOperator(
                        [['id' => 'BETWEEN', 'name' => 'BETWEEN']]
                    ),
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
        return ContentAdminPrepare::class;
    }


    /**
     * @return Collection
     */
    public function getDefaultProperties(): Collection
    {
        $result = [
            [
                BaseProperties::NAME => trans('content::properties.auto_preview'),
                BaseProperties::ALIAS => 'ALLOW_AUTO_RESIZE_IMAGE',
                BaseProperties::VALUE => 'Y',
                BaseProperties::SORT => 100,
                BaseProperties::TYPE => BaseProperties::TYPE_SELECT,
                BaseProperties::DATA => json_encode([
                    'description' => trans('content::properties.auto_preview_description'),
                    'values' => [
                        ['id' => null, 'alias' => 'Y', 'name' => trans('content::properties.yes'),],
                        ['id' => null, 'alias' => 'N', 'name' => trans('content::properties.no'),],
                    ],
                ]),
            ],
            [
                BaseProperties::NAME => trans('content::properties.default_width'),
                BaseProperties::ALIAS => 'DEFAULT_WIDTH',
                BaseProperties::VALUE => '250',
                BaseProperties::SORT => 200,
                BaseProperties::TYPE => BaseProperties::TYPE_NUMBER,
                BaseProperties::DATA => json_encode([
                    'description' => trans('content::properties.default_width_description'),
                ]),
            ],
            [
                BaseProperties::NAME => trans('content::properties.default_height'),
                BaseProperties::ALIAS => 'DEFAULT_HEIGHT',
                BaseProperties::VALUE => '150',
                BaseProperties::SORT => 300,
                BaseProperties::TYPE => BaseProperties::TYPE_NUMBER,
                BaseProperties::DATA => json_encode([
                    'description' => trans('content::properties.default_height_description'),
                ]),
            ],
            [
                BaseProperties::NAME => trans('content::properties.fancybox'),
                BaseProperties::ALIAS => 'WRAP_IMAGES_FANCYBOX',
                BaseProperties::VALUE => 'Y',
                BaseProperties::SORT => 400,
                BaseProperties::TYPE => BaseProperties::TYPE_SELECT,
                BaseProperties::DATA => json_encode([
                    'description' => trans('content::properties.fancybox_description'),
                    'values' => [
                        ['id' => null, 'alias' => 'Y', 'name' => trans('content::properties.yes'),],
                        ['id' => null, 'alias' => 'N', 'name' => trans('content::properties.no'),],
                    ],
                ]),
            ],
//            [
//                BaseProperties::NAME => 'Формы',
//                BaseProperties::ALIAS => 'FORMS',
//                BaseProperties::VALUE => '',
//                BaseProperties::SORT => 500,
//                BaseProperties::TYPE => BaseProperties::TYPE_SELECT,
//                BaseProperties::DATA => json_encode([
//                    'description' => 'выбор доступных форм, в тексте размещается маркер вида {{FORM_[form_name]=[template]}} где form_name - имя формы,
//                     template - шаблон находящийся в theme#SITE_ID.::modules.forms.sample.[template].blade.php',
//                    'values' => (array) FormManager::getList(),
//                    'multiple' => false,
//                ]),
//            ],
        ];

        return collect($result);
    }


}