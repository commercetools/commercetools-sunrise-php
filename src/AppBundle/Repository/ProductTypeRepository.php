<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Repository;

use Commercetools\Core\Model\ProductType\ProductType;
use Commercetools\Core\Model\ProductType\ProductTypeCollection;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class ProductTypeRepository extends Repository
{
    const NAME = 'productType';

    /**
     * @param string $locale
     * @param bool $force
     * @return ProductTypeCollection
     */
    public function getTypes($locale, $force = false)
    {
        $client = $this->getClient($locale);
        $cacheKey = static::NAME;
        $productTypeRequest = ProductTypeQueryRequest::of();
        return $this->retrieveAll($client, $cacheKey, $productTypeRequest, $force);
    }

    /**
     * @param $locale
     * @param $id
     * @return ProductType
     */
    public function getById($locale, $id)
    {
        $type = $this->getTypes($locale)->getById($id);
        if (is_null($type)) {
            $type = $this->getTypes($locale, true)->getById($id);
        }
        return $type;
    }
}
