<?php
/**
 * @author @Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;


use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Request\Customers\Command\CustomerChangeAddressAction;
use Commercetools\Core\Request\Customers\CustomerByIdGetRequest;
use Commercetools\Core\Request\Customers\CustomerUpdateRequest;
use Commercetools\Sunrise\AppBundle\Entity\UserAddress;
use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Sunrise\AppBundle\Security\User\CTPUser;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserController extends SunriseController
{
    public function editAddress(Request $request)
    {
        $customer = $this->getCustomer($this->getUser());
        $address = $customer->getDefaultShippingAddress();

        $userAddress = new UserAddress();
        $userAddress->setFirstName($address->getFirstName());
        $userAddress->setLastName($address->getLastName());
        $userAddress->setCompany($address->getCompany());
        $userAddress->setEmail($customer->getEmail());
        $userAddress->setTitle($customer->getTitle());

        $userAddress->setStreetName($address->getStreetName());
        $userAddress->setStreetNumber($address->getStreetNumber());
        $userAddress->setPostalCode($address->getPostalCode());
        $userAddress->setCity($address->getCity());
        $userAddress->setRegion($address->getRegion());
        $userAddress->setCountry($address->getCountry());
        $userAddress->setPhone($address->getPhone());

        $form = $this->createFormBuilder($userAddress)
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('streetName', TextType::class)
            ->add('StreetNumber', TextType::class)
            ->add('PostalCode', TextType::class)
            ->add('City', TextType::class)
            ->add('Region', TextType::class)
            ->add('Country', TextType::class)
            ->add('Company', TextType::class)
            ->add('Phone', TextType::class)
            ->add('Email', TextType::class)
            ->add('title', TextType::class)
            ->add('save', SubmitType::class, array('label' => 'Save user'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            /**
             * @var UserAddress $formAddress
             */
            $formAddress = $form->getData();
            $newAddress = Address::of();
            $newAddress->setFirstName($formAddress->getFirstName());
            $newAddress->setLastName($formAddress->getLastName());
            $newAddress->setCompany($formAddress->getCompany());
            $newAddress->setEmail($formAddress->getEmail());
            $newAddress->setTitle($formAddress->getTitle());
            $newAddress->setStreetName($formAddress->getStreetName());
            $newAddress->setStreetNumber($formAddress->getStreetNumber());
            $newAddress->setPostalCode($formAddress->getPostalCode());
            $newAddress->setCity($formAddress->getCity());
            $newAddress->setRegion($formAddress->getRegion());
            $newAddress->setCountry($formAddress->getCountry());
            $newAddress->setPhone($formAddress->getPhone());

            $request = CustomerUpdateRequest::ofIdAndVersion($customer->getId(), $customer->getVersion());
            $request->addAction(CustomerChangeAddressAction::ofAddressIdAndAddress($address->getId(), $newAddress));

            /**
             * @var Client $client
             */
            $client = $this->get('commercetools.client');
            $response = $request->executeWithClient($client);

            $newCustomer = $request->mapResponse($response);

            return new response('User has bin updated!');
        }

        return $this->render('editAddress.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function login(Request $request)
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->generateUrl('myAccount'));
        }
        $viewData = $this->getViewData('MyAccount - Login');
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

        $viewData = $this->getViewData('MyAccount - Details');

        $customer = $this->getCustomer($this->getUser());

        $viewData->content->personalDetails = new ViewData();
        $viewData->content->personalDetails->name = $customer->getFirstName() . ' ' . $customer->getLastName();
        $viewData->content->personalDetails->name = $address->getFirstName() . ' ' . $address->getLastName();


        return $this->render('my-account-personal-details.hbs', $viewData->toArray());
    }

    public function addresses(Request $request)
    {
        $viewData = $this->getViewData('MyAccount - Details');

        $customer = $this->getCustomer($this->getUser());

        $shippingAddressData = $customer->getDefaultShippingAddress();
        $billingAddressData = $customer->getDefaultBillingAddress();


        $shippingAddress = new ViewData();
        $billingAddress = new ViewData();

        $billingAddress->name = $billingAddressData->getFirstName() . ' ' . $billingAddressData->getLastName();
        //adress query
        $billingAddress->address = $billingAddressData->getCity() . ' ' . $billingAddressData->getPostalCode();
        $billingAddress->address = $billingAddressData->getStreetName() . ' ' .
            $billingAddressData->getStreetNumber() . ' ';
        $billingAddress->postalCode = $billingAddressData->getPostalCode() . ' ' . $billingAddressData->getCity();
        $billingAddress->country = $billingAddressData->getCountry();
        $billingAddress->compay = $billingAddressData->getCompany();


        $shippingAddress->title = $shippingAddressData->getTitle();
        $shippingAddress->name = $shippingAddressData->getFirstName() . ' ' . $shippingAddressData->getLastName();

        //adress query
        $shippingAddress->address = $shippingAddressData->getCity() . ' ' . $shippingAddressData->getPostalCode();
        $shippingAddress->address = $shippingAddressData->getStreetName() . ' ' .
            $shippingAddressData->getStreetNumber() . ' ';
        $shippingAddress->postalCode = $shippingAddressData->getPostalCode() . ' ' . $shippingAddressData->getCity();
        $shippingAddress->country = $shippingAddressData->getCountry();

        $viewData->content->shippingAddress = $shippingAddress;
        $viewData->content->billingAddress = $billingAddress;

        return $this->render('my-account-address-book.hbs', $viewData->toArray());

    }

    protected function getCustomer(CTPUser $user)
    {
        if (!$user instanceof CTPUser) {
            throw new \InvalidArgumentException();
        }

        /**
         * @var Client $client
         */
        $client = $this->get('commercetools.client');

        $request = CustomerByIdGetRequest::ofId($user->getId());

        $response = $request->executeWithClient($client);
        $customer = $request->mapResponse($response);

        return $customer;
    }
}
