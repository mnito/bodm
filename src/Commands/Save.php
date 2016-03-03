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

use BODM\Base\Model;
use MongoDB\Collection;
use SplStack;
use BODM\ActiveModel;

class Save extends Command
{
    protected $commands;
    protected $active = true;
    
    public function __construct(Model $model, Collection $collection = null)
    {
        $this->commands = new SplStack();
        $this->result = [];
        parent::__construct($model, $collection);
    }
    
    public function makeCommand(Model $model)
    {
        if(!$model->attributeExists('_id')) {
           $command = new Insert($model);
        } elseif($model->isModified()) {
            $command = new Update($model);
        } else {
            return null;
        }
        if($model instanceof ActiveModel) {
            $command->setCollection($model->getCollection());
        } else {
            $this->active = false;
        }
        return $command;
    }
    
    public function addCommand(Command $command)
    {
        $this->commands->push($command);
    }
    
    public function initiate()
    {
        $this->processModel($this->model);
    }
    
    public function processModel(Model $model)
    {
        $command = $this->makeCommand($model);
        if(!($command === null)) {
            $this->addCommand($command);
        }
        $referenceList = $model->getReferenceList();
        foreach($referenceList as $name) {
            if($model->attributeExists($name)) {
                $reference = $model->getRawAttribute($name);
                $this->processReference($reference);
            }
        }
    }
    
    public function processReference($reference)
    {
        if($reference instanceof Model) {
            $this->processModel($reference);
        } elseif(is_array($reference)) {
            foreach($reference as $element) {
                $this->processModel($element);
            }
        }
    }
    
    public function execute(): bool
    {
        $this->initiate();
        $ret = true;
        $commands = $this->commands;
        while(!$commands->isEmpty()) {
            $command = $commands->pop();
            if(!$command->hasCollection()) {
                $ret = false;
            }
            $ret = $ret && $command->execute();
            $this->result[] = $command->getResult();
        }
        return $ret;
    }
}
