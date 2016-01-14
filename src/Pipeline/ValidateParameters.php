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
class ValidateParameters
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
        $rules = $this->getRules($request->config['parameters']);
        $validator = $this->getValidator($rules, $request->parameters);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $next($request);
    }

    /**
     * The validation rules are currently in a sub-array for each parameter, need to flatten
     * this down to a simple $parameter -> $validationArray key/value pair.
     * @param $parameters
     *
     * @return array
     */
    protected function getRules($parameters)
    {
        return array_map(function($details) {
            if(isset($details['validate'])) {
                return $details['validate'];
            }
            return '';
        }, $parameters);
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
            return app('translator');
        }
        return new Validator(new Translator('en'), $parameters, $rules);
    }
}