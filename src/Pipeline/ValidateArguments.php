<?php
namespace STS\Sdk\Pipeline;

use Closure;
use STS\Sdk\Exceptions\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\Translator;
use STS\Sdk\Request;

/**
 * Class ValidateParameters
 * @package RC\Sdk\Pipeline
 */
class ValidateArguments implements PipeInterface
{
    /**
     * I stole these from the resources/lang/en/validation.php array that comes with a full
     * Laravel install.
     *
     * @var array
     */
    protected $messages = [
        'alpha' => 'The :attribute may only contain letters.',
        'alpha_dash' => 'The :attribute may only contain letters, numbers, and dashes.',
        'alpha_num' => 'The :attribute may only contain letters and numbers.',
        'array' => 'The :attribute must be an array.',
        'before' => 'The :attribute must be a date before :date.',
        'between' => [
            'numeric' => 'The :attribute must be between :min and :max.',
            'file' => 'The :attribute must be between :min and :max kilobytes.',
            'string' => 'The :attribute must be between :min and :max characters.',
            'array' => 'The :attribute must have between :min and :max items.',
        ],
        'boolean' => 'The :attribute field must be true or false.',

        'date' => 'The :attribute is not a valid date.',
        'date_format' => 'The :attribute does not match the format :format.',
        'different' => 'The :attribute and :other must be different.',
        'digits' => 'The :attribute must be :digits digits.',
        'digits_between' => 'The :attribute must be between :min and :max digits.',
        'email' => 'The :attribute must be a valid email address.',

        'in' => 'The selected :attribute is invalid.',
        'integer' => 'The :attribute must be an integer.',
        'ip' => 'The :attribute must be a valid IP address.',
        'json' => 'The :attribute must be a valid JSON string.',
        'max' => [
            'numeric' => 'The :attribute may not be greater than :max.',
            'file' => 'The :attribute may not be greater than :max kilobytes.',
            'string' => 'The :attribute may not be greater than :max characters.',
            'array' => 'The :attribute may not have more than :max items.',
        ],
        'min' => [
            'numeric' => 'The :attribute must be at least :min.',
            'file' => 'The :attribute must be at least :min kilobytes.',
            'string' => 'The :attribute must be at least :min characters.',
            'array' => 'The :attribute must have at least :min items.',
        ],
        'not_in' => 'The selected :attribute is invalid.',
        'numeric' => 'The :attribute must be a number.',
        'regex' => 'The :attribute format is invalid.',
        'required' => 'The :attribute field is required.',
        'required_if' => 'The :attribute field is required when :other is :value.',
        'required_with' => 'The :attribute field is required when :values is present.',
        'required_with_all' => 'The :attribute field is required when :values is present.',
        'required_without' => 'The :attribute field is required when :values is not present.',
        'required_without_all' => 'The :attribute field is required when none of :values are present.',
        'same' => 'The :attribute and :other must match.',
        'size' => [
            'numeric' => 'The :attribute must be :size.',
            'file' => 'The :attribute must be :size kilobytes.',
            'string' => 'The :attribute must be :size characters.',
            'array' => 'The :attribute must contain :size items.',
        ],
        'string' => 'The :attribute must be a string.',
        'timezone' => 'The :attribute must be a valid zone.',
        'unique' => 'The :attribute has already been taken.',
        'url' => 'The :attribute format is invalid.',
    ];

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
        return new Validator(new Translator('en'), $data, $rules, $this->messages);
    }
}