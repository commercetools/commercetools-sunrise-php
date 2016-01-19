<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */


namespace Commercetools\Sunrise\AppBundle\DataCollector;


use Commercetools\Sunrise\AppBundle\Profiler\Profile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;

class CTPDataCollector extends DataCollector implements LateDataCollectorInterface
{
    private $profile;

    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        // TODO: Implement collect() method.
    }

    public function lateCollect()
    {
        $this->data['profile'] = serialize($this->profile);
    }


    public function getName()
    {
        return 'ctp';
    }
}
