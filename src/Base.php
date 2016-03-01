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

use MongoDB\BSON\Persistable;
use MongoDB\BSON\Binary;
use JsonSerializable;
use IteratorAggregate;
use stdClass;
use ArrayIterator;
use Iterator;

class Base implements Persistable, JsonSerializable, IteratorAggregate
{
    protected $attributes = [];
    protected $original = [];
    protected $updates = [];
    protected $fullDebug = false;
    
    public function __construct(array $attributes = [])
    {
        if(!empty($attributes)) {
            $this->fill($attributes);
        }
    }
    
    public function fill(array $attributes)
    {
        if(empty($this->attributes)) {
            $this->attributes = $attributes;
            $this->original = $attributes;
            return;
        }
        $this->fillMerge($attributes);
    }
    
    protected function fillMerge(array $attributes)
    {
        $this->updates = array_merge($this->updates, array_keys($attributes));
        $this->attributes = array_merge($this->attributes, $attributes);
    }
    
    public function bsonSerialize(): array
    {
        return self::add__pclass($this->getRawAttributes());
    }
    
    final public function bsonUnserialize(array $data)
    {
        $this->__construct($data);
    }
    
    final public function attributeExists(string $name): bool
    {
        return array_key_exists($name, $this->getRawAttributes());
    }
    
    final public function __isset(string $name): bool
    {
        return $this->attributeExists($name);
    }

    final public function setRawAttribute(string $name, $value, bool $update = true)
    {
        if($update) {
            $this->addUpdate($name);
        }
        $this->attributes[$name] = $value;
    }
    
    public function setAttribute(string $name, $value)
    {
        $this->addUpdate($name);
        if(method_exists($this, $setter = 'set'.ucfirst($name).'Attribute')) {
            return $this->$setter($value);
        }
        $this->setRawAttribute($name, $value, false);
    }
    
    final public function __set(string $name, $value)
    {
        return $this->setAttribute($name, $value);
    }
    
    final public function getRawAttribute(string $name)
    {
        if(!$this->attributeExists($name)) {
            return null;
        }
        return $this->attributes[$name];
    }
    
    public function getRawAttributes(): array
    {
        return $this->attributes;
    }
    
    public function getAttribute(string $name)
    {
        if(method_exists($this, $getter = 'get'.ucfirst($name).'Attribute')) {
            return $this->$getter();
        }
        return $this->getRawAttribute($name);
    }

    public function getAttributes(): array
    {
        $attributes = [];
        $keys = array_keys($this->getRawAttributes());
        foreach($keys as $name) {
            $attributes[$name] = $this->getAttribute($name);
        }
        return $attributes;
    }

    final public function __get(string $name)
    {
        return $this->getAttribute($name);
    }
    
    public function deleteAttribute($name)
    {
        unset($this->attributes[$name]);
    }
    
    final public function __unset(string $name)
    {
        $this->deleteAttribute($name);
    }
    
    public function clearUpdates()
    {
        $this->updates = [];
    }
    
    public function clearAttributes()
    {
        $this->attributes = [];
    }
    
    public function clearOriginal()
    {
        $this->original = [];
    }
    
    public function clearAll()
    {
        $this->clearOriginal();
        $this->clearUpdates();
        $this->clearAttributes();
    }

    protected function addUpdate($name)
    {
        $this->updates[] = $name;
    }
    
    public function isModified(): bool
    {
        return !empty($this->getUpdates());
    }
    
    public function isAttributeModified(string $name): bool
    {
        if(($attribute = $this->getRawAttribute($name)) instanceof self) {
            return $attribute->isModified();
        }
        return in_array($attribute, $this->updates);
    }
    
    public function revertToOriginal()
    {
        $this->attributes = $this->original;
    }
    
    protected function attributesToOriginal()
    {
        $this->original = $this->attributes;
    }

    public function getUpdatesList(): array
    {
        return array_unique($this->updates);
    }
    
    public function getUpdates(): array
    {
        $list = $this->getUpdatesList();
        $updates = [];
        foreach($list as $element) {
            $updates[$element] = $this->getRawAttribute($element);
        }
        return $updates;
    }
    
    final protected static function add__pclass(array $attributes)
    {
        $binData = new Binary(static::class, 0x80);
        $attributes['__pclass'] = $binData;
        return $attributes;
    }
    
    protected static function unmagic(array $attributes): array
    {
        if(isset($attributes['_id']) && is_object($id = $attributes['_id']) && method_exists($id, '__toString')) {
            $attributes['_id'] = $id->__toString();
        }
        unset($attributes['__pclass']);
        return $attributes;
    }
    
    public function getIterator(): Iterator
    {
        return new ArrayIterator(static::unmagic($this->getAttributes()));
    }
    
    public function toArray() : array
    {
        return json_decode(json_encode($this->getAttributes()), true);
    }
    
    public function toStdClass(): stdClass
    {
        return json_decode(json_encode($this->getAttributes()));
    }
    
    final public function __toString(): string
    {
        return $this->toJson();
    }
    
    public function jsonSerialize(): array
    {
        return static::unmagic($this->getAttributes());
    }
    
    public function toJson(): string
    {
        return json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
    }
    
    public function switchFullDebug()
    {
        $this->fullDebug = !$this->fullDebug;
    }
    
    public function __debugInfo(): array
    {
        if($this->fullDebug) {
            return get_object_vars($this);
        }
        return static::unmagic($this->getAttributes());
    }

    public function getClass(): string
    {
        return static::class;
    }
    
    public function getAttributesList(): array
    {
        return array_keys($this->getRawAttributes());
    }
}
