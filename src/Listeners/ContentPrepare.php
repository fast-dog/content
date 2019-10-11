<?php

namespace FastDog\Content\Listeners;

use App\Modules\Config\Entity\DomainManager;
use FastDog\Content\Entity\Content;
use FastDog\Content\Entity\ContentComments;
use FastDog\Content\Entity\ContentConfig;
use FastDog\Content\Entity\ContentTag;
use FastDog\Content\Events\ContentPrepare as EventContentPrepare;
use App\Modules\Form\Entity\FormConfig;
use App\Modules\Form\Entity\FormManager;
use App\Modules\Media\Entity\Gallery;
use DOMDocument;
use DOMElement;
use Illuminate\Http\Request;

/**
 * При просмотре материала на сайте
 *
 * Масштабирование изображений, обработка галерей, обработка маркеров форм, маркеров карты, загрузка комментариев.
 *
 * @package FastDog\Content\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentPrepare
{
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
     * @param EventContentPrepare $event
     * @return void
     * @throws \Throwable
     */
    public function handle(EventContentPrepare $event)
    {
        #$moduleManager = \App::make(ModuleManager::class);
        /**
         * @var $item Content
         */
        $item = $event->getItem();
        $data = $event->getData();

        /**
         * @var $contentConfig ContentConfig
         */
        $contentConfig = ContentConfig::where(ContentConfig::ALIAS, ContentConfig::CONFIG_PUBLIC)->first();

        $data['allow_comment'] = $contentConfig->can('allow_comment');

        if (is_string($data[Content::DATA])) {
            $data[Content::DATA] = json_decode($data[Content::DATA]);
        }
        $data['published_at'] = $item->published_at;
        $allowImageResize = ($item->getParameterByFilterData(['name' => 'ALLOW_AUTO_RESIZE_IMAGE'], 'N') == 'Y');
        $allowWrap = ($item->getParameterByFilterData(['name' => 'WRAP_IMAGES_FANCYBOX'], 'N') == 'Y');

        $textFields = [Content::INTROTEXT, Content::FULLTEXT];

        if ($allowImageResize || $allowWrap) {
            libxml_use_internal_errors(true);

            $defaultWidth = $item->getParameterByFilterData(['name' => 'DEFAULT_WIDTH'], 250);
            $defaultHeight = $item->getParameterByFilterData(['name' => 'DEFAULT_HEIGHT'], 250);

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
                                    $image->removeAttribute('style');
                                    $image->removeAttribute('width');
                                    $image->removeAttribute('height');
                                }
                            }
                        }

                        /**
                         * Обертка для галерей
                         */
                        if ($allowWrap) {
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
        }
        $formsMatch = [];
        /**
         * Обработка форм размещенных в теле страницы
         */
        $forms = $item->getParameterByFilterData(['name' => 'FORMS'], []);
        if (count($forms)) {
            if (strpos($data[Content::FULLTEXT], '{{FORM_') !== false) {
                // ищем маркер формы в тектсе
                if (preg_match_all('/{{FORM_([^}].*)=(.*)}}/iu', $data[Content::FULLTEXT], $formsMatch)) {
                    // форма найдена и определена в параметрах Материала
                    if (isset($formsMatch[1])) {
                        /**
                         * @var $formConfig FormConfig
                         */
                        $formConfig = FormConfig::where([
                            FormConfig::ALIAS => FormConfig::CONFIG_PUBLIC,
                        ])->first();


                        $formsMatch[1] = (array)$formsMatch[1];
                        foreach ($formsMatch[1] as $findId => $formName) {
                            $template = '';
                            if (isset($formsMatch[2][$findId])) {
                                $template = $formsMatch[2][$findId];
                            }
                            $form = FormManager::getByName($formName);
                            if ($form) {
                                $question = [];
                                $form_questions = $form->getQuestions();
                                foreach ($form_questions as $idx => $form_question) {
                                    if (!$form_question['alias']) {
                                        $form_question['alias'] = $form_question['name'];
                                    }
                                    $question[$form_question['alias']] = $form_question;
                                }
                                $view = 'theme#' . DomainManager::getSiteId() . '::modules.forms.sample.' . $template;
                                if (view()->exists($view)) {
                                    $viewData = [
                                        'form' => $form,
                                        'form_questions' => $question,
                                        'use_recaptcha' => $formConfig->can('use_recaptcha'),
                                    ];
                                    view()->share($viewData);
                                    $data[Content::FULLTEXT] = str_replace('{{FORM_' . $formName . '=' . $template . '}}',
                                        view($view, $viewData)->render(),
                                        $data[Content::FULLTEXT]);
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Маркеры на карте
         */
        $data['map_markers'] = null;
        $markers = $item->getParameterByFilterData(['name' => 'map'], []);
        if (count($markers)) {
            $data['map_markers'] = $markers;
        }
        /**
         * Поисковые теги
         */
        $_tags = [];
        $tags = ContentTag::where(ContentTag::ITEM_ID, $item['id'])//->limit(3)->orderBy(\DB::raw('RAND()'))
        ->get();
        foreach ($tags as $tag) {
            array_push($_tags, '<a href="' . url('/search?tag=' . $tag->{ContentTag::TEXT}, [], config('app.use_ssl')) . '">' .
                $tag->{ContentTag::TEXT} . '</a>');
        }
        $data['tags'] = $_tags;
        /**
         * Загрузка комментариев
         */
        if ($data['allow_comment']) {
            /**
             * @var  $commentRoot ContentComments
             */
            $commentRoot = ContentComments::where([
                ContentComments::ITEM_ID => $data['id'],
                'lft' => 1,
            ])->first();
            if ($commentRoot) {
                if (($commentRoot->{'rgt'} - $commentRoot->{'lft'} - 1) / 2) {
                    $data['comments'] = $commentRoot->getDescendants();
                }
            }
            $data['count_comment'] = $item->getCommentCount();
        }


        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $event->setData($data);
    }
}
