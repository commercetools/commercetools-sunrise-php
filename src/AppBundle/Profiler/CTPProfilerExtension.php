<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */


namespace Commercetools\Sunrise\AppBundle\Profiler;


use Symfony\Component\Stopwatch\Stopwatch;

class CTPProfilerExtension
{
    private $actives = array();
    private $stopwatch;
    private $events;

    public function __construct(Profile $profile, Stopwatch $stopwatch = null)
    {
        $this->actives[] = $profile;

        $this->stopwatch = $stopwatch;
        $this->events = new \SplObjectStorage();
    }

    public function enter(Profile $profile)
    {
        if ($this->stopwatch) {
            $this->events[$profile] = $this->stopwatch->start($profile->getName(), 'ctp');
        }

        $this->actives[0]->addProfile($profile);
        array_unshift($this->actives, $profile);
    }

    public function leave(Profile $profile)
    {
        $profile->leave();
        array_shift($this->actives);

        if (1 === count($this->actives)) {
            $this->actives[0]->leave();
        }

        if ($this->stopwatch) {
            $this->events[$profile]->stop();
            unset($this->events[$profile]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ctp-profiler';
    }
}
