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
        $viewData->meta = $this->getMetaData();
        $viewData->footer = $this->getFooterData();
        $viewData->seo = $this->getSeoData();
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

    protected function getMetaData()
    {
        $meta = new ViewData();
        $meta->assetsPath = $this->config['assetsPath'];

        return $meta;
    }

    protected function getFooterData()
    {
        $footer = new ViewData();
        $footer->paySecure = $this->trans('footer.paySecure');
        $footer->followUs = $this->trans('footer.followUs');
        $footer->newsletter = new Url($this->trans('footer.newsletter.text'), '');
        $footer->newsletter->textAlt = $this->trans('footer.newsletter.textAlt');
        $footer->newsletter->placeHolder = $this->trans('footer.newsletter.placeHolder');
        $footer->newsletter->inputId = 'pop-newsletter-input';
        $footer->newsletter->buttonId = 'pop-newsletter-button';

        $footer->customerCare = new ViewData();
        $footer->customerCare->text = $this->trans('footer.customerCare.text');
        $ccList = new Collection();
        $ccList->add(new Url($this->trans('footer.customerCare.contactUs'),''));
        $ccList->add(new Url($this->trans('footer.customerCare.help'),''));
        $ccList->add(new Url($this->trans('footer.customerCare.shipping'),''));
        $ccList->add(new Url($this->trans('footer.customerCare.returns'),''));
        $ccList->add(new Url($this->trans('footer.customerCare.sizeGuide'),''));
        $footer->customerCare->list = $ccList;

        $footer->aboutUs = new ViewData();
        $footer->aboutUs->text = $this->trans('footer.aboutUs.text');
        $aboutUsList = new Collection();
        $aboutUsList->add(new Url($this->trans('footer.aboutUs.ourStory'),''));
        $aboutUsList->add(new Url($this->trans('footer.aboutUs.careers'),''));
        $footer->aboutUs->list = $aboutUsList;

        $footer->shortcuts = new ViewData();
        $footer->shortcuts->text = $this->trans('footer.shortcuts.text');
        $shortcutsList = new Collection();
        $shortcutsList->add(new Url($this->trans('footer.shortcuts.myAccount'),''));
        $shortcutsList->add(new Url($this->trans('footer.shortcuts.stores'),''));
        $shortcutsList->add(new Url($this->trans('footer.shortcuts.giftCards'),''));
        $shortcutsList->add(new Url($this->trans('footer.shortcuts.payment'),''));
        $footer->shortcuts->list = $shortcutsList;

        $footer->legalInfo = new ViewData();
        $footer->legalInfo->text = $this->trans('footer.legalInfo.text');
        $legalInfoList = new Collection();
        $legalInfoList->add(new Url($this->trans('footer.legalInfo.imprint'),''));
        $legalInfoList->add(new Url($this->trans('footer.legalInfo.privacy'),''));
        $legalInfoList->add(new Url($this->trans('footer.legalInfo.terms'),''));
        $footer->legalInfo->list = $legalInfoList;

        return $footer;
    }

    protected function getSeoData()
    {
        $seo = new ViewData();
        $seo->text = $this->trans('seo.text');
        $seo->description = $this->trans('seo.description');

        return $seo;
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
