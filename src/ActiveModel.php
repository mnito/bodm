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

use MongoDB\Driver\Manager;
use MongoDB\Collection;
use MongoDB\BSON\ObjectID;
use Exception;
use Traversable;

use BODM\Reference\ActiveReference;
use BODM\Reference\Reference;
use BODM\Commands\Save;
use BODM\Exception\ExpansionException;

class ActiveModel extends Model
{   
    protected static $defaultDb = 'test';
    protected static $manager;
    protected static $registry = [];
    
    protected $namespace = '';
    protected $embedded = false;
    protected $autoExpand = true;
    protected $graceful = true;
    protected $collection;
    protected $lastResult;
    
    public function __construct(array $attributes = array())
    {
        $this->load();
        parent::__construct($attributes);
    }

    protected function load()
    {
        if($this->embedded === true) {
            return;
        }
        if($this->namespace === '') {
            $this->namespace = static::$defaultDb.'.'.static::getClassKey();
        }
        if(!array_key_exists(static::class, static::$registry)) {
            static::$registry[static::class] = [];
        }
        static::loadManager();
        static::loadCollection();
    }
    
    protected static function loadManager()
    {
        if(empty(static::$manager)) {
            $manager = new Manager("mongodb://localhost:27017");
            static::$manager = $manager;
        }
    }
    
    protected function loadCollection()
    {
        if(empty($this->collection)) {
            $collection = new Collection(static::$manager, $this->namespace);
            $this->collection = $collection;
        }
    }
    
    public function getCollection()
    {
        return $this->collection;
    }
    
    protected static function register(self $object)
    {
        if(isset($object->_id) && !empty($id = $object->_id)) {
            static::$registry[static::class][(string)$id] = $object;
        }
    }
    
    protected static function existsInRegistry($id): bool
    {
        return array_key_exists((string)$id, static::$registry[static::class]);
    }
    
    protected static function getFromRegistry($id): self
    {
        return static::$registry[static::class][(string)$id];
    }
    
    public function getDefaultReference(): Reference
    {
        return new ActiveReference();
    }
    
    public static function isActiveReference($value): bool
    {
        return $value instanceof ActiveReference;
    }
    
    public function insert(): bool
    {
        if($this->attributeExists('_id')) {
            return false;
        }
        return $this->save();
    }
    
    public function update(): bool
    {
        if(!$this->attributeExists('_id')) {
            return false;
        }
        return $this->save();
    }
    
    public function save(): bool
    {
        $save = new Save($this, $this->collection);
        $ret = $save->execute();
        $this->lastResult = $save->getResult();
        return $ret;
    }
    
    private static function tryAutoExpand(&$attribute): bool
    {
        if(static::isActiveReference($attribute)) {
            $attribute = $attribute->expand();
            return true;
        } elseif(is_array($attribute)) {
            $result = true;
            foreach($attribute as &$element) {
                $result = $result && self::tryAutoExpand($element);
            }
            return $result;
        }
        return false;
    }
    
    private static function tryAutoExpandAll($references, &$attributes): int
    {
        $expanded = 0;
        foreach($references as $name) {
            if(array_key_exists($name, $attributes)) {
                $attribute = $attributes[$name];
                if(self::tryAutoExpand($attribute)) {
                    $attributes[$name] = $attribute;
                    $expanded += 1;
                }
            }
        }
        return $expanded;
    }
    
    public function getAttribute(string $name)
    {
        $attribute = parent::getAttribute($name);
        if($this->autoExpand && self::tryAutoExpand($attribute)) {
            $this->setRawAttribute($name, $attribute, false);
        }
        return $attribute;
    }
    
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $references = $this->getReferenceList();
        if($this->autoExpand) {
            $this->tryAutoExpandAll($references, $attributes);
        }
        return $attributes;
    }

    private static function ensureObjectId($id)
    {
        if(!($id instanceof ObjectID)) {
            $id = new ObjectID((string) $id);
        }
        return $id;
    }

    public static function findOneById($id)
    {
        $instance = new static();
        if(empty($id)) {
            return false;
        }
        $id = static::ensureObjectId($id);
        if(static::existsInRegistry($id)) {
            return static::getFromRegistry($id);
        }
        $result = $instance->findOne(['_id' => $id]);
        return $result !== false ? $result : false;
    }
    
    public function findOne($filter = [], $options = [])
    {
        $result = $this->collection->find($filter, $options)->toArray();
        if(array_key_exists(0, $result)) {
            static::register($result[0]);
            return $result[0];
        }
        return false;
    }
    
    public function find($filter = [], $options = []): Traversable
    {
        $result = $this->collection->find($filter, $options);
        return $result;
    }
    
    protected function getGenerator()
    {
        $iterator = parent::getIterator();
        foreach($iterator as $key=>$value) {
            if($this->autoExpand && static::isActiveReference($value)) {
                yield $key => $value->expand();
            } else {
                yield $key => $value;
            }
        }
    }
    
    public function getIterator()
    {
        return $this->getGenerator();
    }
    
    public function setAutoExpand(bool $bool)
    {
        $this->autoExpand = $bool;
    }
    
    public function expand(): Base
    {
        if(!$this->graceful) {
           throw new ExpansionException('Object cannot be expanded anymore.');
        }
        return $this;
    }

    public function getLastResult()
    {
        return $this->lastResult;
    }
}
