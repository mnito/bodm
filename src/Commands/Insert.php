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

use Exception;

class Insert extends Command
{
    protected function insert(): bool
    {
        if(!empty($this->model->_id)) {
            return false;
        }
        try {
            $result = $this->collection->insertOne($this->model);
            $this->result = $result;
            $this->model->_id = $result->getInsertedId();
        } catch (Exception $ex) {
            $this->result = $ex;
            return false;
        }
        return true;
    }
    
    final public function execute(): bool
    {
        return $this->insert();
    }
}
