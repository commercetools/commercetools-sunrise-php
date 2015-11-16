<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model;


use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Request\AbstractApiRequest;
use Commercetools\Core\Request\QueryAllRequestInterface;
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

    public function __construct(Config $config, CacheAdapterInterface $cache, Client $client)
    {
        $this->cache = $cache;
        $this->config = $config;
        $this->client = $client;
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
        if (!$force && $this->config['default.cache.' . $repository] && $this->cache->has($cacheKey)) {
            $cachedData = $this->cache->fetch($cacheKey);
            if (!empty($cachedData)) {
                $data = $cachedData;
            }
            $result = unserialize($data);
            $result->setContext($this->client->getConfig()->getContext());
        } else {
            $helper = new QueryHelper();
            $result = $helper->getAll($this->client, $request);
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
        if (!$force && $this->config['default.cache.' . $repository] && $this->cache->has($cacheKey)) {
            $cachedData = $this->cache->fetch($cacheKey);
            if (empty($cachedData)) {
                throw new NotFoundHttpException("resource not found");
            }
            $result = unserialize($cachedData);
            $result->setContext($this->client->getConfig()->getContext());
        } else {
            $response = $request->executeWithClient($this->client);

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
        if ($this->config['default.cache.' . $repository]) {
            $this->cache->store($cacheKey, $data, $ttl);
        }
    }
}
