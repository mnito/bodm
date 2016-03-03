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

use BODM\ActiveModel;
use BODM\Base\Base;

class ActiveReference extends ActiveModel implements Reference
{
    use ReferenceTrait;
    
    public function expand(): Base
    {
        $id = $this->_id;
        $class = $this->refClass;
        if($class === static::class || empty($class) || ($ref = $class::findOneById($id)) === false) {
            return $this;
        }
        self::register($ref);
        return $ref;
    }
}
