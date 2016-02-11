<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Repository;

use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
use Commercetools\Core\Request\ShippingMethods\ShippingMethodByLocationGetRequest;
use Commercetools\Core\Request\ShippingMethods\ShippingMethodQueryRequest;
use Commercetools\Sunrise\AppBundle\Model\Repository;
use Commercetools\Sunrise\AppBundle\Profiler\Profile;

class ShippingMethodRepository extends Repository
{
    const NAME = 'shippingMethods';

    /**
     * @return ShippingMethodCollection
     */
    public function getShippingMethods($force = false)
    {
        $cacheKey = static::NAME;
        $shippingMethodRequest = ShippingMethodQueryRequest::of();
        return $this->retrieveAll(static::NAME, $cacheKey, $shippingMethodRequest, $force);
    }

    /**
     * @param $name
     * @return \Commercetools\Core\Model\ShippingMethod\ShippingMethod
     */
    public function getByName($name)
    {
        $shippingMethod = $this->getShippingMethods()->getByName($name);
        if (is_null($shippingMethod)) {
            $shippingMethod = $this->getShippingMethods(true)->getByName($name);
        }
        return $shippingMethod;
    }

    /**
     * @param $country
     * @param $currency
     * @return \Commercetools\Core\Response\ApiResponseInterface
     */
    public function getByCountryAndCurrency($country, $currency)
    {
        $request = ShippingMethodByLocationGetRequest::ofCountry($country)->withCurrency($currency);
        $profiler = $this->profiler;
        $profiler->enter($profile = new Profile('getShippingMethodByCountryAndCurrency'));
        return $this->client->executeAsync($request)->then(
            function($response) use ($profiler, $profile) {
                $profiler->leave($profile);
                return $response;
            }
        );
    }
}
