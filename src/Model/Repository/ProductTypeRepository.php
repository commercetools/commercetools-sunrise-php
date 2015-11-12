<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model\Repository;

use Commercetools\Core\Model\ProductType\ProductTypeCollection;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Sunrise\Model\Repository;

class ProductTypeRepository extends Repository
{
    const NAME = 'productTypes';

    /**
     * @return ProductTypeCollection
     */
    public function getTypes()
    {
        $cacheKey = static::NAME;
        $productTypeRequest = ProductTypeQueryRequest::of();
        return $this->retrieveAll(static::NAME, $cacheKey, $productTypeRequest);
    }

    public function getById($id)
    {
        return $this->getTypes()->getById($id);
    }
}
