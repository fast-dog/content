<?php

namespace FastDog\Content\Listeners\Category;

use App\Core\FormFieldTypes;
use App\Modules\Catalog\Entity\CatalogItems;
use App\Modules\Config\Entity\DomainManager;
use FastDog\Content\Entity\Content;
use FastDog\Content\Entity\ContentCategory;
use FastDog\Content\Events\Category\ContentCategoryAdminPrepare as EventContentCategoryAdminPrepare;
use Illuminate\Http\Request;

/**
 * При редактирование
 *
 * @package FastDog\Content\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCategoryAdminSetEditorForm
{
    /**
     * Парамтеры извлекаемые из json объекта data
     *
     * @var array $dataParameters
     */
    public static $dataParameters = [

    ];
    /**
     * @var Request $request
     */
    protected $request;

    /**
     * ContentAdminPrepare constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param EventContentCategoryAdminPrepare $event
     * @return void
     */
    public function handle(EventContentCategoryAdminPrepare $event)
    {

        $item = $event->getItem();
        $data = $event->getData();

        if (count(self::$dataParameters)) {
            foreach (self::$dataParameters as $name => $value) {
                if (in_array($name, self::$dataParameters)) {
                    $data['item'][$name] = $value;
                }
            }
            foreach (self::$dataParameters as $name => $value) {
                if (!isset($data['item'][$name])) {
                    $data['item'][$name] = '';
                }
            }
        }


        $result = $event->getResult();

        $result['form'] = [
            'create_url' => '',
            'update_url' => '',
            'tabs' => (array)[
                (object)[
                    'id' => 'catalog-item-general-tab',
                    'name' => trans('app.Основная информация'),
                    'active' => true,
                    'fields' => (array)[
                        [
                            'id' => ContentCategory::NAME,
                            'type' => FormFieldTypes::TYPE_TEXT,
                            'name' => ContentCategory::NAME,
                            'label' => trans('app.Название'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'required' => true,
                            'validate' => 'required|min:3',
                        ],
                        [
                            'id' => ContentCategory::ALIAS,
                            'type' => FormFieldTypes::TYPE_TEXT_ALIAS,
                            'name' => ContentCategory::ALIAS,
                            'label' => trans('app.Псевдоним'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                        ],
                        [
                            'type' => FormFieldTypes::TYPE_TABS,
                            'tabs' => [
                                [
                                    'id' => 'catalog-item-introtext-tab',
                                    'name' => trans('app.Вступительный текст'),
                                    'active' => true,
                                    'fields' => [
                                        [
                                            'id' => 'introtext',
                                            'type' => FormFieldTypes::TYPE_HTML_EDITOR,
                                            'name' => 'introtext',
                                            'label' => '',
                                        ],
                                    ],
                                ],
                                [
                                    'id' => 'catalog-item-fulltext-tab',
                                    'name' => trans('app.Полный текст'),
                                    'active' => false,
                                    'fields' => [
                                        [
                                            'id' => 'fulltext',
                                            'type' => FormFieldTypes::TYPE_HTML_EDITOR,
                                            'name' => 'fulltext',
                                            'label' => '',
                                        ],
                                    ],
                                ],
                            ],
                        ],

                    ],
                    'side' => [
                        [
                            'id' => ContentCategory::SITE_ID,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => ContentCategory::SITE_ID,
                            'label' => trans('app.Доступ'),
                            'items' => DomainManager::getAccessDomainList(),
                            'css_class' => 'col-sm-12',
                            'active' => DomainManager::checkIsDefault(),
                        ],
                        [
                            'id' => ContentCategory::STATE,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => ContentCategory::STATE,
                            'label' => trans('app.Состояние'),
                            'css_class' => 'col-sm-12',
                            'items' => Content::getStatusList(),
                        ],
                        [
                            'id' => ContentCategory::PARENT_ID,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => ContentCategory::PARENT_ID,
                            'label' => trans('app.Категория'),
                            'css_class' => 'col-sm-12',
                            'items' => ContentCategory::getCategoryList(true),
                        ],
                        [
                            'id' => ContentCategory::CREATED_AT,
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => ContentCategory::CREATED_AT,
                            'label' => trans('app.Дата создания'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                        ],
                        [
                            'id' => ContentCategory::UPDATED_AT,
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => ContentCategory::UPDATED_AT,
                            'label' => trans('app.Дата обновления'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'readonly' => true,
                        ],
                        [
                            'id' => 'published_at',
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => 'published_at',
                            'label' => trans('app.Дата публикации'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'readonly' => true,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-media-tab',
                    'name' => trans('app.Медиа материалы'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_MEDIA,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-seo-tab',
                    'name' => trans('app.Поисковая оптимизация'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_SEO,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-extend-tab',
                    'name' => trans('app.Дополнительно'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_SAMPLE_PROPERTIES,
                            'model_id' => $item->getModelId(),
                            'model' => ContentCategory::class,
                        ],
                    ],
                ],
            ],
        ];

        if (config('app.debug')) {
            $result['_events'][] = __METHOD__;
        }
        $event->setData($data);
        $event->setResult($result);
    }
}