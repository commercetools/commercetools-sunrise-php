<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Core\Model\Common\Money;
use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Sunrise\AppBundle\Model\ViewDataCollection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderModel extends ViewData
{

    protected $generator;
    protected $cartAttributes;

    public function __construct(UrlGeneratorInterface $generator, $cartAttributes)
    {
        $this->generator = $generator;
        $this->cartAttributes = $cartAttributes;
    }

    public function getViewOrder(Order $order)
    {
        $orderModel = new ViewCart();
//        $cartModel->totalItems = $order->getLineItemCount();
        if ($order->getTaxedPrice()) {
            $salexTax = Money::ofCurrencyAndAmount(
                $order->getTaxedPrice()->getTotalGross()->getCurrencyCode(),
                $order->getTaxedPrice()->getTotalGross()->getCentAmount() -
                $order->getTaxedPrice()->getTotalNet()->getCentAmount(),
                $order->getContext()
            );
            $orderModel->salesTax = (string)$salexTax;
            $orderModel->subtotalPrice = (string)$order->getTaxedPrice()->getTotalNet();
            $orderModel->totalPrice = (string)$order->getTotalPrice();
        }
        if ($order->getShippingInfo()) {
            $shippingInfo = $order->getShippingInfo();
            $orderModel->shippingMethod = new Entry(
                $shippingInfo->getShippingMethodName(),
                $shippingInfo->getShippingMethod()->getId()
            );
            $orderModel->shippingMethod->price = (string)$shippingInfo->getPrice();
        }
        if ($order->getShippingAddress()) {
            $orderModel->shippingAddress = Address::fromCartAddress($order->getShippingAddress());
        }
        if ($order->getBillingAddress()) {
            $orderModel->billingAddress = Address::fromCartAddress($order->getBillingAddress());
        } else {
            $orderModel->billingAddress = Address::fromCartAddress($order->getShippingAddress());
        }

        $orderModel->lineItems = $this->getViewCartLineItems($order);
        return $orderModel;
    }

    protected function getViewCartLineItems(Order $order)
    {
        $orderItems = new ListObject();
        $lineItems = $order->getLineItems();

        if (!is_null($lineItems)) {
            foreach ($lineItems as $lineItem) {
                $variant = $lineItem->getVariant();
                $orderLineitem = new ViewData();
                $orderLineitem->productId = $lineItem->getProductId();
                $orderLineitem->variantId = $variant->getId();
                $orderLineitem->lineItemId = $lineItem->getId();
                $orderLineitem->quantity = $lineItem->getQuantity();
                $lineItemVariant = new ViewData();
                $lineItemVariant->url = (string)$this->generator->generate(
                    'pdp-master',
                    ['slug' => (string)$lineItem->getProductSlug()]
                );
                $lineItemVariant->name = (string)$lineItem->getName();
                $lineItemVariant->image = (string)$variant->getImages()->current()->getUrl();
                $price = $lineItem->getPrice();
                if (!is_null($price->getDiscounted())) {
                    $lineItemVariant->price = (string)$price->getDiscounted()->getValue();
                    $lineItemVariant->priceOld = (string)$price->getValue();
                } else {
                    $lineItemVariant->price = (string)$price->getValue();
                }
                $orderLineitem->variant = $lineItemVariant;
                $orderLineitem->sku = $variant->getSku();
                $orderLineitem->totalPrice = $lineItem->getTotalPrice();
                $orderLineitem->attributes = new ViewDataCollection();

                foreach ($this->cartAttributes as $attributeName) {
                    $attribute = $variant->getAttributes()->getByName($attributeName);
                    if ($attribute) {
                        $lineItemAttribute = new ViewData();
                        $lineItemAttribute->label = $attributeName;
                        $lineItemAttribute->key = $attributeName;
                        $lineItemAttribute->value = (string)$attribute->getValue();
                        $orderLineitem->attributes->add($lineItemAttribute);
                    }
                }
                $orderItems->list->add($orderLineitem);
            }
        }

        return $orderItems;
    }
}
