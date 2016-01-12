<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Repository;


use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\CartDraft;
use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
use Commercetools\Core\Request\Carts\CartByIdGetRequest;
use Commercetools\Core\Request\Carts\CartCreateRequest;
use Commercetools\Core\Request\Carts\CartUpdateRequest;
use Commercetools\Core\Request\Carts\Command\CartAddLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartChangeLineItemQuantityAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveLineItemAction;
use Commercetools\Sunrise\AppBundle\Model\Config;
use Commercetools\Sunrise\AppBundle\Model\Repository;

class CartRepository extends Repository
{
    protected $shippingMethodRepository;

    const NAME = 'cart';

    public function __construct(
        Config $config,
        CacheAdapterInterface $cache,
        Client $client,
        ShippingMethodRepository $shippingMethodRepository,
        $locale
    ) {
        parent::__construct($config, $cache, $client);
        $this->shippingMethodRepository = $shippingMethodRepository;
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
        $cart = null;
        if (!is_null($cartId)) {
            $cart = $this->getCart($cartId);
        }

        if (is_null($cart)) {
            $lineItems = LineItemDraftCollection::of()->add(
                LineItemDraft::of()->setProductId($productId)
                    ->setVariantId($variantId)
                    ->setQuantity($quantity)
            );
            $cart = $this->createCart($currency, $country, $lineItems);
        } else {
            $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
            $cartUpdateRequest->addAction(
                CartAddLineItemAction::ofProductIdVariantIdAndQuantity($productId, $variantId, $quantity)
            );
            $cartResponse = $cartUpdateRequest->executeWithClient($this->client);
            $cart = $cartUpdateRequest->mapResponse($cartResponse);
        }

        return $cart;
    }

    public function deleteLineItem($cartId, $lineItemId)
    {
        $cart = $this->getCart($cartId);

        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
        $cartUpdateRequest->addAction(
            CartRemoveLineItemAction::ofLineItemId($lineItemId)
        );
        $cartResponse = $cartUpdateRequest->executeWithClient($this->client);
        $cart = $cartUpdateRequest->mapResponse($cartResponse);

        return $cart;
    }

    public function changeLineItemQuantity($cartId, $lineItemId, $quantity)
    {
        $cart = $this->getCart($cartId);

        $cartUpdateRequest = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
        $cartUpdateRequest->addAction(
            CartChangeLineItemQuantityAction::ofLineItemIdAndQuantity($lineItemId, $quantity)
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
    public function createCart($currency, $country, LineItemDraftCollection $lineItems)
    {
        $shippingMethodResponse = $this->shippingMethodRepository->getByCountryAndCurrency($country, $currency);
        $cartDraft = CartDraft::ofCurrency($currency)->setCountry($country)
            ->setShippingAddress(Address::of()->setCountry($country))
            ->setLineItems($lineItems);
        if (!$shippingMethodResponse->isError()) {
            /**
             * @var ShippingMethodCollection $shippingMethods
             */
            $shippingMethods = $shippingMethodResponse->toObject();
            $cartDraft->setShippingMethod($shippingMethods->current()->getReference());
        }
        $cartCreateRequest = CartCreateRequest::ofDraft($cartDraft);
        $cartResponse = $cartCreateRequest->executeWithClient($this->client);
        $cart = $cartCreateRequest->mapResponse($cartResponse);

        return $cart;
    }
}
