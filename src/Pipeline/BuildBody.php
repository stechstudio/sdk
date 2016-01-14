<?php
namespace RC\Sdk\Pipeline;

use Closure;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\Translator;

/**
 * Class BuildBody
 * @package RC\Sdk\Pipeline
 */
class BuildBody
{
    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $bodyParams = $this->getBodyParams($request->config['parameters']);
        $bodyArguments = $this->getBodyArguments($bodyParams, $request->arguments);

        // Not sure if I need to json_encode this now or not
        $request->body = $bodyArguments;

        return $next($request);
    }

    /**
     * Return a simple array of parameter names that should be located in our body
     *
     * @param $parameters
     *
     * @return array
     */
    protected function getBodyParams($parameters)
    {
        return array_keys(array_filter($parameters, function($details) {
            return $details['location'] == 'body';
        }));
    }

    protected function getBodyArguments($bodyParams, $arguments)
    {
        return array_intersect_key($arguments, array_flip($bodyParams));
    }

}