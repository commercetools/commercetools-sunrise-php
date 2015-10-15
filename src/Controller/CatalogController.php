<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Controller;

use Commercetools\Commons\Helper\PriceFinder;
use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
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

        return $filter;
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
        $country = \Locale::getRegion($this->locale);
        $product = $this->productRepository->getProductBySlug($slug);

//        if (empty($sku)) {
//            $productUrl = $this->getLinkFor(
//                'pdp',
//                [
//                    'slug' => (string)$product->getSlug(),
//                    'sku' => $product->getMasterVariant()->getSku()
//                ]
//            );
//            return new RedirectResponse($productUrl);
//        }

//        $viewData = json_decode(
//            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/templates/pdp.json'),
//            true
//        );
//        foreach ($viewData['content']['wishlistWidged']['list'] as &$wish) {
//            $wish['image'] = '/' . $wish['image'];
//        }
        $viewData = $this->getViewData('Sunrise - ProductRepository Detail Page');

        $viewData->content->static = new ViewData();

        $bagItems = new ViewDataCollection();
        for ($i = 1; $i < 10; $i++) {
            $bagItem = new ViewData();
            $bagItem->text = $i;
            $bagItem->value = $i;
            $bagItem->id = 'pdp-bag-items-' . $i;
            $bagItems->add($bagItem);
        }
        $viewData->content->static->bagItems = $bagItems;
        $viewData->content->product = $this->getProductDetailData($product, $sku);
        $viewData->content->static->productDetailsText = $this->trans('product.detailsText');
        $viewData->content->static->deliveryAndReturnsText = $this->trans('product.deliveryReturnsText');
        $viewData->content->static->standardDeliveryText = $this->trans('product.standardDeliveryText');
        $viewData->content->static->expressDeliveryText = $this->trans('product.expressDeliveryText');
        $viewData->content->static->freeReturnsText = $this->trans('product.freeReturnsText');
        $viewData->content->static->moreDeliveryInfoText = $this->trans('product.moreDeliveryInfoText');
        $viewData->content->static->sizeDefaultItem = new ViewData();
        $viewData->content->static->sizeDefaultItem->text = $this->trans('product.sizeDefaultItem');
        $viewData->content->static->sizeDefaultItem->selected = empty($sku);
        $viewData->content->static->sizeDefaultItem->id = "pdp-size-select-first-option";

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

        $sizes = new ViewDataCollection();

        foreach ($product->getAllVariants() as $variant) {
            $variant->getAttributes()->setAttributeDefinitions($product->getProductType()->getObj()->getAttributes());
            $size = new ViewData();
            $variantSize = $variant->getAttributes()->getByName('commonSize');
            $size->id = 'pdp-size-select-' . $variantSize->getValue()->getKey();
            $size->text = $variantSize->getValue()->getLabel();
            $size->value = $variant->getSku() . '.html';
            $size->selected = !$emptySku && ($variant->getSku() == $sku);
            $sizes->add($size);
        }
        $productData->sizes = $sizes;

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

        /**
         * @var ProductProjectionCollection $products
         * @var PagedSearchResponse $response
         */
        list($products, $offset, $total) = $this->productRepository->getProducts(
            $this->getCategories(),
            $this->locale,
            $itemsPerPage,
            $currentPage,
            $sort,
            $category
        );

        $this->applyPagination(new Uri($request->getRequestUri()), $offset, $total, $itemsPerPage);
        $this->pagination->productsCount = $products->count();
        $this->pagination->totalProducts = $total;

        return $products;
    }
}
