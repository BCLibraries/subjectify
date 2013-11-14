<?php

namespace BCLib\Subjectify;

/**
 * Class Topic
 * @package BCLib\Subjectify
 *
 * @property string $id
 * @property string $label
 * @property Topic  $parent
 */
class Topic
{
    protected $_id;
    protected $_label;

    /** @var  Topic */
    protected $_parent;

    public function __get($name)
    {
        switch ($name) {
            case 'id':
            case 'label':
            case 'parent':
                $property = "_$name";
                return $this->$property;
            default:
                throw new \Exception($name . " is not a property of Topic");
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'id':
            case 'label':
            case 'parent':
                $property = "_$name";
                $this->$property = $value;
                break;
            default:
                throw new \Exception($name . " is not a property of Topic");
        }
    }

    public function __isset($name)
    {
        $property = "_$name";
        return isset($this->$property);
    }
}