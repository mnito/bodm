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

namespace BODM\Commands;

use BODM\Model;
use MongoDB\Collection;

abstract class Command implements Executable
{
    protected $model;
    protected $collection;
    protected $result;
    
    public function __construct(Model $model, Collection $collection = null)
    {
        $this->model = $model;
        $this->collection = $collection;
    }

    public function setCollection(Collection $collection)
    {
        $this->collection = $collection;
    }
    
    public function hasCollection()
    {
        return $this->collection !== null;
    }
    
    public function getModel()
    {
        return $this->model;
    }
    
    public function getResult()
    {
        return $this->result;
    }
    
    abstract public function execute(): bool;
}
