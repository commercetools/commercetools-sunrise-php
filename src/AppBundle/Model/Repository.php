<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model;


use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Request\AbstractApiRequest;
use Commercetools\Core\Request\QueryAllRequestInterface;
use Commercetools\Sunrise\AppBundle\Profiler\CTPProfilerExtension;
use Commercetools\Sunrise\AppBundle\Profiler\Profile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Repository
{
    const CACHE_TTL = 3600;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CacheAdapterInterface
     */
    protected $cache;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var CTPProfilerExtension
     */
    protected $profiler;

    public function __construct($config, CacheAdapterInterface $cache, Client $client, CTPProfilerExtension $profiler)
    {
        if (is_array($config)) {
            $config = new Config($config);
        }
        $this->cache = $cache;
        $this->config = $config;
        $this->client = $client;
        $this->profiler = $profiler;
    }

    /**
     * @param $repository
     * @param $cacheKey
     * @param QueryAllRequestInterface $request
     * @param int $ttl
     * @return mixed
     */
    protected function retrieveAll(
        $repository, $cacheKey,
        QueryAllRequestInterface $request,
        $force = false,
        $ttl = self::CACHE_TTL
    ) {
        $data = [];
        if (!$force && $this->config['cache.' . $repository] && $this->cache->has($cacheKey)) {
            $cachedData = $this->cache->fetch($cacheKey);
            if (!empty($cachedData)) {
                $data = $cachedData;
            }
            $result = unserialize($data);
            $result->setContext($this->client->getConfig()->getContext());
        } else {
            $helper = new QueryHelper();
            $this->profiler->enter($profile = new Profile('retrieveAll' . ucfirst($repository)));
            $result = $helper->getAll($this->client, $request);
            $this->profiler->leave($profile);
            $this->store($repository, $cacheKey, serialize($result), $ttl);
        }

        return $result;
    }

    /**
     * @param $repository
     * @param $cacheKey
     * @param AbstractApiRequest $request
     * @param int $ttl
     * @return \Commercetools\Core\Model\Common\JsonDeserializeInterface|null
     */
    protected function retrieve($repository, $cacheKey, AbstractApiRequest $request, $force = false, $ttl = self::CACHE_TTL)
    {
        if (!$force && $this->config['cache.' . $repository] && $this->cache->has($cacheKey)) {
            $cachedData = $this->cache->fetch($cacheKey);
            if (empty($cachedData)) {
                throw new NotFoundHttpException("resource not found");
            }
            $result = unserialize($cachedData);
            $result->setContext($this->client->getConfig()->getContext());
        } else {
            $this->profiler->enter($profile = new Profile('retrieve' . ucfirst($repository)));
            $response = $request->executeWithClient($this->client);
            $this->profiler->leave($profile);
            if ($response->isError() || is_null($response->toObject())) {
                $this->store($repository, $cacheKey, '', $ttl);
                throw new NotFoundHttpException("resource not found");
            }
            $result = $request->mapResponse($response);
            $this->store($repository, $cacheKey, serialize($result), $ttl);
        }

        return $result;
    }

    protected function store($repository, $cacheKey, $data, $ttl)
    {
        if ($this->config['cache.' . $repository]) {
            $this->cache->store($cacheKey, $data, $ttl);
        }
    }
}
