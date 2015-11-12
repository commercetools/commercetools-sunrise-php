<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model\Repository;


use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\CartDraft;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Request\Carts\CartByIdGetRequest;
use Commercetools\Core\Request\Carts\CartCreateRequest;
use Commercetools\Core\Request\Carts\CartUpdateRequest;
use Commercetools\Core\Request\Carts\Command\CartAddLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodAction;
use Commercetools\Sunrise\Model\Config;
use Commercetools\Sunrise\Model\Repository;

class CartRepository extends Repository
{
    const NAME = 'cart';

    public function __construct(Config $config, CacheAdapterInterface $cache, Client $client, $locale)
    {
        parent::__construct($config, $cache, $client);
    }


    public function getCart($cartId = null)
    {
        $cart = null;
        if ($cartId) {
            $cartRequest = CartByIdGetRequest::ofId($cartId);
            $cartResponse = $cartRequest->executeWithClient($this->client);
            $cart = $cartRequest->mapResponse($cartResponse);
        }

        if (is_null($cart)) {
            $cart = Cart::of($this->client->getConfig()->getContext());
        }

        return $cart;
    }

    /**
     * @param $cartId
     * @param $productId
     * @param $variantId
     * @param $quantity
     * @return Cart|null
     */
    public function addLineItem($cartId, $productId, $variantId, $quantity, $currency, $country)
    {
        $cart = $this->getOrCreateCart($cartId, $currency, $country);

        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
        $cartUpdateRequest->addAction(
            CartAddLineItemAction::ofProductIdVariantIdAndQuantity($productId, $variantId, $quantity)
        );
        $cartResponse = $cartUpdateRequest->executeWithClient($this->client);
        $cart = $cartUpdateRequest->mapResponse($cartResponse);
        return $cart;
    }

    /**
     * @param $cartId
     * @param $currency
     * @param $country
     * @return Cart|null
     */
    public function getOrCreateCart($cartId, $currency, $country)
    {
        $cart = null;
        if (!is_null($cartId)) {
            $cartRequest = CartByIdGetRequest::ofId($cartId);
            $cartResponse = $cartRequest->executeWithClient($this->client);
            $cart = $cartRequest->mapResponse($cartResponse);
        }

        if (is_null($cart)) {
            $cartDraft = CartDraft::ofCurrency($currency)->setCountry($country);
            $cartCreateRequest = CartCreateRequest::ofDraft($cartDraft);
            $cartResponse = $cartCreateRequest->executeWithClient($this->client);
            $cart = $cartCreateRequest->mapResponse($cartResponse);

            $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
            $cartUpdateRequest->addAction(CartSetShippingAddressAction::of()->setAddress(Address::of()->setCountry($country)));
            $cartResponse = $cartUpdateRequest->executeWithClient($this->client);
            $cart = $cartUpdateRequest->mapResponse($cartResponse);
        }

        return $cart;
    }
}
