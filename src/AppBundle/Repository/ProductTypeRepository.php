<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Repository;

use Commercetools\Core\Model\ProductType\ProductTypeCollection;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Sunrise\AppBundle\Model\Repository;

class ProductTypeRepository extends Repository
{
    const NAME = 'productTypes';

    /**
     * @return ProductTypeCollection
     */
    public function getTypes($force = false)
    {
        $cacheKey = static::NAME;
        $productTypeRequest = ProductTypeQueryRequest::of();
        return $this->retrieveAll(static::NAME, $cacheKey, $productTypeRequest, $force);
    }

    public function getById($id)
    {
        $type = $this->getTypes()->getById($id);
        if (is_null($type)) {
            $type = $this->getTypes(true)->getById($id);
        }
        return $type;
    }
}
