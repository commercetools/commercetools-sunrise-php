<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Core\Model\Common\Money;
use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Sunrise\AppBundle\Model\ViewDataCollection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CartModel extends ViewData
{

    protected $generator;
    protected $cartAttributes;

    public function __construct(UrlGeneratorInterface $generator, $cartAttributes)
    {
        $this->generator = $generator;
        $this->cartAttributes = $cartAttributes;
    }

    public function getViewCart(Cart $cart)
    {
        $cartModel = new ViewCart();
        $cartModel->totalItems = $cart->getLineItemCount();
        if ($cart->getTaxedPrice()) {
            $salexTax = Money::ofCurrencyAndAmount(
                $cart->getTaxedPrice()->getTotalGross()->getCurrencyCode(),
                $cart->getTaxedPrice()->getTotalGross()->getCentAmount() -
                $cart->getTaxedPrice()->getTotalNet()->getCentAmount(),
                $cart->getContext()
            );
            $cartModel->salesTax = (string)$salexTax;
            $cartModel->subtotalPrice = (string)$cart->getTaxedPrice()->getTotalNet();
            $cartModel->totalPrice = (string)$cart->getTotalPrice();
        }
        if ($cart->getShippingInfo()) {
            $shippingInfo = $cart->getShippingInfo();
            $cartModel->shippingMethod = new Entry(
                $shippingInfo->getShippingMethodName(),
                $shippingInfo->getShippingMethod()->getId()
            );
            $cartModel->shippingMethod->price = (string)$shippingInfo->getPrice();
        }
        if ($cart->getShippingAddress()) {
            $cartModel->shippingAddress = Address::fromCartAddress($cart->getShippingAddress());
        }
        if ($cart->getBillingAddress()) {
            $cartModel->billingAddress = Address::fromCartAddress($cart->getBillingAddress());
        } else {
            $cartModel->billingAddress = Address::fromCartAddress($cart->getShippingAddress());
        }

        $cartModel->lineItems = $this->getViewCartLineItems($cart);
        return $cartModel;
    }

    protected function getViewCartLineItems(Cart $cart)
    {
        $cartItems = new ListObject();
        $lineItems = $cart->getLineItems();

        if (!is_null($lineItems)) {
            foreach ($lineItems as $lineItem) {
                $variant = $lineItem->getVariant();
                $cartLineItem = new ViewData();
                $cartLineItem->productId = $lineItem->getProductId();
                $cartLineItem->variantId = $variant->getId();
                $cartLineItem->lineItemId = $lineItem->getId();
                $cartLineItem->quantity = $lineItem->getQuantity();
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
                $cartLineItem->variant = $lineItemVariant;
                $cartLineItem->sku = $variant->getSku();
                $cartLineItem->totalPrice = $lineItem->getTotalPrice();
                $cartLineItem->attributes = new ViewDataCollection();

                foreach ($this->cartAttributes as $attributeName) {
                    $attribute = $variant->getAttributes()->getByName($attributeName);
                    if ($attribute) {
                        $lineItemAttribute = new ViewData();
                        $lineItemAttribute->label = $attributeName;
                        $lineItemAttribute->key = $attributeName;
                        $lineItemAttribute->value = (string)$attribute->getValue();
                        $cartLineItem->attributes->add($lineItemAttribute);
                    }
                }
                $cartItems->list->add($cartLineItem);
            }
        }

        return $cartItems;
    }
}
