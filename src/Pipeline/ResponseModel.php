<?php
namespace STS\Sdk\Pipeline;

use Closure;
use STS\Sdk\Exceptions\ServiceUnavailableException;
use STS\Sdk\Request;
use STS\Sdk\Request\UriBuilder;
use STS\Sdk\Response\Builder;
use STS\Sdk\Response\Model;

/**
 * Class ResponseModel
 * @package RC\Sdk\Pipeline
 */
class ResponseModel implements PipeInterface
{
    /**
     * @var Builder
     */
    private $builder;

    /**
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed|Model
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // No model class? Return the raw response.
        if(!$request->getOperation()->hasResponseModelClass()) {
            return $response;
        }

        $class = $request->getOperation()->getResponseModelClass();

        // A collection response needs a collection of models.
        if($request->getOperation()->wantsResponseCollection()) {
            return $this->builder->collection($class, $response);
        }

        // Still here? Return a single model.
        return $this->builder->single($class, $response);
    }
}