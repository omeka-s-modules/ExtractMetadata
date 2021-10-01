<?php
namespace ExtractMetadata\Service\Mapper;

use ExtractMetadata\Mapper\JsonPointer;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class JsonPointerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $crosswalk = require sprintf('%s/../../../config/json_pointer_crosswalk.php', __DIR__);
        return new JsonPointer($crosswalk, $services->get('Omeka\EntityManager'));
    }
}
