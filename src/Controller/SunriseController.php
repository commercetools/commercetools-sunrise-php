<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Controller;


use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Category\CategoryCollection;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Sunrise\Model\Collection;
use Commercetools\Sunrise\Model\View\Header;
use Commercetools\Sunrise\Model\View\Tree;
use Commercetools\Sunrise\Model\View\Url;
use Commercetools\Sunrise\Model\ViewData;
use Commercetools\Sunrise\Template\TemplateService;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\TranslatorInterface;

class SunriseController
{
    const CACHE_TTL = 3600;
    /**
     * @var TemplateService
     */
    protected $view;
    /**
     * @var UrlGenerator
     */
    protected $generator;
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var CacheAdapterInterface
     */
    protected $cache;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $locale;

    public function __construct(
        Client $client,
        $locale,
        TemplateService $view,
        UrlGenerator $generator,
        CacheAdapterInterface $cache,
        TranslatorInterface $translator,
        $config
    )
    {
        $this->locale = $locale;
        $this->view = $view;
        $this->generator = $generator;
        $this->translator = $translator;
        $this->config = $config;
        $this->cache = $cache;
        $this->client = $client;
    }

    protected function getViewData($title)
    {
        $viewData = new ViewData();
        $viewData->header = $this->getHeaderViewData($title);
        $viewData->meta = $this->getMeta();

        return $viewData;
    }

    protected function getHeaderViewData($title)
    {
        $header = new Header($title);
        $header->stores = new Url($this->trans('header.stores'), '');
        $header->help = new Url($this->trans('header.help'), '');
        $header->callUs = new Url(
            $this->trans('header.callUs', ['%phone%' => $this->config['header']['callUs']]),
            ''
        );
        $header->location = [
            'language' => [
                [
                    'text' => 'German',
                    'value' => '',
                    'selected' => true
                ]
            ],
            'country' => [
                [
                    'text' => 'Germany',
                    'value' => '',
                    'selected' => true
                ]
            ]
        ];
        $header->user = new ViewData();
        $header->user->isLoggedIn = false;
        $header->user->signIn = new Url('Login', '');
        $header->miniCart = new Url('MiniCart', '');
        $header->navMenu = $this->getNavMenu();

        return $header;
    }

    protected function getNavMenu()
    {
        $navMenu = new ViewData();

        $cacheKey = 'category-menu';
        if ($this->cache->has($cacheKey)) {
            $categoryMenu = unserialize($this->cache->fetch($cacheKey));
        } else {
            $categories = $this->getCategories();
            $categoryMenu = new Collection();
            foreach ($categories->getRoots() as $root) {
                /**
                 * @var Category $root
                 */
                $menuEntry = new Tree(
                    (string)$root->getName(), $this->getLinkFor('category', ['category' => $root->getSlug()])
                );

                foreach ($categories->getByParent($root->getId()) as $children) {
                    /**
                     * @var Category $children
                     */
                    $childrenEntry = new Tree(
                        (string)$children->getName(),
                        $this->getLinkFor('category', ['category' => $children->getSlug()])
                    );

                    foreach ($categories->getByParent($children->getId()) as $subChild) {
                        /**
                         * @var Category $subChild
                         */
                        $childrenSubEntry = new Url(
                            (string)$subChild->getName(),
                            $this->getLinkFor('category', ['category' => $subChild->getSlug()])
                        );
                        $childrenEntry->addNode($childrenSubEntry);
                    }
                    $menuEntry->addNode($childrenEntry);
                }
                $categoryMenu->add($menuEntry);
            }
            $categoryMenu = $categoryMenu->toArray();
            $this->cache->store($cacheKey, serialize($categoryMenu), static::CACHE_TTL);
        }
        $navMenu->categories = $categoryMenu;

        return $navMenu;
    }

    protected function getMeta()
    {
        $meta = new ViewData();
        $meta->assetsPath = $this->config['assetsPath'];

        return $meta;
    }

    protected function trans($id, $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * @return CategoryCollection
     */
    protected function getCategories()
    {
        $cacheKey = 'categories';

        $this->cache->remove($cacheKey);
        $categoryData = [];
        if ($this->cache->has($cacheKey)) {
            $cachedCategories = $this->cache->fetch($cacheKey);
            if (!empty($cachedCategories)) {
                $categoryData = $cachedCategories;
            }
            $categories = CategoryCollection::fromArray($categoryData, $this->client->getConfig()->getContext());
        } else {
            $helper = new QueryHelper();
            $categories = $helper->getAll($this->client, CategoryQueryRequest::of());
            $this->cache->store($cacheKey, $categories->toArray(), static::CACHE_TTL);
        }

        return $categories;
    }

    protected function getLinkFor($site, $params)
    {
        return $this->generator->generate($site, $params);
    }
}
