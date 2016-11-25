<?php

namespace App;

class BaseModel
{

    public $properties;

    public function __construct() {
        $this->properties = array();
    }

    public function __set($propertyName, $value) {
        $this->properties[$propertyName] = $value;
    }

    public function __get($propertyName) {
        if (isset($this->properties[$propertyName])) {
            return $this->properties[$propertyName];
        } else {
            return null;
        }
    }
}
