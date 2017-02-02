<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 4/1/16
 * Time: 4:48 PM
 */

namespace STS\Sdk\Response;

use Illuminate\Support\Collection;
use STS\Sdk\Exceptions\ServiceResponseException;

/**
 * Builds a response with a response class and data.
 * @package STS\Sdk\Response
 */
class Builder
{
    /**
     * @param $class
     * @param $data
     *
     * @return mixed
     */
    public function single($class, $data)
    {
        $this->verifyArray($data);
        return new $class($data);
    }

    /**
     * @param $class
     * @param $data
     *
     * @return Collection
     */
    public function collection($class, $data) {
        return new Collection(
            array_map(function($data) use ($class) {
                $this->verifyArray($data);
                return $this->single($class, $data);
            }, $data)
        );
    }

    /**
     * @param $data
     *
     * @throws ServiceResponseException
     */
    protected function verifyArray($data)
    {
        if(!is_array($data)) {
            throw new ServiceResponseException("Malformed response from service");
        }
    }
}
