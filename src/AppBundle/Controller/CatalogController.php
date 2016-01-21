<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;

use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Product\Facet;
use Commercetools\Core\Model\Product\FacetResultCollection;
use Commercetools\Core\Model\Product\ProductProjectionCollection;
use Commercetools\Core\Response\PagedSearchResponse;
use Commercetools\Sunrise\AppBundle\Model\Config;
use Commercetools\Sunrise\AppBundle\Repository\CategoryRepository;
use Commercetools\Sunrise\AppBundle\Repository\ProductRepository;
use Commercetools\Sunrise\AppBundle\Repository\ProductTypeRepository;
use Commercetools\Sunrise\AppBundle\Model\View\ViewLink;
use Commercetools\Sunrise\AppBundle\Model\View\ProductModel;
use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Sunrise\AppBundle\Model\ViewDataCollection;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CatalogController extends SunriseController
{
    const SLUG_SKU_SEPARATOR = '--';

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var FacetResultCollection
     */
    protected $facets;

    /**
     * @var ProductModel
     */
    protected $productModel;

    public function __construct(
        Client $client,
        $locale,
        UrlGenerator $generator,
        CacheAdapterInterface $cache,
        TranslatorInterface $translator,
        EngineInterface $templateEngine,
        AuthorizationCheckerInterface $authChecker,
        $config,
        Session $session,
        CategoryRepository $categoryRepository,
        ProductTypeRepository $productTypeRepository,
        ProductRepository $productRepository
    )
    {
        if (is_array($config)) {
            $config = new Config($config);
        }
        parent::__construct(
            $client,
            $locale,
            $generator,
            $cache,
            $translator,
            $templateEngine,
            $authChecker,
            $config,
            $session,
            $categoryRepository,
            $productTypeRepository
        );
        $this->productRepository = $productRepository;
        $this->productModel = new ProductModel($cache, $config, $productTypeRepository, $generator);
    }


    public function home(Request $request)
    {
        $viewData = $this->getViewData('Sunrise - Home');
        $viewData->content->banners = new ViewData();
        $viewData->content->banners->bannerOne = new ViewData();
        $viewData->content->banners->bannerOne->first = new ViewLink(
            $this->generator->generate('category', ['category' => 'accessories'])
        );
        $viewData->content->banners->bannerOne->second = new ViewLink(
            $this->generator->generate('category', ['category' => 'women'])
        );
        $viewData->content->banners->bannerTwo = new ViewData();
        $viewData->content->banners->bannerTwo->first = new ViewLink(
            $this->generator->generate('category', ['category' => 'men'])
        );
        $viewData->content->banners->bannerThree = new ViewData();
        $viewData->content->banners->bannerThree->first = new ViewLink(
            $this->generator->generate('category', ['category' => 'shoes'])
        );
        $viewData->content->banners->bannerThree->third = new ViewLink(
            $this->generator->generate('category', ['category' => 'accessories-women-sunglasses'])
        );
        $viewData->content->banners->bannerThree->fourth = new ViewLink(
            $this->generator->generate('category', ['category' => 'accessories-women-sunglasses'])
        );

        return $this->render('home.hbs', $viewData->toArray());
    }

    public function search(Request $request)
    {
        $uri = new Uri($request->getRequestUri());
        $products = $this->getProducts($request);

        $viewData = $this->getViewData('Sunrise - ProductRepository Overview Page');

        $viewData->content->text = "Women";
        $viewData->content->banner = new ViewData();
        $viewData->content->banner->text = "Women";
        $viewData->content->banner->description = "Lorem dolor deserunt debitis voluptatibus odio id animi voluptates alias eum adipisci laudantium iusto totam quibusdam modi quo! Consectetur.";
        $viewData->content->banner->imageMobile = "/assets/img/banner_mobile.jpg";
        $viewData->content->banner->imageDesktop = "/assets/img/banner_desktop.jpg";
        $viewData->jumboTron = new ViewData();
        $viewData->content->products = new ViewData();
        $viewData->content->products->list = new ViewDataCollection();
        $viewData->content->displaySelector = $this->getDisplayContent($this->getItemsPerPage($request));
//        $viewData->content->facets = $this->getFiltersData($uri);
        $viewData->content->sortSelector = $this->getSortData($this->getSort($request, 'sunrise.products.sort'));
        foreach ($products as $key => $product) {
            $viewData->content->products->list->add(
                $this->productModel->getProductOverviewData($product, $product->getMasterVariant(), $this->locale)
            );
        }
        $viewData->content->pagination = $this->pagination;
        /**
         * @var callable $renderer
         */
        return $this->render('pop.hbs', $viewData->toArray());
    }

    public function detail(Request $request)
    {
        $slug = $request->get('slug');
        $sku = $request->get('sku');

        $viewData = $this->getViewData('Sunrise - ProductRepository Detail Page');

        $product = $this->productRepository->getProductBySlug($slug, $this->locale);
        $productData = $this->productModel->getProductDetailData($product, $sku, $this->locale);
        $viewData->content->product = $productData;

        return $this->render('pdp.hbs', $viewData->toArray());
    }

    protected function getFiltersData(UriInterface $uri)
    {
        $filter = new ViewData();
        $filter->url = $uri->getPath();
        $filter->list = new ViewDataCollection();
        $filter->list->add($this->getCategoriesFacet());

        return $filter;
    }

    protected function addToCollection($categoryTree, ViewDataCollection $collection, $ancestors, $categoryId, ViewData $entry)
    {
        if (!empty($ancestors)) {
            $firstAncestor = array_shift($ancestors);
            $firstAncestorEntry = $categoryTree[$firstAncestor];

            $ancestor = $collection->getAt($firstAncestor);
            if (is_null($ancestor)) {
                $firstAncestorEntry->children = new ViewDataCollection();
                $collection->add($firstAncestorEntry, $firstAncestor);
            }
            if (!isset($ancestor->children)) {
                $firstAncestorEntry->children = new ViewDataCollection();
            }
            $this->addToCollection($categoryTree, $firstAncestorEntry->children, $ancestors, $categoryId, $entry);
        } else {
            $collection->add($entry, $categoryId);
        }
    }

    protected function getCategoriesFacet()
    {
        $maxDepth = 1;
        $categoryFacet = $this->facets->getByName('categories');
        $categoryData = $this->categoryRepository->getCategories();

        $cacheKey = 'category-facet-tree-' . $this->locale;
        if (!$this->cache->has($cacheKey)) {
            $categoryTree = [];
            foreach ($categoryData as $category) {
                $categoryEntry = new ViewData();
                $categoryEntry->value = $category->getId();
                $categoryEntry->label = (string)$category->getName();
                $ancestors = $category->getAncestors();
                $categoryEntry->ancestors = [];
                if (!is_null($ancestors)) {
                    foreach ($ancestors as $ancestor) {
                        $categoryEntry->ancestors[] = $ancestor->getId();
                    }
                }
                $categoryTree[$category->getId()] = $categoryEntry;
            }
            $this->cache->store($cacheKey, serialize($categoryTree));
        } else {
            $categoryTree = unserialize($this->cache->fetch($cacheKey));
        }

        $limitedOptions = new ViewDataCollection();

        foreach ($categoryFacet->getTerms() as $term) {
            $categoryId = $term->getTerm();
            $categoryEntry = $categoryTree[$categoryId];
            if (count($categoryEntry->ancestors) > $maxDepth) {
                continue;
            }
            $categoryEntry->count = $term->getCount();
            $this->addToCollection($categoryTree, $limitedOptions, $categoryEntry->ancestors, $categoryId, $categoryEntry);
        }

        $categories = new ViewData();
        $categories->hierarchicalSelectFacet = true;
        $categories->facet = new ViewData();
        $categories->facet->available = true;
        $categories->facet->label = $this->trans('search.filters.productType');
        $categories->facet->key = 'product-type';
        $categories->facet->limitedOptions = $limitedOptions;

        return $categories;
    }

    protected function getSortData($currentSort)
    {
        $sortData = new ViewData();
        $sortData->list = new ViewDataCollection();

        foreach ($this->config['sunrise.products.sort'] as $sort) {
            $entry = new ViewData();
            $entry->value = $sort['formValue'];
            $entry->label = $this->trans('search.sort.' . $sort['formValue']);
            if ($currentSort == $sort) {
                $entry->selected = true;
            }
            $sortData->list->add($entry);
        }
        return $sortData;
    }

    protected function getDisplayContent($currentCount)
    {
        $display = new ViewData();
        $display->list = new ViewDataCollection();

        foreach ($this->config->get('sunrise.itemsPerPage') as $count) {
            $entry = new ViewData();
            $entry->value = $count;
            $entry->label = $count;
            if ($currentCount == $count) {
                $entry->selected = true;
            }
            $display->list->add($entry);
        }

        return $display;
    }

    protected function getProducts(Request $request)
    {
        $country = \Locale::getRegion($this->locale);
        $currency = $this->config->get('currencies.'. $country);
        $itemsPerPage = $this->getItemsPerPage($request);
        $currentPage = $this->getCurrentPage($request);
        $sort = $this->getSort($request, 'sunrise.products.sort')['searchParam'];
        $category = $request->get('category');

        $facetDefinitions = [
            Facet::of()->setName('categories.id')->setAlias('categories')
        ];
        /**
         * @var ProductProjectionCollection $products
         * @var PagedSearchResponse $response
         */
        list($products, $facets, $offset, $total) = $this->productRepository->getProducts(
            $this->categoryRepository->getCategories(),
            $this->locale,
            $itemsPerPage,
            $currentPage,
            $sort,
            $currency,
            $country,
            $category,
            $facetDefinitions
        );

        $this->applyPagination(new Uri($request->getRequestUri()), $offset, $total, $itemsPerPage);
        $this->pagination->currentPage = $products->count(); // @todo this is actually the count of products
        $this->pagination->totalPages = $total; // @todo this is actually the total count of products
        $this->facets = $facets;

        return $products;
    }
}
