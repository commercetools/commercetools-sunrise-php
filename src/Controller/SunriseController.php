<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Controller;


use Commercetools\Sunrise\Model\Collection;
use Commercetools\Sunrise\Model\View\Header;
use Commercetools\Sunrise\Model\View\Tree;
use Commercetools\Sunrise\Model\View\Url;
use Commercetools\Sunrise\Model\ViewData;
use Commercetools\Sunrise\Template\TemplateService;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\TranslatorInterface;

class SunriseController
{
    /**
     * @var TemplateService
     */
    protected $view;
    /**
     * @var UrlGenerator
     */
    protected $generator;
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $config;

    public function __construct(
        TemplateService $view,
        UrlGenerator $generator,
        TranslatorInterface $translator,
        $config
    )
    {
        $this->view = $view;
        $this->generator = $generator;
        $this->translator = $translator;
        $this->config = $config;
    }

    protected function getHeaderViewData($title)
    {
        $header = new Header($title);
        $header->stores = new Url($this->trans('header.stores'), '');
        $header->help = new Url($this->trans('header.help'), '');
        $header->callUs = new Url(
            $this->trans('header.callUs', ['%phone%' => $this->config['header']['callUs']]),
            ''
        );
        $header->location = [
            'language' => [
                [
                    'text' => 'German',
                    'value' => '',
                    'selected' => true
                ]
            ],
            'country' => [
                [
                    'text' => 'Germany',
                    'value' => '',
                    'selected' => true
                ]
            ]
        ];
        $header->user = new ViewData();
        $header->user->isLoggedIn = false;
        $header->user->signIn = new Url('Login', '');
        $header->miniCart = new Url('MiniCart', '');
        $header->navMenu = new ViewData();
        $header->navMenu->new = new Url($this->trans('header.nav.new'), '');
        $header->navMenu->sale = new Url($this->trans('header.nav.sale'), '');

        $womenCategory = new Tree('Women', '');
        $categoriesSubTree1 = new Tree('Women', '');
        $categoriesSubTree1->addNode(new Url('Basic example', ''));
        $categoriesSubTree1->addNode(new Url('Button toolbar', ''));
        $categoriesSubTree1->addNode(new Url('Sizing', ''));
        $categoriesSubTree1->addNode(new Url('Nesting', ''));
        $categoriesSubTree1->addNode(new Url('Vertical variation', ''));

        $categoriesSubTree2 = new Tree('Glasses', '');
        $categoriesSubTree2->addNode(new Url('Lorem', ''));
        $categoriesSubTree2->addNode(new Url('Ipsum', ''));

        $categoriesSubTree3 = new Tree('Women', '');
        $categoriesSubTree3->addNode(new Url('Basic example', ''));
        $categoriesSubTree3->addNode(new Url('Button toolbar', ''));
        $categoriesSubTree3->addNode(new Url('Sizing', ''));
        $categoriesSubTree3->addNode(new Url('Nesting', ''));
        $categoriesSubTree3->addNode(new Url('Vertical variation', ''));

        $categoriesSubTree4 = new Tree('Glasses', '');
        $categoriesSubTree4->addNode(new Url('Lorem', ''));
        $categoriesSubTree4->addNode(new Url('Ipsum', ''));


        $womenCategory->addNode($categoriesSubTree1);
        $womenCategory->addNode($categoriesSubTree2);
        $womenCategory->addNode($categoriesSubTree3);
        $womenCategory->addNode($categoriesSubTree4);

        $menCategory = new Tree('Men', '');
        $categoriesSubTree1 = new Tree('Men', '');
        $categoriesSubTree1->addNode(new Url('Basic example', ''));
        $categoriesSubTree1->addNode(new Url('Button toolbar', ''));
        $categoriesSubTree1->addNode(new Url('Sizing', ''));
        $categoriesSubTree1->addNode(new Url('Nesting', ''));
        $categoriesSubTree1->addNode(new Url('Vertical variation', ''));

        $categoriesSubTree2 = new Tree('Glasses', '');
        $categoriesSubTree2->addNode(new Url('Lorem', ''));
        $categoriesSubTree2->addNode(new Url('Very long navigation item', ''));
        $categoriesSubTree2->addNode(new Url('Very long navigation items will be truncated', ''));
        $categoriesSubTree2->addNode(new Url('Button toolbar', ''));
        $categoriesSubTree2->addNode(new Url('Sizing', ''));
        $categoriesSubTree2->addNode(new Url('Nesting', ''));
        $categoriesSubTree2->addNode(new Url('Very long navigation items will be truncated', ''));
        $categoriesSubTree2->addNode(new Url('Very long navigation item', ''));

        $categoriesSubTree3 = new Tree('Men', '');
        $categoriesSubTree3->addNode(new Url('Basic example', ''));
        $categoriesSubTree3->addNode(new Url('Button toolbar', ''));
        $categoriesSubTree3->addNode(new Url('Sizing', ''));
        $categoriesSubTree3->addNode(new Url('Nesting', ''));
        $categoriesSubTree3->addNode(new Url('Very long navigation items will be truncated', ''));

        $categoriesSubTree4 = new Tree('Glasses', '');
        $categoriesSubTree4->addNode(new Url('Lorem', ''));
        $categoriesSubTree4->addNode(new Url('Very long navigation item', ''));
        $categoriesSubTree4->addNode(new Url('Very long navigation items will be truncated', ''));
        $categoriesSubTree4->addNode(new Url('Button toolbar', ''));
        $categoriesSubTree4->addNode(new Url('Sizing', ''));
        $categoriesSubTree4->addNode(new Url('Nesting', ''));
        $categoriesSubTree4->addNode(new Url('Very long navigation items will be truncated', ''));
        $categoriesSubTree4->addNode(new Url('Very long navigation item', ''));


        $menCategory->addNode($categoriesSubTree1);
        $menCategory->addNode($categoriesSubTree2);
        $menCategory->addNode($categoriesSubTree3);
        $menCategory->addNode($categoriesSubTree4);

        $header->navMenu->categories = new Collection();
        $header->navMenu->categories->add($womenCategory);
        $header->navMenu->categories->add($menCategory);

        $brands = new Tree('Brands', '');
        $brandsSubTree1 = new Tree('Brands', '');
        $brandsSubTree1->addNode(new Url('Basic example', ''));
        $brandsSubTree1->addNode(new Url('Button toolbar', ''));
        $brandsSubTree1->addNode(new Url('Sizing', ''));
        $brandsSubTree1->addNode(new Url('Nesting', ''));
        $brandsSubTree1->addNode(new Url('Vertical variation', ''));

        $brandsSubTree2 = new Tree('Glasses', '');
        $brandsSubTree2->addNode(new Url('Lorem', ''));
        $brandsSubTree2->addNode(new Url('Ipsum', ''));
        $brands->addNode($brandsSubTree1);
        $brands->addNode($brandsSubTree2);

        $header->navMenu->brands = $brands;

        return ['header' => $header->toArray()];
    }

    protected function trans($id, $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }
}
