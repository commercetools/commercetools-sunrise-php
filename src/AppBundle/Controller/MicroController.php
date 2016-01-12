<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */


namespace Commercetools\Sunrise\AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MicroController extends Controller
{
    public function indexAction()
    {
        return new Response('Bla');
    }

    public function randomAction($limit)
    {
        $number = rand(0, $limit);

        return $this->render('home.hbs', [
            'number' => $number
        ]);
    }
}
