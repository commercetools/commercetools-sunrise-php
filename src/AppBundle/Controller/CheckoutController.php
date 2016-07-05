<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;

use Commercetools\Sunrise\AppBundle\Model\View\Address;
use Commercetools\Sunrise\AppBundle\Model\View\AddressForm;
use Commercetools\Sunrise\AppBundle\Model\View\CartModel;
use Commercetools\Sunrise\AppBundle\Model\View\Error;
use Commercetools\Sunrise\AppBundle\Model\View\ViewLink;
use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Symfony\CtpBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CtpBundle\Security\User\User;
use Particle\Validator\Validator;
use Symfony\Component\HttpFoundation\Request;

class CheckoutController extends SunriseController
{
    public function checkoutAction(Request $request)
    {
        $user = $this->getUser();
        if ($user instanceof User && !is_null($user->getId())) {
            return $this->checkoutAddressAction($request);
        }

        return $this->checkoutAddressAction($request);
    }

    public function checkoutSigninAction(Request $request)
    {
        $viewData = $this->getViewData('Sunrise - Checkout - Signin', $request);

        $viewData->content->returningCustomerTitle = $this->trans('signin.returningCustomerTitle', [], 'checkout');
        $viewData->content->ifYouHaveAccountTitle = $this->trans('signin.ifYouHaveAccountTitle', [], 'checkout');
        $viewData->content->emailTitle = $this->trans('signin.emailTitle', [], 'checkout');
        $viewData->content->passwordTitle = $this->trans('signin.passwordTitle', [], 'checkout');
        $viewData->content->rememberMeTitle = $this->trans('signin.rememberMeTitle', [], 'checkout');
        $viewData->content->quickCheckoutBtn = $this->trans('signin.quickCheckoutBtn', [], 'checkout');
        $viewData->content->guestCheckoutTitle = $this->trans('signin.guestCheckoutTitle', [], 'checkout');
        $viewData->content->guestCheckoutParagraph1 = $this->trans('signin.guestCheckoutParagraph1', [], 'checkout');
        $viewData->content->guestCheckoutParagraph2 = $this->trans('signin.guestCheckoutParagraph2', [], 'checkout');
        $viewData->content->continueGuestBtn = $this->trans('signin.continueGuestBtn', [], 'checkout');
        
        return $this->render('checkout-signin.hbs', $viewData->toArray());
    }

    public function checkoutAddressAction(Request $request)
    {
        $session = $this->get('session');
        $cartId = $session->get(CartRepository::CART_ID);
        $cart = $this->get('commercetools.repository.cart')->getCart($request->getLocale(), $cartId);

        $viewData = $this->getViewData('Sunrise - Checkout - Address', $request);
        $viewData->meta->_links->add(new ViewLink($this->generateUrl('checkoutAddress')), 'checkoutAddressesSubmit');
        $addressForm = new AddressForm();
        $addressForm->billingAddressDifferentToBillingAddress = false;
        
        if ($request->isMethod('post')) {
            $validator = new Validator();
            $validator->required('firstNameShipping', $this->trans('form.firstName', [], 'main'))->lengthBetween(2, 50)->alpha();
            $validator->required('lastNameShipping', $this->trans('form.lastName', [], 'main'))->lengthBetween(2, 50)->alpha();
            $validator->required('streetNameShipping', $this->trans('form.addressOne', [], 'main'))->lengthBetween(2, 50)->alpha();
            $validator->optional('additionalStreetInfoShipping', $this->trans('form.addressTwo', [], 'main'))->lengthBetween(2, 50)->alpha();
            $validator->required('cityShipping', $this->trans('form.city', [], 'main'))->lengthBetween(2, 50)->alpha();
            $validator->required('postalCodeShipping', $this->trans('form.postCode', [], 'main'))->lengthBetween(2, 10)->alpha();
            $validator->required('countryShipping', $this->trans('form.country', [], 'main'))->length(2)->alpha();
            $validator->optional('regionShipping', $this->trans('form.region', [], 'main'))->lengthBetween(2, 50)->alpha();
            $validator->optional('phoneShipping', $this->trans('form.phone', [], 'main'))->phone('de');
            $validator->optional('emailShipping', $this->trans('form.email', [], 'main'))->email();

            if ($request->get('billingAddressDifferentToBillingAddress', false)) {
                $validator->required('firstNameBilling', $this->trans('form.firstName', [], 'main'))->lengthBetween(2, 50)->alpha();
                $validator->required('lastNameBilling', $this->trans('form.lastName', [], 'main'))->lengthBetween(2, 50)->alpha();
                $validator->required('streetNameBilling', $this->trans('form.addressOne', [], 'main'))->lengthBetween(2, 50)->alpha();
                $validator->optional('additionalStreetInfoBilling', $this->trans('form.addressTwo', [], 'main'))->lengthBetween(2, 50)->alpha();
                $validator->required('cityBilling', $this->trans('form.city', [], 'main'))->lengthBetween(2, 50)->alpha();
                $validator->required('postalCodeBilling', $this->trans('form.postCode', [], 'main'))->lengthBetween(2, 10)->alpha();
                $validator->required('countryBilling', $this->trans('form.country', [], 'main'))->length(2)->alpha();
                $validator->optional('regionBilling', $this->trans('form.region', [], 'main'))->lengthBetween(2, 50)->alpha();
                $validator->optional('phoneBilling', $this->trans('form.phone', [], 'main'))->phone('de');
                $validator->optional('emailBilling', $this->trans('form.email', [], 'main'))->email();
            }

            $result = $validator->validate($request->request->all());
            if ($result->isNotValid()) {
                var_dump($request->get('firstNameShipping'));
                var_dump($result->getFailures());
                foreach ($result->getMessages() as $message) {
                    $addressForm->errors->globalErrors->add(new Error(current($message)));
                }
            }
        }
        $viewData->content->addressForm = $addressForm;
        $cartModel = new CartModel($this->get('app.route_generator'), $this->config['sunrise.cart.attributes']);
        $viewData->content->cart = $cartModel->getViewCart($cart);

        return $this->render('checkout-address.hbs', $viewData->toArray());
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
}
