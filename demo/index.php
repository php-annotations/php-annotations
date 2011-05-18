<?php

// This example isn't by any means finished, and not yet properly documented...

set_include_path(
  dirname(__FILE__)
  .PATH_SEPARATOR . dirname(dirname(__FILE__)) . '/lib'
  .PATH_SEPARATOR . dirname(dirname(__FILE__)) . '/annotations'
);

spl_autoload_register(
  function($name)
    { require $name.'.php'; },
  true, // throw exceptions on error
  true  // prepend autoloader
);

Annotations::$config['cachePath'] = dirname(__FILE__) . '/runtime';

class Person
{
  /**
   * @var string
   * @length(50)
   * @text('label' => 'Full Name')
   */
  public $name;
  
  /**
   * @var string
   * @length(50)
   * @text('label' => 'Street Address')
   */
  public $address;
  
  /**
   * @var string
   */
  public $age;
}

abstract class Widget
{
  protected $object;
  protected $property;
  protected $value;
  
  public $errors = array();
  
  public function __construct($object, $property)
  {
    $this->object = $object;
    $this->property = $property;
    $this->value = $object->$property;
  }
  
  public function addError($property, $message)
  {
    $this->errors[] = $message;
  }
  
  protected function getMetadata($type, $name, $default=null)
  {
    $a = Annotations::ofProperty($this->object, $this->property, $type);
    
    if (!count($a))
      return $default;
    
    return $a[0]->$name;
  }
  
  abstract public function update($input);
  
  abstract public function display();
  
  public function getLabel()
  {
    return $this->getMetadata('TextAnnotation', 'label', ucfirst($this->property));
  }
}

class StringWidget extends Widget
{
  public function update($input)
  {
    $min = $this->getMetadata('LengthAnnotation', 'min', 0);
    $max = $this->getMetadata('LengthAnnotation', 'max', 255);
    
    if (strlen($input) < $min)
      $this->addError("Minimum length is {$min} characters");
    else if (strlen($input) > $max)
      $this->addError("Maximum length is {$max} characters");
    
    $this->value = $input;
  }
  
  public function display()
  {
    $length = $this->getMetadata('LengthAnnotation', 'max', 255);
    echo '<input type="text" name="' . get_class($this->object) . '[' . $this->property . ']" maxlength="' . $length . '" value="' . htmlspecialchars($this->value) . '"/>';
  }
}

class Form
{
  private $object;
  
  private $widgets = array();
  
  private function getMetadata($property, $type, $name, $default=null)
  {
    $a = Annotations::ofProperty(get_class($this->object), $property, $type);
    
    if (!count($a))
      return $default;
    
    return $a[0]->$name;
  }
  
  public function __construct($object)
  {
    $this->object = $object;

    $class = new ReflectionClass($this->object);
    
    foreach ($class->getProperties() as $property)
    {
      $type = $this->getMetadata($property->name, 'VarAnnotation', 'type', 'text');
      
      $wtype = ucfirst($type).'Widget';
      
      $this->widgets[] = new $wtype($this->object, $property->name);
    }
  }
  
  public function display()
  {
    echo '<form method="post">';
    
    foreach ($this->widgets as $widget)
    {
      echo '<label>' . htmlspecialchars($widget->getLabel()) . '<br/>';
      $widget->display();
      echo '</label><br/>';
    }
    
    echo '</form method="post">';
  }
}

$person = new Person;

$form = new Form($person);

$form->display();
