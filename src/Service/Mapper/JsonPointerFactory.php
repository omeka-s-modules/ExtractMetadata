<?php
namespace ExtractMetadata\Service\Mapper;

use ExtractMetadata\Mapper\JsonPointer;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class JsonPointerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        $entityManager = $services->get('Omeka\EntityManager');
        return new JsonPointer($config['extract_metadata_json_pointer_crosswalk'], $entityManager);
    }
}
