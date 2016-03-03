<?php

/*
 * Copyright 2016 Michael P. Nitowski
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

namespace BODM;

use BODM\Reference\Reference;
use BODM\Reference\BaseReference;

class Model extends Base
{
    protected $references = [];
    protected $compressed = [];
    protected $referenceObjects = [];
    
    public function getKey(): string
    {
        return static::getClassKey();
    }
    
    public function getDefaultKey(Base $object = null): string
    {
        return static::getClassKey();
    }
    
    public static function getClassKey(): string
    {
        $class = static::class;
        $pos = strrpos($class, '\\');
        if ($pos) {
            $class = substr($class, $pos + 1);
        }
        return strtolower($class);
    }

    public function bsonSerialize(): array
    {
        $attributes = $this->getAttributesWithReferences();
        return self::add__pclass($attributes);
    }
    
    protected function getDefaultReference(): Reference
    {
        return new BaseReference();
    }

    private function tryParseReference(string $key, $value, string &$refKey, &$refVal): bool
    {
        if(!is_numeric($key)) {
            $refKey = $key;
            $refVal = $value instanceof Reference ? $value : $this->getDefaultReference();
            return true;
        } elseif(is_string($value) && !is_numeric($value)) {
            $refKey = $value;
            $refVal = $this->getDefaultReference();
            return true;
        }
        $refKey = uniqid();
        $refVal = null;
        return false;
    }
    
    protected function parseReferences(): array
    {
        $references = $this->references;
        $referenceObjs = [];
        foreach($references as $key=>$value) {
            $refKey = '';
            $refVal = null;
            if(!$this->tryParseReference($key, $value, $refKey, $refVal)) {
                continue;
            }
            $referenceObjs[$refKey] = $refVal; 
        }
        return $referenceObjs;
    }
    
    public function getReferenceObjects(): array
    {
        if(!empty($this->referenceObjects)) {
            return $this->referenceObjects;
        }
        $references = $this->parseReferences();
        $this->referenceObjects = $references;
        return $references;
    }
    
    public function clearReferenceObjects()
    {
        $this->referenceObjects = [];
    }
    
    public function hasReferences()
    {
        return !empty($this->references);
    }
    
    final public function getReferenceList(): array
    {
        return array_keys($this->getReferenceObjects());
    }
    
    public function getReferenceObject($name)
    {
        $references = $this->getReferenceObjects();
        if(array_key_exists($name, $references)) {
            return $references[$name];
        }
        return $this->getDefaultReference();
    }
    
    public function getAttributesWithReferences(): array
    {
        $referenceList= $this->getReferenceList();
        $attributes = $this->getRawAttributes();
        foreach($referenceList as $name) {
            if(array_key_exists($name, $attributes)) {
                $attributes[$name] = $this->compress($attributes[$name]);
            }
        }
        return $attributes;
    }
    
    protected function modifyReference(Reference $reference, Base $attribute)
    {
        $reference->setReferenceClass($attribute->getClass());
    }
    
    public function compress($object)
    {
        if($object instanceof self) {
            return $this->compressSingle($object);
        } elseif(is_array($object)) {
            return $this->compressArray($object);
        }
        return $object;
    }
    
    public function compressSingle(self $object): Base
    {
        $reference = $this->compressSingleShallow($object);
        if(!$object->hasReferences()) {
            return $reference;
        }
        $list = array_intersect($object->getReferenceList(), $object->getCompressedAttributesList($this->getDefaultKey()));
        foreach($list as $name) {
            $attribute = $object->getRawAttribute($name);
            if($attribute === null) {
                continue;
            }
            $reference->setRawAttribute($name, $object->compress($attribute));
        }
        return $reference;
    }
    
    protected function compressSingleShallow(self $object): Base
    {
        $reference = $this->getReferenceObject($object->getDefaultKey($this));
        $reference->fill($object->getCompressedAttributes($this->getDefaultKey()));
        $this->modifyReference($reference, $object);
        return $reference;
    }
    
    public function compressArray(array $objects): array
    {
        foreach($objects as &$object) {
            $object = $this->compress($object);
        }
        return $objects;
    }
  
    private static function filterOutSpecific(array $list): array
    {
        foreach($list as $key=>$value) {
            if((!is_numeric($key) || !is_string($value))) {
                unset($list[$key]);
            }
        }
        return $list;
    }
    
    private function inject_id(array &$list)
    {
        array_push($list, '_id');
    }
    
    private static function ensureArray($value): array
    {
        if (!is_array($value)) {
            $value = (array) $value;
        }
        return $value;
    }
    
    private static function unsetNotAttributes(array $notList, array &$filteredList)
    {
        foreach($notList as $value) {
            $flippedList = array_flip($filteredList);
            if(array_key_exists($value, $flippedList)) {
                unset($filteredList[$flippedList[$value]]);
            }
        }
    }
    
    public function getCompressedAttributesList($classKey = ''): array
    {
        $attributesList = $this->getAttributesList();
        $compressed = $this->compressed;
        self::inject_id($compressed);
        $generalList = self::filterOutSpecific($compressed);
        if($classKey == '' || !array_key_exists($classKey, $compressed)) {
            return array_intersect($attributesList, $generalList);
        }
        $specificList = static::ensureArray($compressed[$classKey]);
        if(array_key_exists('__not', $specificList)) {
            $notList = static::ensureArray($specificList['__not']);
            self::unsetNotAttributes($notList, $generalList);
            unset($specificList['__not']);
        }
        return array_intersect($attributesList, array_merge($generalList, $specificList));
    }

    final public function getCompressedAttributes($classKey = ''): array
    {
        $attributes = $this->getRawAttributes();
        $list = $this->getCompressedAttributesList($classKey);
        return array_intersect_key($attributes, array_flip($list));
    }
    
    public function getUpdates(): array
    {
        $updates = parent::getUpdates();
        $references = $this->getReferenceList();
        $referenceUpdates = [];
        foreach($references as $name) {
            $attribute = $this->getRawAttribute($name);
            $referenceUpdates = $this->getNewReferenceUpdates($name, $attribute, $referenceUpdates);
        }
        return array_merge($updates, $referenceUpdates);
    }
    
    private function getNewReferenceUpdates($name, $attribute, $referenceUpdates, $full = false)
    {
        if($attribute instanceof self) {
            $compressedList = array_flip($this->compress($attribute)->getAttributesList());
            $referenceUpdates = array_merge(
                Helper::prefixKeys($this->filter($attribute->getUpdates(), $compressedList), $name.'.'), 
                $referenceUpdates
            );
        } elseif(is_array($attribute)) {
            $i = 0;
            foreach($attribute as $element) {
                $referenceUpdates = $this->getNewReferenceUpdates($name.'.'.$i, $element, $referenceUpdates);
                $i++;
            }
        }
        return $referenceUpdates;
    }
    
    private function filter(array $updates, $compressedList)
    {
        $separator = '.';
        foreach($updates as $key=>$update) {
            if(!array_key_exists($key, $compressedList) && (bool) strpos($key, $separator) === false) {
                unset($updates[$key]);
            }
        }
        return $updates;
    }
}
