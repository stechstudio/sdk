<?php
namespace STS\Sdk\Pipeline;

use Closure;
use STS\Sdk\Exceptions\ServiceUnavailableException;
use STS\Sdk\Request;
use STS\Sdk\Request\Cache;

/**
 * Class CacheFallback
 * @package STS\Sdk\Pipeline
 */
class CacheFallback implements PipeInterface
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     * @throws ServiceUnavailableException
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next)
    {
        // If this isn't an operation we should cache, just move right along
        if (!$this->cache->shouldCache($request)) {
            return $next($request);
        }

        $this->cache->setPool($request->getDescription()->getCachePool());

        try {
            $response = $next($request);

            $this->cache->store($request, $response);

            return $response;

        } catch (ServiceUnavailableException $e) {
            // Fallback to cache if it exists
            if ($this->cache->has($request)) {
                return $this->cache->get($request);
            }

            // If not...
            throw $e;
        }
    }
}
