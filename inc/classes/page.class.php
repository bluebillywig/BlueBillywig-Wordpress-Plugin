<?php
namespace BlueBillywig\Classes;

use QL\UriTemplate\Exception;

class Page
{
    private $id;
    private $name;
    
    abstract $scriptDependencies = array();

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function __get($name)
    {
        if(property_exists($this, $name))
        {
            return $name;
        }
        throw new Exception('Unkown property ' . $name);
    }

    public function render_page()
    {
        
    }
}

?>