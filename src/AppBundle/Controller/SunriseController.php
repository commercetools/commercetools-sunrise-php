<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;

use Cache\Adapter\Common\CacheItem;
use Commercetools\Core\Model\Category\Category;
use Commercetools\Sunrise\AppBundle\Model\Config;
use Commercetools\Sunrise\AppBundle\Model\View\Entry;
use Commercetools\Sunrise\AppBundle\Model\View\Footer;
use Commercetools\Sunrise\AppBundle\Model\View\LinkList;
use Commercetools\Sunrise\AppBundle\Model\View\Location;
use Commercetools\Sunrise\AppBundle\Model\View\Meta;
use Commercetools\Sunrise\AppBundle\Model\View\MiniCart;
use Commercetools\Sunrise\AppBundle\Model\View\Model;
use Commercetools\Sunrise\AppBundle\Model\View\NavMenu;
use Commercetools\Sunrise\AppBundle\Model\View\Newsletter;
use Commercetools\Sunrise\AppBundle\Model\View\User;
use Commercetools\Sunrise\AppBundle\Model\View\ViewLink;
use Commercetools\Sunrise\AppBundle\Model\ViewDataCollection;
use Commercetools\Sunrise\AppBundle\Model\View\Header;
use Commercetools\Sunrise\AppBundle\Model\View\Tree;
use Commercetools\Sunrise\AppBundle\Model\View\Url;
use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Symfony\CtpBundle\Model\Repository\CartRepository;
use GuzzleHttp\Psr7\Uri;
use Psr\Cache\CacheItemInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;

class SunriseController extends Controller
{
    const ITEMS_PER_PAGE = 12;
    const PAGE_SELECTOR_RANGE = 2;
    const FIRST_PAGE = 1;
    const ITEM_COUNT_ELEMENT = 'items';
    const SORT_ELEMENT = 'sort';
    const SORT_DEFAULT = 'new';

    const CSRF_TOKEN_FORM = 'csrfToken';

    const CACHE_TTL = 3600;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $locale;

    protected $pagination;

    /**
     * @var string
     */
    private $interpolationPrefix;

    /**
     * @var string
     */
    private $interpolationSuffix;

    /**
     * @var string
     */
    private $defaultNamespace;

    public function __construct(
        $config
    )
    {
        if (is_array($config)) {
            $config = new Config($config);
        }
        $this->config = $config;
        $this->defaultNamespace = $this->config['i18n.namespace.defaultNs'];
        $this->interpolationPrefix = $this->config['i18n.interpolationPrefix'];
        $this->interpolationSuffix = $this->config['i18n.interpolationSuffix'];
    }

    protected function getViewData($title, Request $request = null)
    {
        $viewData = new Model();
        $viewData->header = $this->getHeaderViewData($title, $request);
        $viewData->meta = $this->getMetaData();
        $viewData->footer = $this->getFooterData();
        $viewData->seo = $this->getSeoData();
        $viewData->content = new ViewData();
        return $viewData;
    }

    protected function getHeaderViewData($title, Request $request = null)
    {
        $locale = $this->get('commercetools.locale.converter')->convert($request->getLocale());
        $session = $this->get('session');
        $header = new Header($title);
        $languages = new ViewDataCollection();

        $routeParams = $request->get('_route_params');
        $queryParams = \GuzzleHttp\Psr7\parse_query($request->getQueryString());
        foreach ($this->config['languages'] as $language) {
            $routeParams['_locale'] = $language;
            $languageUri = $this->generateUrl($request->get('_route'), $routeParams);

            $uri = new Uri($languageUri);
            $languageEntry = new Entry(
                $this->trans('header.languages.' . $language),
                (string)$uri->withQuery(\GuzzleHttp\Psr7\build_query($queryParams))
            );
            if ($language == \Locale::getPrimaryLanguage($locale)) {
                $languageEntry->selected = true;
            }

            $languages->add($languageEntry);
        }
        $header->location = new Location($languages);

//        $countries = new ViewDataCollection();
//        foreach ($this->config['countries'] as $country) {
//            $countryEntry = new ViewData();
//            $countryEntry->label = $this->trans('header.countries.' . $country);
//            $countryEntry->value = $country;
//            $countries->add($countryEntry);
//        }
//
//        $header->location->country = $countries;
        $header->user = new User(new Url('Login', ''), false);
        $header->miniCart = new MiniCart($session->get(CartRepository::CART_ITEM_COUNT, 0));
        $header->navMenu = $this->getNavMenu($locale);

        return $header;
    }

