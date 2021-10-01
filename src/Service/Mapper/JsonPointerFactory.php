<?php
namespace ExtractMetadata\Service\Mapper;

use ExtractMetadata\Mapper\JsonPointer;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class JsonPointerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $crosswalk = require 'json_pointer_crosswalk.php';
        return new JsonPointer($crosswalk, $services->get('Omeka\EntityManager'));
    }
}
