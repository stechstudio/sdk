<?php
namespace RC\Sdk\Pipeline;

use Closure;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\Translator;
use RC\Sdk\Request;

/**
 * Class ValidateParameters
 * @package RC\Sdk\Pipeline
 */
class ValidateArguments
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     * @throws ValidationException
     */
    public function handle(Request $request, Closure $next)
    {
        $rules = $request->getOperation()->getValidationRules();
        $validator = $this->getValidator($rules, $request->getOperation()->getData());

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $next($request);
    }

    /**
     * @param $rules
     * @param $data
     *
     * @return Validator
     */
    protected function getValidator($rules, $data)
    {
        return new Validator(new Translator('en'), $data, $rules);
    }
}