    protected function getNavMenu($locale)
    {
        $navMenu = new NavMenu();

        $cacheKey = 'category-menu-' . $locale;
        $cache = $this->get('commercetools.cache');
        if ($cache->hasItem($cacheKey)) {
            /**
             * @var CacheItemInterface $item
             */
            $item = $cache->getItem($cacheKey);
            $categoryMenu = $item->get();
        } else {
            $categories = $this->get('app.repository.category')->getCategories($locale);
            $categoryMenu = new ViewDataCollection();
            $roots = $this->sortCategoriesByOrderHint($categories->getRoots());

            foreach ($roots as $root) {
                /**
                 * @var Category $root
                 */
                $menuEntry = new Tree(
                    (string)$root->getName(), $this->generateUrl('category', ['category' => $root->getSlug()])
                );
                if ($root->getSlug() == $this->config['sunrise.sale.slug']) {
                    $menuEntry->sale = true;
                }

                $subCategories = $this->sortCategoriesByOrderHint($categories->getByParent($root->getId()));
                foreach ($subCategories as $children) {
                    /**
                     * @var Category $children
                     */
                    $childrenEntry = new Tree(
                        (string)$children->getName(),
                        $this->generateUrl('category', ['category' => $children->getSlug()])
                    );

                    $subChildCategories = $this->sortCategoriesByOrderHint($categories->getByParent($children->getId()));
                    foreach ($subChildCategories as $subChild) {
                        /**
                         * @var Category $subChild
                         */
                        $childrenSubEntry = new Url(
                            (string)$subChild->getName(),
                            $this->generateUrl('category', ['category' => $subChild->getSlug()])
                        );
                        $childrenEntry->addNode($childrenSubEntry);
                    }
                    $menuEntry->addNode($childrenEntry);
                }
                $categoryMenu->add($menuEntry);
            }
            $categoryMenu = $categoryMenu->toArray();
            $item = $cache->getItem($cacheKey)->set($categoryMenu)->expiresAfter(static::CACHE_TTL);
            $cache->save($item);
        }
        $navMenu->categories = $categoryMenu;

        return $navMenu;
    }

    protected function getMetaData()
    {
        $meta = new Meta();
        $meta->assetsPath = $this->config['sunrise.assetsPath'];
        $meta->_links = new ViewDataCollection();
        $meta->_links->add(new ViewLink($this->generateUrl('home')), 'home');
        $meta->_links->add(new ViewLink($this->generateUrl('category', ['category' => 'new'])), 'newProducts');
        $meta->_links->add(new ViewLink($this->generateUrl('cartAdd')), 'addToCart');
        $meta->_links->add(new ViewLink($this->generateUrl('miniCart')), 'miniCart');
        $meta->_links->add(new ViewLink($this->generateUrl('cart')), 'cart');
        $meta->_links->add(new ViewLink($this->generateUrl('login_route')), 'signIn');
        $meta->_links->add(new ViewLink($this->generateUrl('login_check')), 'logInSubmit');
        $meta->csrfToken = $this->getCsrfToken(static::CSRF_TOKEN_FORM);
        $bagItems = new ViewDataCollection();
        for ($i = 1; $i < 10; $i++) {
            $bagItems->add($i);
        }
        $meta->bagQuantityOptions = $bagItems;


        return $meta;
    }

    protected function validateCsrfToken($formName, $token)
    {
        $session = $this->get('session');
        $storedToken = $this->get('session')->get($formName);
        $session->remove($formName);
        if ($storedToken == $token) {
            return true;
        }

        return false;
    }

    protected function getCsrfToken($formName)
    {
        $session = $this->get('session');
        $token=hash("sha512",mt_rand(0,mt_getrandmax()));
        $session->set($formName, $token);

        return $token;
    }

    protected function getFooterData()
    {
        $footer = new Footer();
        $footer->paySecure = $this->trans('footer.paySecure');
        $footer->followUs = $this->trans('footer.followUs');
        $footer->newsletter = new Newsletter($this->trans('footer.newsletter.text'), '');
        $footer->newsletter->textAlt = $this->trans('footer.newsletter.textAlt');
        $footer->newsletter->placeHolder = $this->trans('footer.newsletter.placeHolder');
        $footer->newsletter->inputId = 'pop-newsletter-input';
        $footer->newsletter->buttonId = 'pop-newsletter-button';

        $footer->customerCare = new LinkList();
        $footer->customerCare->text = $this->trans('footer.customerCare.text');
        $ccList = new ViewDataCollection();
        $ccList->add(new Url($this->trans('footer.customerCare.contactUs'),''));
        $ccList->add(new Url($this->trans('footer.customerCare.help'),''));
        $ccList->add(new Url($this->trans('footer.customerCare.shipping'),''));
        $ccList->add(new Url($this->trans('footer.customerCare.returns'),''));
        $ccList->add(new Url($this->trans('footer.customerCare.sizeGuide'),''));
        $footer->customerCare->list = $ccList;

        $footer->aboutUs = new LinkList();
        $footer->aboutUs->text = $this->trans('footer.aboutUs.text');
        $aboutUsList = new ViewDataCollection();
        $aboutUsList->add(new Url($this->trans('footer.aboutUs.ourStory'),''));
        $aboutUsList->add(new Url($this->trans('footer.aboutUs.careers'),''));
        $footer->aboutUs->list = $aboutUsList;

        $footer->shortcuts = new LinkList();
        $footer->shortcuts->text = $this->trans('footer.shortcuts.text');
        $shortcutsList = new ViewDataCollection();
        $shortcutsList->add(new Url($this->trans('footer.shortcuts.myAccount'),''));
        $shortcutsList->add(new Url($this->trans('footer.shortcuts.stores'),''));
        $shortcutsList->add(new Url($this->trans('footer.shortcuts.giftCards'),''));
        $shortcutsList->add(new Url($this->trans('footer.shortcuts.payment'),''));
        $footer->shortcuts->list = $shortcutsList;

        $footer->legalInfo = new LinkList();
        $footer->legalInfo->text = $this->trans('footer.legalInfo.text');
        $legalInfoList = new ViewDataCollection();
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
        if (is_null($domain)) {
            $domain = $this->defaultNamespace;
        }
        return $this->get('translator')->trans($id, $this->mapInterpolation($parameters), $domain, $locale);
    }

