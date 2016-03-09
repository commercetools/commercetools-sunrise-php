<?php
/**
 * @author Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\Customers\CustomerByIdGetRequest;
use Commercetools\Sunrise\AppBundle\Entity\UserAddress;
use Commercetools\Sunrise\AppBundle\Entity\UserDetails;
use Commercetools\Sunrise\AppBundle\Model\View\Url;
use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Sunrise\AppBundle\Model\ViewDataCollection;
use Commercetools\Sunrise\AppBundle\Security\User\CTPUser;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserController extends SunriseController
{
    protected function getViewData($title, Request $request = null)
    {
        $viewData = parent::getViewData($title, $request);
        $viewData->content->url = new ViewData();

        $viewData->content->sidebarItemOne = new Url('personal details', $this->generateUrl('myDetails'));
        $viewData->content->sidebarItemTwo = new Url('addressbook', $this->generateUrl('myAdressBook'));
        $viewData->content->sidebarItemFour =  new Url('myOrders', $this->generateUrl('myOrders'));
        $viewData->content->sidebarItemSeven = new Url('logout', $this->generateUrl('logout'));

//        $viewData->content->sidebarItemThree = $this->generateUrl('myAccount'); // payment details
//        $viewData->content->sidebarItemSix = $this->generateUrl(''); // wishlist
//        $viewData->content->sidebarItemFive = $this->generateUrl(''); // returns exchange

        return $viewData;
    }

    public function editAddressAction(Request $request)
    {
        $addressId = $request->get('id');
        $viewData = $this->getViewData('MyAccount - Edit Address', $request);

        $customer = $this->getCustomer($this->getUser());

        $address = $customer->getAddresses()->getById($addressId);

        $userAddress = UserAddress::ofAddress($address);

        $viewData->content->address = new ViewData();

        $viewData->content->contentTitle = $this->trans('User addresses');

        $viewData->content->address->firstName = $userAddress->getFirstName();
        $viewData->content->address->lastName = $userAddress->getLastName();
        $viewData->content->address->streetName = $userAddress->getStreetName();
        $viewData->content->address->streetNumber = $userAddress->getStreetNumber();
        $viewData->content->address->postalCode = $userAddress->getPostalCode();
        $viewData->content->address->city = $userAddress->getCity();
        $viewData->content->address->region = $userAddress->getRegion();
        $viewData->content->address->country = $userAddress->getCountry();
        $viewData->content->address->company = $userAddress->getCompany();
        $viewData->content->address->phone = $userAddress->getPhone();
        $viewData->content->address->email = $userAddress->getEmail();

        $countries = new ViewData();
        $countries->list = new ViewDataCollection();

        foreach ($this->config->get('countries') as $country) {
            $entry = new ViewData();
            $entry->label = $country;
            $entry->value = $country;
            if ($address->getCountry() == $country) {
                $entry->selected = true;
            }
            $countries->list->add($entry);
        }
        $viewData->content->address->countries = $countries;

        $form = $this->createNamedFormBuilder('', $userAddress)
            ->add('csrfToken', TextType::class)
            ->add('additionalStreetInfo', TextType::class)
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('streetName', TextType::class)
            ->add('streetNumber', TextType::class)
            ->add('postalCode', TextType::class)
            ->add('city', TextType::class)
            ->add('region', TextType::class)
            ->add('country', TextType::class)
            ->add('company', TextType::class)
            ->add('phone', TextType::class)
            ->add('email', TextType::class)
            ->add('title', ChoiceType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            /**
             * @var UserDetails $userDetails
             */

            try {
                $this->get('app.repository.customer')
                    ->setAddresses(
                        $customer,
                        $userAddress->toCTPAddress(),
                        $addressId
                    );
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $this->trans($e->getMessage(), 'customers'));
                return new Response($e->getMessage());
            }
            return $this->redirect($this->generateUrl('myAdressBook'));
        }

        return $this->render('my-account-address-edit.hbs', $viewData->toArray());
    }

    public function loginAction(Request $request)
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->generateUrl('myAccount'));
        }
        $viewData = $this->getViewData('MyAccount - Login', $request);
        $authUtils = $this->get('security.authentication_utils');
        $error = $authUtils->getLastAuthenticationError();
        $lastUsername = $authUtils->getLastUsername();

        return $this->render('my-account-login.hbs', $viewData->toArray());
    }

    public function secretAction(Request $request)
    {
        //@todo change button, now it navigate to the detail page
        return $this->redirect($this->generateUrl('myDetails'));
    }

    public function detailsAction(Request $request)
    {
        $viewData = $this->getViewData('MyAccount - Details', $request);

        $customerId = $this->getUser()->getID();

        /**
         * @var Customer $customer
         */
        $customer = $this->get('app.repository.customer')->getCustomer($customerId);

        $viewData->content->personalDetails = new ViewData();
        $viewData->content->personalDetails->name = $customer->getFirstName() . ' ' . $customer->getLastName();
        $viewData->content->personalDetails->password = $this->trans('Password: ********');
        $viewData->content->personalDetails->email = $customer->getEmail();

        $editCustomer = new ViewData();
        $editCustomer->firstName = $customer->getFirstName();
        $editCustomer->secondName = $customer->getLastName();
        $editCustomer->email = $customer->getEmail();
        $viewData->content->editPersonalDetails = $editCustomer->toArray();

        $userDetails = UserDetails::ofCustomer($customer);

        $form = $this->createNamedFormBuilder('', $userDetails)
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('email', TextType::class)
            ->add('currentPassword', TextType::class)
            ->add(
                'password',
                RepeatedType::class,
                ['type' => PasswordType::class, 'first_name' => 'main', 'second_name' => 'confirm']
            )
            ->add('save', SubmitType::class, array('label' => 'Save user'))
            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            /**
             * @var UserDetails $userDetails
             */
            $userDetails = $form->getData();
            $firstName = $userDetails->getFirstName();
            $lastName = $userDetails->getLastName();
            $email = $userDetails->getEmail();
            $currentPassword = $userDetails->getCurrentPassword();
            $newPassword = $userDetails->getPassword();

            $customer = $this->get('app.repository.customer')
                ->setCustomerDetails($customer, $firstName, $lastName, $email);

            if (is_null($customer)) {
                $this->addFlash('error', 'Error updating user');
                return $this->redirect($this->generateUrl('myDetails'));
            } else {
                $this->addFlash('notice', 'User updated');
            }

            try {
                $this->get('app.repository.customer')
                    ->setNewPassword($customer, $currentPassword, $newPassword);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $this->trans($e->getMessage(), [], 'customers'));
                return new Response($e->getMessage());
            }

            return $this->redirect($this->generateUrl('myDetails'));
        }

        return $this->render('my-account-personal-details.hbs', $viewData->toArray());
    }

    public function addressesAction(Request $request)
    {
        $viewData = $this->getViewData('MyAccount - Details', $request);

        $customer = $this->getCustomer($this->getUser());

        $viewData->content->shippingAddress = $this->getViewAddress($customer->getDefaultShippingAddress());
        $viewData->content->billingAddress = $this->getViewAddress($customer->getDefaultBillingAddress());;

        return $this->render('my-account-address-book.hbs', $viewData->toArray());
    }

    protected function getViewAddress(Address $address)
    {
        $viewAddress = new ViewData();
        $viewAddress->name = $address->getFirstName() . ' ' . $address->getLastName();
        $viewAddress->address = $address->getCity() . ' ' . $address->getPostalCode();
        $viewAddress->address = $address->getStreetName() . ' ' . $address->getStreetNumber() . ' ';
        $viewAddress->postalCode = $address->getPostalCode() . ' ' . $address->getCity();
        $viewAddress->country = $address->getCountry();
        $viewAddress->compay = $address->getCompany();
        $viewAddress->link = $this->trans($this->generateUrl('editAddress', ['id' => $address->getId()]));

        return $viewAddress;
    }

    public function ordersAction(Request $request)
    {
        $viewData = $this->getViewData('MyAccount - Orders', $request);
        $orders = $this->get('app.repository.order')->getOrders($this->getUser()->getId());

        $viewData->content->orderNumberTitle = $this->trans('my-account:orderNumber');

        /**
         * @var Order $order
         */
        $viewData->content->order = new ViewDataCollection();
        foreach ($orders as $order) {

            $orderData = new ViewData();
            $order->orderNumber = $order->getOrderNumber();
            $orderData->date = $order->getCreatedAt()->format('d.m.Y');
            $orderData->total = $order->getTotalPrice();
            $orderData->paymentStatus = $order->getPaymentState();
            $orderData->shipping = $order->getShipmentState();
            $orderData->view = 'VIEW';
            $orderData->detailUri = $this->generateUrl('myOrderDetails', ['orderId' => $order->getId()]);

            $viewData->content->order->add($orderData);
        }

        return $this->render('my-account-my-orders.hbs', $viewData->toArray());
    }


    public function orderDetailAction(Request $request, $orderId)
    {
        $viewData = $this->getViewData('MyAccount - Orders', $request);

        /**
         * @var Order $order
         */
        $order = $this->get('app.repository.order')->getOrder($orderId);


        if ($order->getCustomerId() !== $this->getUser()->getId()) {
            throw new AccessDeniedException();
        }

        $this->addOrderDetails($viewData->content, $order);

        return $this->render('my-account-my-orders-order.hbs', $viewData->toArray());
    }

    protected function addOrderDetails($content, Order $order)
    {
        $content->yourOrderTitle = $this->trans('Your Order Details');
        $content->orderNumberTitle = $this->trans('Order number');
        $content->orderNumber = $order->getOrderNumber();
        $content->orderDateTitle = $this->trans('Order date');
        $content->orderDate = $order->getCreatedAt()->format('d.m.Y');
        $content->printReceiptBtn = $this->trans('print receipt');

        $shippingAddress = $order->getShippingAddress();
        $shippingAddressData = new ViewData();

        $shippingAddressData->title = $this->trans('shipping details');
        $shippingAddressData->name = $shippingAddress->getFirstName(). ' ' . $shippingAddress->getLastName();
        $shippingAddressData->address = $shippingAddress->getStreetName() . ' ' . $shippingAddress->getStreetNumber();
        $shippingAddressData->city = $shippingAddress->getCity();
        $shippingAddressData->region = $shippingAddress->getRegion();
        $shippingAddressData->postalCode = $shippingAddress->getPostalCode();
        $shippingAddressData->country = $shippingAddress->getCountry();
        $shippingAddressData->number = $shippingAddress->getPhone();
        $shippingAddressData->email = $shippingAddress->getEmail();

        $content->shippingAddress = $shippingAddressData;

        $billingAddress = $order->getBillingAddress();
        $billingAddressData = new ViewData();

        $billingAddressData->title = $this->trans('billing address');
        $billingAddressData->name = $billingAddress->getFirstName(). ' ' . $billingAddress->getLastName();
        $billingAddressData->address = $billingAddress->getStreetName(). ' ' . $billingAddress->getStreetNumber();
        $billingAddressData->city = $billingAddress->getCity();
        $billingAddressData->region = $billingAddress->getRegion();
        $billingAddressData->postalCode = $billingAddress->getPostalCode();
        $billingAddressData->country = $billingAddress->getCountry();
        $billingAddressData->number = $billingAddress->getPhone();
        $billingAddressData->email = $billingAddress->getEmail();

        $content->billingAddress = $billingAddressData;

        $shippingMethod = $order->getShippingInfo();
        $shippingMethodData = new ViewData();
        $shippingMethodData->title = $this->trans('Shipping Method');

        if (!is_null($shippingMethod)) {
            $shippingMethodData->text = $this->trans($shippingMethod->getShippingMethodName(), [], 'orders');
            $content->shippingMethod = $shippingMethodData;
        }

        $content->Code = $order->getDiscountCodes()->toArray();
        $content->subtotal = $order->getTaxedPrice()->getTotalNet();

        $content->orderDiscountTitle = $this->trans('Order discount');
        $content->standartDeliveryTitle = $this->trans('Standard Delivery');

        $content->promoCode = $order->getDiscountCodes();

        $content->salesTaxTitle = $this->trans('Sales Tax');
        $content->orderTotalTitle = $this->trans('Order Total');
        if ($order->getShippingInfo()) {
            $content->standartDelivery = $order->getShippingInfo()->getPrice();
        }
        $content->orderTotal = $order->getTotalPrice();
        $content->salesTax = Money::ofCurrencyAndAmount(
            $order->getTaxedPrice()->getTotalGross()->getCurrencyCode(),
            $order->getTaxedPrice()->getTotalGross()->getCentAmount()
            - $order->getTaxedPrice()->getTotalNet()->getCentAmount()
        );

        $lineItems = $order->getLineItems();

        $content->order = new ViewDataCollection();
        foreach ($lineItems as $lineItem) {
            $variant = $lineItem->getVariant();

            $lineItemsData = new ViewData();
            $lineItemsData->totalPrice = (string)$lineItem->getTotalPrice();
            $lineItemsData->image = $variant->getImages()->current()->getUrl();
            $lineItemsData->quantity = (string)$lineItem->getQuantity();
            $price = $lineItem->getPrice();
            if (!is_null($price->getDiscounted())) {
                $lineItemsData->discountedPrice = (string)$price->getDiscounted()->getValue();
            }
            $lineItemsData->price = (string)$price->getValue();
            $lineItemsData->productTitleOne = $lineItem->getName();
            $lineItemsData->sku = $lineItem->getVariant()->getSku();
            $content->order->add($lineItemsData);
        }
        $content->productDescriptionTitle = $this->trans('Product Description');
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
