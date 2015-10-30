<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Controller;

use Commercetools\Commons\Helper\PriceFinder;
use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Common\Attribute;
use Commercetools\Core\Model\Product\Facet;
use Commercetools\Core\Model\Product\FacetResultCollection;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\ProductProjectionCollection;
use Commercetools\Core\Model\Product\ProductVariant;
use Commercetools\Core\Response\PagedSearchResponse;
use Commercetools\Sunrise\Model\Config;
use Commercetools\Sunrise\Model\Repository\CategoryRepository;
use Commercetools\Sunrise\Model\Repository\ProductRepository;
use Commercetools\Sunrise\Model\ViewData;
use Commercetools\Sunrise\Model\ViewDataCollection;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGenerator;
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

    public function __construct(
        Client $client,
        $locale,
        UrlGenerator $generator,
        CacheAdapterInterface $cache,
        TranslatorInterface $translator,
        Config $config,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository
    )
    {
        parent::__construct($client, $locale, $generator, $cache, $translator, $config, $categoryRepository);
        $this->productRepository = $productRepository;
    }


    public function home(Request $request)
    {
        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/templates/home.json'),
            true
        );
        foreach ($viewData['content']['wishlistWidged']['list'] as &$wish) {
            $wish['image'] = '/' . $wish['image'];
        }
        $viewData = array_merge(
            $viewData,
            $this->getViewData('Sunrise - Home')->toArray()
        );

        return ['home', $viewData];
    }

    public function search(Request $request)
    {
        $uri = new Uri($request->getRequestUri());
        $products = $this->getProducts($request);

        $viewData = $this->getViewData('Sunrise - ProductRepository Overview Page');

        $viewData->content = new ViewData();
        $viewData->content->text = "Women";
        $viewData->content->banner = new ViewData();
        $viewData->content->banner->text = "Women";
        $viewData->content->banner->description = "Lorem dolor deserunt debitis voluptatibus odio id animi voluptates alias eum adipisci laudantium iusto totam quibusdam modi quo! Consectetur.";
        $viewData->content->banner->imageMobile = "/assets/img/banner_mobile.jpg";
        $viewData->content->banner->imageDesktop = "/assets/img/banner_desktop.jpg";
        $viewData->jumboTron = new ViewData();
        $viewData->content->products = new ViewData();
        $viewData->content->products->list = new ViewDataCollection();
        $viewData->content->static = $this->getStaticContent();
        $viewData->content->display = $this->getDisplayContent($this->getItemsPerPage($request));
        $viewData->content->filters = $this->getFiltersData($uri);
        $viewData->content->sort = $this->getSortData($this->getSort($request, 'sunrise.products.sort'));
        foreach ($products as $key => $product) {
            $viewData->content->products->list->add($this->getProductOverviewData($product));
        }
        $viewData->content->pagination = $this->pagination;
        /**
         * @var callable $renderer
         */
        return ['product-overview', $viewData->toArray()];
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
        $categoryData = $this->getCategories();

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
        $categories->facet = new ViewData();
        $categories->facet->available = true;
        $categories->hierarchicalSelectFacet = true;
        $categories->facet->key = 'product-type';
        $categories->facet->label = $this->trans('search.filters.productType');
        $categories->facet->available = true;
        $categories->facet->limitedOptions = $limitedOptions;

        return $categories;
    }

    protected function getSortData($currentSort)
    {
        $sortData = new ViewDataCollection();

        foreach ($this->config['sunrise.products.sort'] as $sort) {
            $entry = new ViewData();
            $entry->value = $sort['formValue'];
            $entry->label = $this->trans('search.sort.' . $sort['formValue']);
            if ($currentSort == $sort) {
                $entry->selected = true;
            }
            $sortData->add($entry);
        }

        return $sortData;
    }

    protected function getStaticContent()
    {
        $static = new ViewData();
        $static->productCountSeparatorText = $this->trans('filter.productCountSeparator');
        $static->displaySelectorText = $this->trans('filter.itemsPerPage');
        $static->saleText = $this->trans('product.saleText');
        $static->productDetailsText = $this->trans('product.detailsText');
        $static->deliveryAndReturnsText = $this->trans('product.deliveryReturnsText');
        $static->standardDeliveryText = $this->trans('product.standardDeliveryText');
        $static->expressDeliveryText = $this->trans('product.expressDeliveryText');
        $static->freeReturnsText = $this->trans('product.freeReturnsText');
        $static->moreDeliveryInfoText = $this->trans('product.moreDeliveryInfoText');
        $static->sizeDefaultItem = new ViewData();
        $static->sizeDefaultItem->text = $this->trans('product.sizeDefaultItem');
        $static->sizeDefaultItem->selected = empty($sku);
        $static->sizeDefaultItem->id = "pdp-size-select-first-option";

        $bagItems = new ViewDataCollection();
        for ($i = 1; $i < 10; $i++) {
            $bagItem = new ViewData();
            $bagItem->text = $i;
            $bagItem->value = $i;
            $bagItem->id = 'pdp-bag-items-' . $i;
            $bagItems->add($bagItem);
        }
        $static->bagItems = $bagItems;

        return $static;
    }

    protected function getDisplayContent($currentCount)
    {
        $display = new ViewDataCollection();

        foreach ($this->config->get('sunrise.itemsPerPage') as $count) {
            $entry = new ViewData();
            $entry->value = $count;
            $entry->text = $count;
            if ($currentCount == $count) {
                $entry->selected = true;
            }
            $display->add($entry);
        }

        return $display;
    }

    public function detail(Request $request)
    {
        $slug = $request->get('slug');
        $sku = $request->get('sku');
        $product = $this->productRepository->getProductBySlug($slug);

        $viewData = $this->getViewData('Sunrise - ProductRepository Detail Page');

        $viewData->content->static = $this->getStaticContent();
        $viewData->content->product = $this->getProductDetailData($product, $sku);

        return ['product-detail', $viewData->toArray()];
    }

    protected function getProductOverviewData(ProductProjection $product)
    {
        $productData = new ViewData();
        $productVariant = $product->getMasterVariant();

        $price = PriceFinder::findPriceFor($productVariant->getPrices(), 'EUR');
        if (!is_null($price->getDiscounted())) {
            $productData->price = $price->getDiscounted()->getValue();
            $productData->priceOld = $price->getValue();
        } else {
            $productData->price = $price->getValue();
        }
        $productUrl = $this->generator->generate(
            'pdp',
            [
                'slug' => (string)$product->getSlug(),
                'sku' => $product->getMasterVariant()->getSku()
            ]
        );
        $productData->id = $product->getId();
        $productData->sale = isset($productData->priceOld);
        $productData->text = (string)$product->getName();
        $productData->text = (string)$product->getName();
        $productData->text = (string)$product->getName();
        $productData->text = (string)$product->getName();
        $productData->description = (string)$product->getDescription();
        $productData->url = $productUrl;
        $productData->imageUrl = (string)$productVariant->getImages()->getAt(0)->getUrl();
        $productData->images = new ViewDataCollection();
        foreach ($productVariant->getImages() as $image) {
            $imageData = new ViewData();
            $imageData->thumbImage = $image->getUrl();
            $imageData->bigImage = $image->getUrl();
            $productData->images->add($imageData);
        }

        return $productData;
    }

    protected function getProductDetailData(ProductProjection $product, $sku)
    {
        $emptySku = false;
        if (empty($sku)) {
            $emptySku = true;
            $sku = $product->getMasterVariant()->getSku();
        }

        $productVariant = $product->getVariantBySku($sku);
        if (empty($productVariant)) {
            throw new NotFoundHttpException("resource not found");
        }

        $productUrl = $this->getLinkFor(
            'pdp',
            ['slug' => (string)$product->getSlug(), 'sku' => $productVariant->getSku()]
        );
        $productData = new ViewData();
        $productData->id = $product->getId();
        $productData->text = (string)$product->getName();
        $productData->description = (string)$product->getDescription();
        $productData->sku = $productVariant->getSku();
        $productData->url = $productUrl;
        $productData->imageUrl = (string)$productVariant->getImages()->getAt(0)->getUrl();
        $price = PriceFinder::findPriceFor($productVariant->getPrices(), 'EUR');
        if (!is_null($price->getDiscounted())) {
            $productData->price = $price->getDiscounted()->getValue();
            $productData->priceOld = $price->getValue();
        } else {
            $productData->price = $price->getValue();
        }

        $productData->images = new ViewDataCollection();
        foreach ($productVariant->getImages() as $image) {
            $imageData = new ViewData();
            $imageData->thumbImage = $image->getUrl();
            $imageData->bigImage = $image->getUrl();
            $productData->images->add($imageData);
        }


        $selectorConfig = $this->config['sunrise.products.variantsSelector'];
        $selectorName = $selectorConfig['name'];
        $variantsSelector = new ViewDataCollection();
        foreach ($product->getAllVariants() as $variant) {
            /**
             * @var ProductVariant $variant
             */
            $variant->getAttributes()->setAttributeDefinitions($product->getProductType()->getObj()->getAttributes());
            $selectorAttribute = $variant->getAttributes()->getByName($selectorConfig['attribute']);
            $variantsSelector->add(
                $this->getSelectorData(
                    $selectorAttribute,
                    $variant->getSku(),
                    $product->getSlug(),
                    (!$emptySku ? $sku: null),
                    $selectorConfig['idPrefix']
                )
            );
        }
        $productData->$selectorName = $variantsSelector;

        $productData->details = new ViewDataCollection();
        $productVariant->getAttributes()->setAttributeDefinitions(
            $product->getProductType()->getObj()->getAttributes()
        );
        foreach ($productVariant->getAttributes() as $attribute) {
            $attributeDefinition = $product->getProductType()->getObj()->getAttributes()->getByName(
                $attribute->getName()
            );
            $attributeData = new ViewData();
            $attributeData->text = (string)$attributeDefinition->getLabel() . ': ' . (string)$attribute->getValue();
            $productData->details->add($attributeData);
        }

        return $productData;
    }

    protected function getProducts(Request $request)
    {
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
            $this->getCategories(),
            $this->locale,
            $itemsPerPage,
            $currentPage,
            $sort,
            $category,
            $facetDefinitions
        );

        $this->applyPagination(new Uri($request->getRequestUri()), $offset, $total, $itemsPerPage);
        $this->pagination->productsCount = $products->count();
        $this->pagination->totalProducts = $total;
        $this->facets = $facets;

        return $products;
    }

    /**
     * @param Attribute $attribute
     * @param $variantSku
     * @param $productSlug
     * @param $sku
     * @param $idPrefix
     * @return ViewData
     */
    protected function getSelectorData(Attribute $attribute, $variantSku, $productSlug, $sku, $idPrefix)
    {
        $size = new ViewData();
        $size->id = $idPrefix . '-' . (string)$attribute->getValue();
        $size->text = (string)$attribute->getValue();
        $url = $this->getLinkFor(
            'pdp',
            ['slug' => $productSlug, 'sku' => $variantSku]
        );
        $size->value = $url;
        $size->selected = ($variantSku == $sku);

        return $size;
    }
}
