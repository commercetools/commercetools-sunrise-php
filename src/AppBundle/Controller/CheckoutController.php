<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;

use Commercetools\Symfony\CtpBundle\Security\User\User;
use Symfony\Component\HttpFoundation\Request;

class CheckoutController extends SunriseController
{
    public function checkoutAction(Request $request)
    {
        $user = $this->getUser();
        if ($user instanceof User && !is_null($user->getId())) {
            return $this->checkoutShippingAction($request);
        }

        return $this->checkoutSigninAction($request);
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
}
