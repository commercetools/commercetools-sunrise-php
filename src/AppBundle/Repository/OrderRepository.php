<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Repository;

use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Request\Orders\OrderByIdGetRequest;
use Commercetools\Core\Request\Orders\OrderQueryRequest;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class OrderRepository extends Repository
{
    /**
     * @param $locale
     * @param $customerId
     * @return OrderCollection
     */
    public function getOrders($locale, $customerId)
    {
        $client = $this->getClient();
        $request = OrderQueryRequest::of()->where('customerId = "' . $customerId . '"');
        $response = $request->executeWithClient($client);
        $orders = $request->mapFromResponse(
            $response,
            $this->mapperFactory->build($locale, $request->getResultClass())
        );

        return $orders;
    }

    /**
     * @param $locale
     * @param $orderId
     * @return Order
     */
    public function getOrder($locale, $orderId)
    {
        $client = $this->getClient();
        $request = OrderByIdGetRequest::ofId($orderId);
        $response = $request->executeWithClient($client);
        $order = $request->mapFromResponse(
            $response,
            $this->mapperFactory->build($locale, $request->getResultClass())
        );
        return $order;
    }
}
