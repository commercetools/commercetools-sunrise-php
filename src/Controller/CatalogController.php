<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Request\Products\ProductProjectionBySlugGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CatalogController
{
    public function home(Request $request, Application $app)
    {
        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/output/templates/home.json'),
            true
        );
        $viewData['meta']['assetsPath'] = '/' . $viewData['meta']['assetsPath'];
        // @ToDo remove when cucumber tests are working correctly
        array_unshift($viewData['header']['navMenu']['categories'], ['text' => 'Sunrise Home']);
        return $app['view']->render('home', $viewData);
    }

    public function search(Request $request, Application $app)
    {
        $language = $request->get('lang');
        /**
         * @var Client $client
         */
        $client = $app['client'];
        $client->getConfig()->getContext()->setLanguages($app['languages'][$language]);
        $request = ProductProjectionSearchRequest::of();
        $response = $request->executeWithClient($client);
        $products = $request->mapResponse($response);

        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/output/templates/pop.json'),
            true
        );
        $viewData['meta']['assetsPath'] = '/' . $viewData['meta']['assetsPath'];
        $viewData['content']['products']['list'] = [];
        foreach ($products as $key => $product) {
            $price = $product->getMasterVariant()->getPrices()->getAt(0)->getValue();
            $productData = [
                'id' => $product->getId(),
                'text' => (string)$product->getName(),
                'description' => (string)$product->getDescription(),
                'url' => (string)$product->getSlug() . '.html',
                'imageUrl' => (string)$product->getMasterVariant()->getImages()->getAt(0)->getUrl(),
                'price' => (string)$price,
                'new' => true, // ($product->getCreatedAt()->getDateTime()->modify('14 days ago') > new \DateTime())
            ];
            foreach ($product->getMasterVariant()->getImages() as $image) {
                $productData['images'][] = [
                    'thumbImage' => $image->getThumb() ? :'http://placehold.it/200x200',
                    'bigImage' => $image->getUrl() ? :'http://placehold.it/200x200'
                ];
            }
            $viewData['content']['products']['list'][] = $productData;
        }
        /**
         * @var callable $renderer
         */
        $html = $app['view']->render('product-overview', $viewData);
        return $html;
    }

    public function detail(Request $request, Application $app)
    {
        $slug = $request->get('slug');
        $language = $request->get('lang');
        /**
         * @var Client $client
         */
        $client = $app['client'];
        $client->getConfig()->getContext()->setLanguages($app['languages'][$language]);
        $request = ProductProjectionBySlugGetRequest::ofSlugAndContext($slug, $client->getConfig()->getContext());
        $response = $request->executeWithClient($client);

        if ($response->isError() || is_null($response->toObject())) {
            throw new NotFoundHttpException("product $slug does not exist.");
        }
        $product = $request->mapResponse($response);
        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/output/templates/pdp.json'),
            true
        );
        $viewData['meta']['assetsPath'] = '/' . $viewData['meta']['assetsPath'];

        $productData = [
            'id' => $product->getId(),
            'text' => (string)$product->getName(),
            'description' => (string)$product->getDescription(),
            'url' => (string)$product->getSlug(),
            'imageUrl' => (string)$product->getMasterVariant()->getImages()->getAt(0)->getUrl(),
        ];
        foreach ($product->getMasterVariant()->getImages() as $image) {
            $productData['images'][] = [
                'thumbImage' => $image->getMedium(),
                'bigImage' => $image->getUrl()
            ];
        }
        $viewData['content']['product'] = array_merge(
            $viewData['content']['product'],
            $productData
        );
        return $app['view']->render('product-detail', $viewData);
    }
}
