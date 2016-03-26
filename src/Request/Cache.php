<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/26/16
 * Time: 3:24 PM
 */

namespace STS\Sdk\Request;

use Stash\Pool;
use STS\Sdk\Request;

class Cache
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * Cache constructor.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->setPool($pool);
    }


    /**
     * @param Request $request
     *
     * @return string
     */
    public function getCacheKey(Request $request)
    {
        return "Sdk/"
        . $request->getServiceName() . "/"
        . $request->getOperation()->getName() . "/"
        . md5(json_encode($request->getOperation()->getData()));
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function shouldCache(Request $request)
    {
        return $request->getDescription()->wantsCache() && $request->getOperation()->wantsCache();
    }

    /**
     * @param Request $request
     * @param         $response
     */
    public function store(Request $request, $response)
    {
        $this->setPool($request->getDescription()->getCachePool());

        $item = $this->getPool()->getItem($this->getCacheKey($request))->set($response);

        $this->getPool()->save($item);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function has(Request $request)
    {
        $this->setPool($request->getDescription()->getCachePool());

        return $this->getPool()->getItem($this->getCacheKey($request))->isHit();
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        $this->setPool($request->getDescription()->getCachePool());

        return $this->getPool()->getItem($this->getCacheKey($request))->get();
    }

    /**
     * @return Pool
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * @param Pool $pool
     */
    public function setPool($pool)
    {
        $this->pool = $pool;
    }
}