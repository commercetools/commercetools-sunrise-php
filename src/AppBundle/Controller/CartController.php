<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Sunrise\AppBundle\Model\View\CartModel;
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
        $viewData = $this->getViewData('Sunrise - Cart', $request);
        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $customerId = $this->get('security.token_storage')->getToken()->getUser()->getId();
        $cart = $this->get('commercetools.repository.cart')->getCart($request->getLocale(), $cartId, $customerId);
        $viewData->content = new ViewData();

        $cartModel = new CartModel($this->get('app.route_generator'), $this->config['sunrise.cart.attributes']);
        $viewData->content->cart = $cartModel->getViewCart($cart);

        $viewData->meta->_links->add(new ViewLink($this->generateUrl('home'), 'continueShopping'));
        $viewData->meta->_links->add(new ViewLink($this->generateUrl('lineItemDelete')), 'deleteLineItem');
        $viewData->meta->_links->add(new ViewLink($this->generateUrl('lineItemChange')), 'changeLineItem');
        $viewData->meta->_links->add(new ViewLink($this->generateUrl('checkout')), 'checkout');

        return $this->render('cart.hbs', $viewData->toArray());
    }

    public function addAction(Request $request)
    {
        $locale = $this->get('commercetools.locale.converter')->convert($request->getLocale());
        $session = $this->get('session');

        $productId = $request->get('productId');
        $variantId = (int)$request->get('variantId');
        $quantity = (int)$request->get('quantity');
        $sku = $request->get('productSku');
        $slug = $request->get('productSlug');
        $cartId = $session->get(CartRepository::CART_ID);
        $country = \Locale::getRegion($locale);
        $currency = $this->config->get('currencies.'. $country);
        $this->get('commercetools.repository.cart')
            ->addLineItem(
                $request->getLocale(),
                $cartId,
                $productId,
                $variantId,
                $quantity,
                $currency,
                $country,
                $this->getCustomerId()
            );

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
            ->changeLineItemQuantity(
                $request->getLocale(),
                $cartId,
                $lineItemId,
                $lineItemCount,
                $this->getCustomerId()
            );

        return new RedirectResponse($this->generateUrl('cart'));
    }

    public function deleteLineItemAction(Request $request)
    {
        $session = $this->get('session');
        $lineItemId = $request->get('lineItemId');
        $cartId = $session->get(CartRepository::CART_ID);
        $this->get('commercetools.repository.cart')
            ->deleteLineItem(
                $request->getLocale(),
                $cartId,
                $lineItemId,
                $this->getCustomerId()
            );

        return new RedirectResponse($this->generateUrl('cart'));
    }

    protected function getCustomerId()
    {
        $user = $this->getUser();
        if (is_null($user)) {
            return null;
        }
        $customerId = $user->getId();

        return $customerId;
    }
}
