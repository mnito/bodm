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

namespace BODM\Reference;

trait ReferenceTrait 
{
    protected $refClass;
    
    public function fill(array $attributes)
    {
        if(array_key_exists('__refClass', $attributes)) {
            $this->refClass = $attributes['__refClass'];
            unset($attributes['__refClass']);
        }
        parent::fill($attributes);
    }
    
    public function setReferenceClass(string $class)
    {
        $this->refClass = $class;
    }
    
    public function bsonSerialize(): array
    {
        $attributes = parent::bsonSerialize();
        $attributes['__refClass'] = !empty($this->refClass) ? $this->refClass : static::class;
        return $attributes;
    }
}
