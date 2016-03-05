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

use BODM\Base\Model;
use BODM\Base\Base;
use BODM\config\config;
use MongoDB\Driver\Manager;
use MongoDB\Collection;
use MongoDB\BSON\ObjectID;
use Exception;
use Traversable;
use BODM\Reference\ActiveReference;
use BODM\Reference\Reference;
use BODM\Commands\Save;
use BODM\Commands\Remove;
use BODM\Exception\ExpansionException;

class ActiveModel extends Model
{   
    const DEFAULT_DB = config::DEFAULT_DB;
    
    protected static $manager;
    protected static $registry = [];
    
    protected $dbName;
    protected $collectionName;
    protected $embedded = false;
    protected $autoExpand = false;
    protected $graceful = true;
    protected $collection;
    protected $lastResult;
    
    final public function __construct(array $attributes = array())
    {
        $this->load();
        parent::__construct($attributes);
    }
    
    protected function load()
    {
        if($this->embedded === true) {
            return;
        }
        if($this->dbName === null) {
            $this->dbName = static::DEFAULT_DB;
        }
        if($this->collectionName === null) {
            $this->collectionName = static::getClassKey();
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
            $collection = new Collection(static::$manager, $this->dbName, $this->collectionName);
            $this->collection = $collection;
        }
    }
    
    public function bsonUnserialize(array $data)
    {
        $this->load();
        parent::bsonUnserialize($data);
    }
    
    public function getCollection(): Collection
    {
        return $this->collection;
    }
    
    public static function construct(array $attributes = array())
    {
        return new static($attributes);
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
    
    public function remove(): bool
    {
        $remove = new Remove($this, $this->collection);
        $ret = $remove->execute();
        $this->lastResult = $remove->getResult();
        return $ret;
    }
    
    private static function tryExpand(&$attribute): bool
    {
        if(static::isActiveReference($attribute)) {
            $attribute = $attribute->expand();
            return true;
        } elseif(is_array($attribute)) {
            $result = true;
            foreach($attribute as &$element) {
                $result = $result && self::tryExpand($element);
            }
            return $result;
        } elseif($attribute instanceof Traversable) {
            $objects = static::convertToArray($attribute);
            return self::tryExpand($objects);
        }
        return false;
    }
    
    private static function tryExpandAll(array $references, array &$attributes): array
    {
        $expanded = [];
        foreach($references as $name) {
            if(array_key_exists($name, $attributes)) {
                $attribute = $attributes[$name];
                if(self::tryExpand($attribute)) {
                    $attributes[$name] = $attribute;
                    $expanded[$name] = $attribute;
                }
            }
        }
        return $expanded;
    }
    
    public function getAttribute(string $name, $expand = false)
    {
        $attribute = parent::getAttribute($name);
        if(($this->autoExpand || $expand) && self::tryExpand($attribute)) {
            $this->setRawAttribute($name, $attribute, false);
        }
        return $attribute;
    }
    
    public function getAttributes($expand = false): array
    {
        $attributes = parent::getAttributes();
        $references = $this->getReferenceList();
        if($this->autoExpand || $expand) {
            $expanded = $this->tryExpandAll($references, $attributes);
            foreach($expanded as $name=>$value) {
                $this->setRawAttribute($name, $value, false);
            }
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
        if(empty($id)) {
            return false;
        }
        $id = static::ensureObjectId($id);
        if(static::existsInRegistry($id)) {
            return static::getFromRegistry($id);
        }
        $result = static::findOne(['_id' => $id]);
        return $result !== null ? $result : false;
    }
    
    public static function findOne($filter = [], $options = [])
    {
        $instance = new static();
        return $instance->getCollection()->findOne($filter, $options);
    }
    
    public static function find($filter = [], $options = []): Traversable
    {
        $instance = new static();
        $result = $instance->getCollection()->find($filter, $options);
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
