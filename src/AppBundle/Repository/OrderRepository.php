<?php
/**
 * @author: @Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Repository;


use Commercetools\Core\Client;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Request\Orders\OrderByIdGetRequest;
use Commercetools\Core\Request\Orders\OrderQueryRequest;
use Commercetools\Sunrise\AppBundle\Model\Repository;

class OrderRepository extends Repository
{
    const NAME = 'order';

    /**
     * @param $customerId
     * @return OrderCollection
     */
    public function getOrders($customerId)
    {
        $request = OrderQueryRequest::of()->where('customerId = "' . $customerId . '"');
        $response = $request->executeWithClient($this->client);
        $orders = $request->mapResponse($response);

        return $orders;
    }

    /**
     * @param $orderId
     * @return Order
     */
    public function getOrder($orderId)
    {
        $request = OrderByIdGetRequest::ofId($orderId);
        $response = $request->executeWithClient($this->client);
        $order = $request->mapResponse($response);
        return $order;
    }
}