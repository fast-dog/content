<?php

namespace FastDog\Content\Listeners\Category;

use FastDog\Content\Events\Category\ContentCategoryAdminPrepare as EventContentCategoryAdminPrepare;
use FastDog\Content\Models\Content;
use FastDog\Content\Models\ContentCategory;
use FastDog\Core\Models\DomainManager;
use FastDog\Core\Models\FormFieldTypes;
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
                    'name' => trans('content::forms.general.title'),
                    'active' => true,
                    'fields' => (array)[
                        [
                            'id' => ContentCategory::NAME,
                            'type' => FormFieldTypes::TYPE_TEXT,
                            'name' => ContentCategory::NAME,
                            'label' => trans('content::forms.general.fields.name'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'required' => true,
                            'validate' => 'required|min:3',
                        ],
                        [
                            'id' => ContentCategory::ALIAS,
                            'type' => FormFieldTypes::TYPE_TEXT_ALIAS,
                            'name' => ContentCategory::ALIAS,
                            'label' => trans('content::forms.general.fields.alias'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                        ],
                        [
                            'type' => FormFieldTypes::TYPE_TABS,
                            'tabs' => [
                                [
                                    'id' => 'catalog-item-introtext-tab',
                                    'name' => trans('content::forms.general.fields.introtext'),
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
                                    'name' => trans('content::forms.general.fields.fulltext'),
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
                            'label' => trans('content::forms.general.fields.access'),
                            'items' => DomainManager::getAccessDomainList(),
                            'css_class' => 'col-sm-12',
                            'active' => DomainManager::checkIsDefault(),
                        ],
                        [
                            'id' => ContentCategory::STATE,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => ContentCategory::STATE,
                            'label' => trans('content::forms.general.fields.state'),
                            'css_class' => 'col-sm-12',
                            'items' => Content::getStatusList(),
                        ],
                        [
                            'id' => ContentCategory::PARENT_ID,
                            'type' => FormFieldTypes::TYPE_SELECT,
                            'name' => ContentCategory::PARENT_ID,
                            'label' => trans('content::forms.general.fields.category'),
                            'css_class' => 'col-sm-12',
                            'items' => ContentCategory::getCategoryList(true),
                        ],
                        [
                            'id' => ContentCategory::CREATED_AT,
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => ContentCategory::CREATED_AT,
                            'label' => trans('content::forms.general.fields.created_at'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                        ],
                        [
                            'id' => ContentCategory::UPDATED_AT,
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => ContentCategory::UPDATED_AT,
                            'label' => trans('content::forms.general.fields.updated_at'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'readonly' => true,
                        ],
                        [
                            'id' => 'published_at',
                            'type' => FormFieldTypes::TYPE_DATE,
                            'name' => 'published_at',
                            'label' => trans('content::forms.general.fields.published_at'),
                            'css_class' => 'col-sm-12',
                            'form_group' => true,
                            'readonly' => true,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-media-tab',
                    'name' => trans('content::forms.media.title'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_MEDIA,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-seo-tab',
                    'name' => trans('content::forms.seo.title'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_SEO,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'catalog-item-extend-tab',
                    'name' => trans('content::forms.extend.title'),
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