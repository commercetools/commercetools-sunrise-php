<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Controller;


use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Sunrise\Model\Config;
use Commercetools\Sunrise\Model\Repository\CartRepository;
use Commercetools\Sunrise\Model\Repository\CategoryRepository;
use Commercetools\Sunrise\Model\Repository\ProductTypeRepository;
use Commercetools\Sunrise\Model\ViewData;
use Commercetools\Sunrise\Model\ViewDataCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\TranslatorInterface;

class CartController extends SunriseController
{
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
        Config $config,
        Session $session,
        CategoryRepository $categoryRepository,
        ProductTypeRepository $productTypeRepository,
        CartRepository $cartRepository
    )
    {
        parent::__construct(
            $client,
            $locale,
            $generator,
            $cache,
            $translator,
            $config,
            $session,
            $categoryRepository,
            $productTypeRepository
        );
        $this->cartRepository = $cartRepository;
    }

    public function add(Request $request)
    {
        $productId = $request->get('productId');
        $variantId = (int)$request->get('variantId');
        $quantity = (int)$request->get('quantity');
        $sku = $request->get('productSku');
        $slug = $request->get('productSlug');
        $cartId = $this->session->get('cartId');
        $country = \Locale::getRegion($this->locale);
        $currency = $this->config->get('default.currencies.'. $country);
        $cart = $this->cartRepository->addLineItem($cartId, $productId, $variantId, $quantity, $currency, $country);
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
        $lineItemId = $request->get('lineItemId');
        $lineItemCount = (int)$request->get('lineItemCount');
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

    public function index()
    {
        $viewData = $this->getViewData('Sunrise - Cart');
        $cartId = $this->session->get('cartId');
        $cart = $this->cartRepository->getCart($cartId);

        $viewData->content = new ViewData();
        $viewData->content->cart = $this->getCart($cart);
        $viewData->meta->_links = new ViewData();
        $viewData->meta->_links->deleteLineItem = new ViewData();
        $viewData->meta->_links->deleteLineItem->href = $this->generator->generate('lineItemDelete');
        $viewData->meta->_links->changeLineItem = new ViewData();
        $viewData->meta->_links->changeLineItem->href = $this->generator->generate('lineItemChange');

        return ['cart', $viewData];
    }

    protected function getCart(Cart $cart)
    {
        $cartModel = new ViewData();
        $cartModel->itemsTotal = $this->getItemCount($cart);
        if ($cart->getTaxedPrice()) {
            $salexTax = Money::ofCurrencyAndAmount(
                $cart->getTaxedPrice()->getTotalGross()->getCurrencyCode(),
                $cart->getTaxedPrice()->getTotalGross()->getCentAmount() -
                    $cart->getTaxedPrice()->getTotalNet()->getCentAmount(),
                $cart->getContext()
            );
            $cartModel->salesTax = $salexTax;
            $cartModel->subtotal = $cart->getTaxedPrice()->getTotalNet();
            $cartModel->total = $cart->getTotalPrice();
        }
        $cartModel->lineItems = $this->getCartLineItems($cart);

        return $cartModel;
    }

    public function getCartLineItems(Cart $cart)
    {
        $cartItems = new ViewData();
        $cartItems->list = new ViewDataCollection();

        $lineItems = $cart->getLineItems();

        if (!is_null($lineItems)) {
            foreach ($lineItems as $lineItem) {
                $variant = $lineItem->getVariant();
                $cartLineItem = new ViewData();
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
                $cartLineItem->total = $lineItem->getTotalPrice();
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
