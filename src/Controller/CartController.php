<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Controller;


use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Sunrise\Model\Config;
use Commercetools\Sunrise\Model\Repository\CartRepository;
use Commercetools\Sunrise\Model\Repository\CategoryRepository;
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

    /**
     * @var Session
     */
    protected $session;

    public function __construct(
        Client $client,
        $locale,
        UrlGenerator $generator,
        CacheAdapterInterface $cache,
        TranslatorInterface $translator,
        Config $config,
        CategoryRepository $categoryRepository,
        CartRepository $cartRepository,
        Session $session
    )
    {
        parent::__construct($client, $locale, $generator, $cache, $translator, $config, $categoryRepository);
        $this->cartRepository = $cartRepository;
        $this->session = $session;
    }

    public function add(Request $request)
    {
        $productId = $request->get('productId');
        $variantId = (int)$request->get('variantId');
        $quantity = (int)$request->get('quantity');
        $sku = $request->get('sku');
        $slug = $request->get('productSlug');
        $cartId = $this->session->get('cartId');
        $country = \Locale::getRegion($this->locale);
        $currency = $this->config->get('default.currencies.'. $country);
        $cart = $this->cartRepository->addLineItem($cartId, $productId, $variantId, $quantity, $currency, $country);
        $this->session->set('cartId', $cart->getId());
        $this->session->save();

        return new RedirectResponse(
            $this->generator->generate('pdp', ['slug' => $slug, 'sku' => $sku])
        );
    }

    public function index()
    {
        $viewData = $this->getViewData('Sunrise - Cart');
        $viewData->cartItems = $this->getCartItems();


        return ['cart', $viewData];
    }

    public function getCartItems()
    {
        $cartItems = new ViewData();
        $cartItems->list = new ViewDataCollection();

        $cart = $this->cartRepository->getCart();

        $lineItems = $cart->getLineItems();

        if (!is_null($lineItems)) {
            foreach ($lineItems as $lineItem) {
                $cartLineItem = new ViewData();
                $cartItems->list->add($cartLineItem);
            }
        }

        return $cartItems;
    }
}
