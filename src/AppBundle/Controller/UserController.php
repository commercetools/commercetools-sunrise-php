<?php
/**
 * @author Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends SunriseController
{
    public function login(Request $request)
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->generateUrl('myAccount'));
        }
        $viewData = $this->getViewData('MyAccount - Login', $request);
        $authUtils = $this->get('security.authentication_utils');
        // get the login error if there is one
        $error = $authUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authUtils->getLastUsername();

        return $this->render('my-account-login.hbs', $viewData->toArray());
    }

    public function secret(Request $request)
    {
        return new Response('Top secret');
    }

    public function details(Request $request)
    {

        $viewData = $this->getViewData('MyAccount - Details', $request);

        return $this->render('my-account-personal-details.hbs', $viewData->toArray());
    }
}
