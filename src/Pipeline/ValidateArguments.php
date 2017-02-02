<?php
namespace STS\Sdk\Pipeline;

use Closure;
use STS\Sdk\Exceptions\ValidationException;
use Illuminate\Support\Facades\Validator;
use STS\Sdk\Request;

/**
 * Class ValidateParameters
 * @package RC\Sdk\Pipeline
 */
class ValidateArguments implements PipeInterface
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

        if ($validator->fails()) {
            throw new ValidationException($validator->messages());
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

        return Validator::make($data, $rules);
    }
}
