<?php
/**
 * @author @Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;


use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Sunrise\AppBundle\Repository\CategoryRepository;
use Commercetools\Sunrise\AppBundle\Repository\ProductTypeRepository;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;

class UserController extends SunriseController
{
    protected $authUtils;

    public function __construct(
        Client $client,
        $locale,
        UrlGenerator $generator,
        CacheAdapterInterface $cache,
        TranslatorInterface $translator,
        EngineInterface $templateEngine,
        AuthorizationCheckerInterface $authChecker,
        AuthenticationUtils $authUtils,
        $config,
        Session $session,
        CategoryRepository $categoryRepository,
        ProductTypeRepository $productTypeRepository
    ) {
        $this->authUtils = $authUtils;
        parent::__construct(
            $client,
            $locale,
            $generator,
            $cache,
            $translator,
            $templateEngine,
            $authChecker,
            $config,
            $session,
            $categoryRepository,
            $productTypeRepository
        );
    }

    public function login(Request $request)
    {
        $viewData = $this->getViewData('MyAccount - Login');

        return $this->render('my-account-login.hbs', $viewData->toArray());
    }

    public function secret(Request $request)
    {
        return new Response('Top secret');
    }
}