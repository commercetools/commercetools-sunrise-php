<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model\Repository;


use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Category\CategoryCollection;
use Commercetools\Core\Model\Product\Filter;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Request\Products\ProductProjectionBySlugGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Sunrise\Model\Repository;

class ProductRepository extends Repository
{
    const NAME = 'products';

    /**
     * @param $slug
     * @return ProductProjection|null
     */
    public function getProductBySlug($slug)
    {
        $cacheKey = static::NAME . '-' . $slug;
        $productRequest = ProductProjectionBySlugGetRequest::ofSlugAndContext($slug, $this->client->getConfig()->getContext());
        return $this->retrieve(static::NAME, $cacheKey, $productRequest);
    }

    /**
     * @param CategoryCollection $categories
     * @param $locale
     * @param $itemsPerPage
     * @param $currentPage
     * @param $sort
     * @param null $category
     * @return \Commercetools\Core\Model\Product\ProductProjectionCollection
     */
    public function getProducts(CategoryCollection $categories, $locale, $itemsPerPage, $currentPage, $sort, $category = null)
    {
        $searchRequest = ProductProjectionSearchRequest::of()
            ->sort($sort)
            ->limit($itemsPerPage)
            ->offset(min($itemsPerPage * ($currentPage - 1),100000));

        if ($category) {
            $category = $categories->getBySlug($category, $locale);
            if ($category instanceof Category) {
                $searchRequest->addFilter(
                    Filter::of()->setName('categories.id')->setValue($category->getId())
                );
            }
        }

        $response = $searchRequest->executeWithClient($this->client);
        $products = $searchRequest->mapResponse($response);

        return [$products, $response->getOffset(), $response->getTotal()];
    }
}
