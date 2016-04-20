<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Sunrise\AppBundle\Model\View\ViewLink;
use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Sunrise\AppBundle\Model\ViewDataCollection;
use Commercetools\Symfony\CtpBundle\Model\Repository\CartRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends SunriseController
{
    const CSRF_TOKEN_NAME = 'csrfToken';

    public function indexAction(Request $request)
    {
        $session = $this->get('session');
        $viewData = $this->getViewData('Sunrise - Cart', $request);
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->get('commercetools.repository.cart')->getCart($request->getLocale(), $cartId);
        $viewData->content = new ViewData();
        $viewData->content->cart = $this->getCart($cart);
        $viewData->meta->_links->continueShopping = new ViewLink($this->generateUrl('home'));
        $viewData->meta->_links->deleteLineItem = new ViewLink($this->generateUrl('lineItemDelete'));
        $viewData->meta->_links->changeLineItem = new ViewLink($this->generateUrl('lineItemChange'));
        $viewData->meta->_links->checkout = new ViewLink($this->generateUrl('checkout'));

        return $this->render('cart.hbs', $viewData->toArray());
    }

    public function addAction(Request $request)
    {
        $session = $this->get('session');

        $productId = $request->get('productId');
        $variantId = (int)$request->get('variantId');
        $quantity = (int)$request->get('quantity');
        $sku = $request->get('productSku');
        $slug = $request->get('productSlug');
        $cartId = $session->get(CartRepository::CART_ID);
        $country = \Locale::getRegion($this->locale);
        $currency = $this->config->get('currencies.'. $country);
        $this->get('commercetools.repository.cart')
            ->addLineItem($request->getLocale(), $cartId, $productId, $variantId, $quantity, $currency, $country);

        if (empty($sku)) {
            $redirectUrl = $this->generateUrl('pdp-master', ['slug' => $slug]);
        } else {
            $redirectUrl = $this->generateUrl('pdp', ['slug' => $slug, 'sku' => $sku]);
        }
        return new RedirectResponse($redirectUrl);
    }

    public function miniCartAction(Request $request)
    {
        $viewData = $this->getHeaderViewData('MiniCart', $request);
        $viewData->meta = $this->getMetaData();

        $response = new Response();
        $response->headers->addCacheControlDirective('no-cache');
        $response->headers->addCacheControlDirective('no-store');

        $response = $this->render('common/mini-cart.hbs', $viewData->toArray(), $response);

        return $response;
    }

    public function changeLineItemAction(Request $request)
    {
        $session = $this->get('session');
        $lineItemId = $request->get('lineItemId');
        $lineItemCount = (int)$request->get('quantity');
        $cartId = $session->get(CartRepository::CART_ID);
        $this->get('commercetools.repository.cart')
            ->changeLineItemQuantity($request->getLocale(), $cartId, $lineItemId, $lineItemCount);

        return new RedirectResponse($this->generateUrl('cart'));
    }

    public function deleteLineItemAction(Request $request)
    {
        $session = $this->get('session');
        $lineItemId = $request->get('lineItemId');
        $cartId = $session->get(CartRepository::CART_ID);
        $this->get('commercetools.repository.cart')
            ->deleteLineItem($request->getLocale(), $cartId, $lineItemId);

        return new RedirectResponse($this->generateUrl('cart'));
    }

    public function checkoutAction(Request $request)
    {
        $session = $this->get('session');
        $userId = $session->get(CartRepository::CART_ID);
        if (is_null($userId)) {
            return $this->checkoutSigninAction($request);
        }

        return $this->checkoutShippingAction($request);
    }

    public function checkoutSigninAction(Request $request)
    {
        $viewData = $this->getViewData('Sunrise - Checkout - Signin', $request);
        return $this->render('checkout-signin.hbs', $viewData->toArray());
    }

    public function checkoutShippingAction(Request $request)
    {
        $viewData = $this->getViewData('Sunrise - Checkout - Shipping', $request);
        return $this->render('checkout-shipping.hbs', $viewData->toArray());
    }

    public function checkoutPaymentAction(Request $request)
    {
        $viewData = $this->getViewData('Sunrise - Checkout - Payment', $request);
        return $this->render('checkout-payment.hbs', $viewData->toArray());
    }

    public function checkoutConfirmationAction(Request $request)
    {
        $viewData = $this->getViewData('Sunrise - Checkout - Confirmation', $request);
        return $this->render('checkout-confirmation.hbs', $viewData->toArray());
    }

    protected function getCart(Cart $cart)
    {
        $cartModel = new ViewData();
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
            $cartModel->shippingMethod = new ViewData();
            $cartModel->shippingMethod->price = (string)$shippingInfo->getPrice();
        }

        $cartModel->lineItems = $this->getCartLineItems($cart);
        return $cartModel;
    }

    protected function getCartLineItems(Cart $cart)
    {
        $cartItems = new ViewData();
        $cartItems->list = new ViewDataCollection();

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
                $lineItemVariant->url = (string)$this->generateUrl(
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

                $cartAttributes = $this->config['sunrise.cart.attributes'];
                foreach ($cartAttributes as $attributeName) {
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
