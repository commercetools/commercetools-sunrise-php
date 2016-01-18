<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;


use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Sunrise\AppBundle\Model\Config;
use Commercetools\Sunrise\AppBundle\Repository\CartRepository;
use Commercetools\Sunrise\AppBundle\Repository\CategoryRepository;
use Commercetools\Sunrise\AppBundle\Repository\ProductTypeRepository;
use Commercetools\Sunrise\AppBundle\Model\View\ViewLink;
use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Sunrise\AppBundle\Model\ViewDataCollection;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\TranslatorInterface;

class CartController extends SunriseController
{
    const CSRF_TOKEN_NAME = 'csrfToken';

    /**
     * @var CartRepository
     */
    protected $cartRepository;

    public function __construct(
        Client $client,
        $locale,
        UrlGenerator $generator,
        CacheAdapterInterface $cache,
        TranslatorInterface $translator,
        EngineInterface $templateEngine,
        $config,
        Session $session,
        CategoryRepository $categoryRepository,
        ProductTypeRepository $productTypeRepository,
        CartRepository $cartRepository
    )
    {
        if (is_array($config)) {
            $config = new Config($config);
        }
        parent::__construct(
            $client,
            $locale,
            $generator,
            $cache,
            $translator,
            $templateEngine,
            $config,
            $session,
            $categoryRepository,
            $productTypeRepository
        );
        $this->cartRepository = $cartRepository;
    }

    public function index()
    {
        $viewData = $this->getViewData('Sunrise - Cart');
        $cartId = $this->session->get('cartId');
        $cart = $this->cartRepository->getCart($cartId);
        $viewData->content = new ViewData();
        $viewData->content->cart = $this->getCart($cart);
        $viewData->meta->_links->continueShopping = new ViewLink($this->generator->generate('home'));
        $viewData->meta->_links->deleteLineItem = new ViewLink($this->generator->generate('lineItemDelete'));
        $viewData->meta->_links->changeLineItem = new ViewLink($this->generator->generate('lineItemChange'));
        $viewData->meta->_links->checkout = new ViewLink($this->generator->generate('checkout'));

        return $this->render('cart.hbs', $viewData->toArray());
    }

    public function add(Request $request)
    {
        // TODO: enable if product add form has a csrf token
//        if (!$this->validateCsrfToken(static::CSRF_TOKEN_FORM, $request->get(static::CSRF_TOKEN_NAME))) {
//            throw new \InvalidArgumentException('CSRF Token invalid');
//        }
        $productId = $request->get('productId');
        $variantId = (int)$request->get('variantId');
        $quantity = (int)$request->get('quantity');
        $sku = $request->get('productSku');
        $slug = $request->get('productSlug');
        $cartId = $this->session->get('cartId');
        $country = \Locale::getRegion($this->locale);
        $currency = $this->config->get('currencies.'. $country);
        $cart = $this->cartRepository->addLineItem($cartId, $productId, $variantId, $quantity, $currency, $country);
        $this->session->set('cartId', $cart->getId());
        $this->session->set('cartNumItems', $this->getItemCount($cart));
        $this->session->save();

        if (empty($sku)) {
            $redirectUrl = $this->generator->generate('pdp-master', ['slug' => $slug]);
        } else {
            $redirectUrl = $this->generator->generate('pdp', ['slug' => $slug, 'sku' => $sku]);
        }
        return new RedirectResponse($redirectUrl);
    }

    public function changeLineItem(Request $request)
    {
        if (!$this->validateCsrfToken(static::CSRF_TOKEN_FORM, $request->get(static::CSRF_TOKEN_NAME))) {
            throw new \InvalidArgumentException('CSRF Token invalid');
        }
        $lineItemId = $request->get('lineItemId');
        $lineItemCount = (int)$request->get('quantity');
        $cartId = $this->session->get('cartId');
        $cart = $this->cartRepository->changeLineItemQuantity($cartId, $lineItemId, $lineItemCount);

        $this->session->set('cartNumItems', $this->getItemCount($cart));
        $this->session->save();

        return new RedirectResponse($this->generator->generate('cart'));
    }

    public function deleteLineItem(Request $request)
    {
        $lineItemId = $request->get('lineItemId');
        $cartId = $this->session->get('cartId');
        $cart = $this->cartRepository->deleteLineItem($cartId, $lineItemId);

        $this->session->set('cartNumItems', $this->getItemCount($cart));
        $this->session->save();

        return new RedirectResponse($this->generator->generate('cart'));
    }

    public function checkout(Request $request)
    {
        $userId = $this->session->get('userId');
        if (is_null($userId)) {
            return $this->checkoutSignin($request);
        }

        return $this->checkoutShipping($request);
    }

    public function checkoutSignin(Request $request)
    {
        $viewData = $this->getViewData('Sunrise - Checkout - Signin');
        return $this->render('checkout-signin.hbs', $viewData->toArray());
    }

    public function checkoutShipping(Request $request)
    {
        $viewData = $this->getViewData('Sunrise - Checkout - Shipping');
        return $this->render('checkout-shipping.hbs', $viewData->toArray());
    }

    public function checkoutPayment(Request $request)
    {
        $viewData = $this->getViewData('Sunrise - Checkout - Payment');
        return $this->render('checkout-payment.hbs', $viewData->toArray());
    }

    public function checkoutConfirmation(Request $request)
    {
        $viewData = $this->getViewData('Sunrise - Checkout - Confirmation');
        return $this->render('checkout-confirmation.hbs', $viewData->toArray());
    }

    protected function getItemCount(Cart $cart)
    {
        $count = 0;
        if ($cart->getLineItems()) {
            foreach ($cart->getLineItems() as $lineItem) {
                $count+= $lineItem->getQuantity();
            }
        }
        return $count;
    }

    protected function getCart(Cart $cart)
    {
        $cartModel = new ViewData();
        $cartModel->totalItems = $this->getItemCount($cart);
        if ($cart->getTaxedPrice()) {
            $salexTax = Money::ofCurrencyAndAmount(
                $cart->getTaxedPrice()->getTotalGross()->getCurrencyCode(),
                $cart->getTaxedPrice()->getTotalGross()->getCentAmount() -
                    $cart->getTaxedPrice()->getTotalNet()->getCentAmount(),
                $cart->getContext()
            );
            $cartModel->salesTax = $salexTax;
            $cartModel->subtotalPrice = $cart->getTaxedPrice()->getTotalNet();
            $cartModel->totalPrice = $cart->getTotalPrice();
        }
        if ($cart->getShippingInfo()) {
            $shippingInfo = $cart->getShippingInfo();
            $cartModel->shippingMethod = new ViewData();
            $cartModel->shippingMethod->value = $shippingInfo->getShippingMethodName();
            $cartModel->shippingMethod->label = $shippingInfo->getShippingMethodName();
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
                $cartLineItem->url = (string)$this->generator->generate(
                    'pdp-master',
                    ['slug' => (string)$lineItem->getProductSlug()]
                );
                $cartLineItem->name = (string)$lineItem->getName();
                $cartLineItem->image = (string)$variant->getImages()->current()->getUrl();
                $cartLineItem->sku = $variant->getSku();
                $price = $lineItem->getPrice();
                if (!is_null($price->getDiscounted())) {
                    $cartLineItem->price = (string)$price->getDiscounted()->getValue();
                    $cartLineItem->priceOld = (string)$price->getValue();
                } else {
                    $cartLineItem->price = (string)$price->getValue();
                }
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