    protected function mapInterpolation($parameters)
    {
        $args = [];
        foreach ($parameters as $key => $value) {
            $key = $this->interpolationPrefix . $key . $this->interpolationSuffix;
            $args[$key] = $value;
        }
        return $args;
    }

    protected function getLinkFor($site, $params)
    {
        return $this->generateUrl($site, $params);
    }

    protected function applyPagination(UriInterface $uri, $offset, $total, $itemsPerPage)
    {
        $firstPage = static::FIRST_PAGE;
        $pageRange = static::PAGE_SELECTOR_RANGE;
        $currentPage = floor($offset / max(1, $itemsPerPage)) + 1;
        $totalPages = ceil($total / max(1, $itemsPerPage));

        $displayedPages = $pageRange * 2 + 3;
        $pageThresholdLeft = $displayedPages - $pageRange;
        $thresholdPageLeft = $displayedPages - 1;
        $pageThresholdRight = $totalPages - $pageRange - 2;
        $thresholdPageRight = $totalPages - $displayedPages + 2;
        $pagination = new ViewData();

        if ($totalPages <= $displayedPages) {
            $pagination->pages = $this->getPages($uri, $firstPage, $totalPages, $currentPage);
        } elseif ($currentPage < $pageThresholdLeft) {
            $pagination->pages = $this->getPages($uri, $firstPage, $thresholdPageLeft, $currentPage);
            $pagination->lastPage = $this->getPageUrl($uri, $totalPages);
        } elseif ($currentPage > $pageThresholdRight) {
            $pagination->pages = $this->getPages($uri, $thresholdPageRight, $totalPages, $currentPage);
            $pagination->firstPage = $this->getPageUrl($uri, $firstPage);
        } else {
            $pagination->pages = $this->getPages(
                $uri,
                $currentPage - $pageRange,
                $currentPage + $pageRange,
                $currentPage
            );
            $pagination->firstPage = $this->getPageUrl($uri, $firstPage);
            $pagination->lastPage = $this->getPageUrl($uri, $totalPages);
        }

        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $pagination->previousUrl = $this->getPageUrl($uri, $prevPage)->url;
        }
        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            $pagination->nextUrl = $this->getPageUrl($uri, $nextPage)->url;
        }

        $this->pagination = $pagination;
    }

    protected function getItemsPerPage(Request $request)
    {
        $itemsPerPage = $request->get(static::ITEM_COUNT_ELEMENT, static::ITEMS_PER_PAGE);
        if (!in_array($itemsPerPage, $this->config->get('sunrise.itemsPerPage'))) {
            return static::ITEMS_PER_PAGE;
        }
        return $itemsPerPage;
    }

    protected function getSort(Request $request, $configEntry)
    {
        $sort = $request->get(static::SORT_ELEMENT, static::SORT_DEFAULT);
        if (!array_key_exists($sort, $this->config->get($configEntry))) {
            return static::SORT_DEFAULT;
        }
        return $this->config->get($configEntry)[$sort];
    }

    protected function getCurrentPage(Request $request)
    {
        $currentPage = $request->get('page', 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }

        return $currentPage;
    }

    protected function getPageUrl(UriInterface $uri, $number, $query = 'page')
    {
        $url = new Url($number, Uri::withQueryValue($uri, $query,$number));
        return $url;
    }

    protected function getPages(UriInterface $uri, $start, $stop, $currentPage)
    {
        $pages = new ViewDataCollection();
        for ($i = $start; $i <= $stop; $i++) {
            $url = $this->getPageUrl($uri, $i);
            if ($currentPage == $i) {
                $url->selected = true;
            }
            $pages->add($url);
        }
        return $pages;
    }

    protected function sortCategoriesByOrderHint($categories)
    {
        usort($categories, function (Category $a, Category $b) {
            return $a->getOrderHint() > $b->getOrderHint();
        });

        return $categories;
    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilder
     */
    protected function createNamedFormBuilder($name, $data = null, array $options = array())
    {
        return $this->get('form.factory')->createNamedBuilder($name, FormType::class, $data, $options);
    }
}
