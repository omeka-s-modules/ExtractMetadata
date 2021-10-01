<?php
namespace ExtractMetadata\Mapper;

use Doctrine\Common\Collections\Criteria;
use Omeka\Entity;
use Rs\Json\Pointer;

/**
 * Map metadata to media/item values using JSON pointer.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6901 For format specification
 * @see https://github.com/raphaelstolt/php-jsonpointer For PHP implementation
 */
class JsonPointer implements MapperInterface
{
    protected $crosswalk;

    protected $entityManager;

    public function __construct($crosswalk, $entityManager)
    {
        $this->crosswalk = $crosswalk;
        $this->entityManager = $entityManager;
    }

    public function map(Entity\Media $mediaEntity, array $metadataEntities)
    {
        $itemEntity = $mediaEntity->getItem();
        $mediaValues = $mediaEntity->getValues();
        $itemValues = $itemEntity->getValues();
        $propertiesToClear = ['media' => [], 'item' =>[]];
        $valuesToAdd = ['media' => [], 'item' => []];
        foreach ($this->crosswalk as $map) {
            if (!isset($map['resource'], $map['extractor'], $map['pointer'], $map['term'], $map['replace'])) {
                // All keys are required.
                continue;
            }
            if (!in_array($map['resource'], ['media', 'item'])) {
                // This resource is invalid.
                continue;
            }
            $metadataEntity = current(array_filter($metadataEntities, function ($metadataEntity) use ($map) {
                return $map['extractor'] === $metadataEntity->getExtractor();
            }));
            if (!$metadataEntity) {
                // This extractor has no metadata entity.
                continue;
            }
            $property = $this->getPropertyByTerm($map['term']);
            if (!$property) {
                // This term has no property.
                continue;
            }
            try {
                $jsonPointer = new Pointer(json_encode($metadataEntity->getMetadata()));
                $valueString = $jsonPointer->get($map['pointer']);
            } catch (\Exception $e) {
                // Invalid JSON, invalid pointer, or nonexistent value.
                continue;
            }
            if (!is_string($valueString)) {
                // The pointer did not resolve to a string.
                continue;
            }
            $value = new Entity\Value;
            $value->setType('literal');
            $value->setProperty($property);
            $value->setValue($valueString);
            $value->setIsPublic(true);
            if ('media' === $map['resource']) {
                $value->setResource($mediaEntity);
                $valuesToAdd['media'][] = $value;
                if ($map['replace']) {
                    $propertiesToClear['media'][] = $property;
                }
            }
            if ('item' === $map['resource']) {
                $value->setResource($itemEntity);
                $valuesToAdd['item'][] = $value;
                if ($map['replace']) {
                    $propertiesToClear['item'][] = $property;
                }
            }
        }
        // Remove values.
        foreach ($propertiesToClear['media'] as $property) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('property', $property));
            foreach ($mediaValues->matching($criteria) as $mediaValue) {
                $mediaValues->removeElement($mediaValue);
            }
        }
        foreach ($propertiesToClear['item'] as $property) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('property', $property));
            foreach ($itemValues->matching($criteria) as $itemValue) {
                $itemValues->removeElement($itemValue);
            }
        }
        // Add values.
        foreach ($valuesToAdd['media'] as $value) {
            $mediaValues->add($value);
        }
        foreach ($valuesToAdd['item'] as $value) {
            $itemValues->add($value);
        }
    }

    /**
     * Get property by term.
     *
     * @param string $term vocabularyPrefix:propertyLocalName
     * @return null|Entity\Property
     */
    public function getPropertyByTerm($term)
    {
        list($prefix, $localName) = array_pad(explode(':', $term), 2, null);
        $dql = '
            SELECT p
            FROM Omeka\Entity\Property p
            JOIN p.vocabulary v
            WHERE p.localName = :localName
            AND v.prefix = :prefix';
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters([
            'localName' => $localName,
            'prefix' => $prefix,
        ]);
        return $query->getOneOrNullResult();
    }
}
