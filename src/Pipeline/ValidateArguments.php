<?php
namespace RC\Sdk\Pipeline;

use Closure;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\Translator;

/**
 * Class ValidateParameters
 * @package RC\Sdk\Pipeline
 */
class ValidateArguments
{
    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     * @throws ValidationException
     */
    public function handle($request, Closure $next)
    {
        $rules = $request->getValidationRules($request->getParameters());
        $validator = $this->getValidator($rules, $request->getArguments());

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $next($request);
    }

    /**
     * If we are running inside Laravel, fetch the Validator from IoC. Otherwise build one manually.
     *
     * @param $rules
     * @param $parameters
     *
     * @return Validator
     */
    protected function getValidator($rules, $parameters)
    {
        if(function_exists('app')) {
            // @codeCoverageIgnoreStart
            return app('translator')->make($parameters, $rules);
            // @codeCoverageIgnoreEnd
        }
        return new Validator(new Translator('en'), $parameters, $rules);
    }
}