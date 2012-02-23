<?php

/*
 * This file is part of the php-annotation framework.
 *
 * (c) Rasmus Schultz <rasmus@mindplay.dk>
 *
 * This software is licensed under the GNU LGPL license
 * for more information, please see:
 *
 * <http://code.google.com/p/php-annotations>
 */

namespace Mindplay\Annotation;

/**
 * This class implements a parser for source code annotations
 */
class AnnotationParser
{
  const CHAR = -1;
  const SCAN = 1;
  const CLASS_NAME = 2;
  const SCAN_CLASS = 3;
  const MEMBER = 4;
  const METHOD_NAME = 5;
  const NAMESPACE_NAME = 6;

  const SKIP = 7;
  const NAME = 8;
  const COPY_LINE = 9;
  const COPY_ARRAY = 10;

  /**
   * @var boolean $debug Set to TRUE to enable HTML output for debugging
   */
  public $debug = false;

  /**
   * @var boolean Enable PHP autoloader when searching for annotation classes (defaults to true)
   */
  public $autoload = true;

  /**
   * @var AnnotationManager Internal reference to the AnnotationManager associated with this parser.
   */
  protected $manager;

  /**
   * Creates a new instance of the annotation parser.
   *
   * @param AnnotationManager The annotation manager associated with this parser.
   */
  public function __construct(AnnotationManager $manager)
  {
    $this->manager = $manager;
  }

  /**
   * @param string $source The PHP source code to be parsed
   * @param string $path The path of the source file being parsed (used only for error-reporting)
   * @return string PHP source code to construct the annotations of the given PHP source code
   */
  public function parse($source, $path)
  {
    $index = array();

    $annotations = array();
    $state = self::SCAN;
    $nesting = 0;
    $class = null;
    $namespace = '';

    $VISIBILITY = array(T_PUBLIC, T_PRIVATE, T_PROTECTED, T_VAR);

    $line = 0;

    if ($this->debug)
      echo '<table><tr><th>Line</th><th>Type</th><th>String</th><th>State</th><th>Nesting</th></tr>';

    foreach (token_get_all($source) as $token)
    {
      list($type, $str, $line) = is_array($token) ? $token : array(self::CHAR, $token, $line);

      switch ($state)
      {
        case self::SCAN:
          if ($type==T_CLASS)
            $state = self::CLASS_NAME;
          if ($type==T_NAMESPACE)
          {
            $state = self::NAMESPACE_NAME;
            $namespace = '';
          }
        break;

        case self::NAMESPACE_NAME:
          if ($type==T_STRING || $type==T_NS_SEPARATOR)
          {
            $namespace .= $str;
          }
          else if ($str == ';')
          {
            $state = self::SCAN;
            $namespace .= '\\';
          }
        break;

        case self::CLASS_NAME:
          if ($type==T_STRING)
          {
            $class = $namespace.$str;
            $index[$class] = $annotations;
            $annotations = array();
            $state = self::SCAN_CLASS;
          }
        break;

        case self::SCAN_CLASS:
          if (in_array($type, $VISIBILITY))
            $state = self::MEMBER;
          if ($type==T_FUNCTION)
            $state = self::METHOD_NAME;
        break;

        case self::MEMBER:
          if ($type==T_VARIABLE)
          {
            $index[$class.'::'.$str] = $annotations;
            $annotations = array();
            $state = self::SCAN_CLASS;
          }
          if ($type==T_FUNCTION)
            $state = self::METHOD_NAME;
        break;

        case self::METHOD_NAME:
          if ($type==T_STRING)
          {
            $index[$class.'::'.$str] = $annotations;
            $annotations = array();
            $state = self::SCAN_CLASS;
          }
        break;
      }

      if (($state >= self::SCAN_CLASS) && ($type == self::CHAR))
      {
        switch ($str)
        {
          case '{':
            $nesting++;
          break;

          case '}':
            $nesting--;
            if ($nesting==0)
            {
              $class = null;
              $state = self::SCAN;
            }
          break;
        }
      }

      if ($type==T_COMMENT || $type==T_DOC_COMMENT)
      {
        $annotations = array_merge($annotations, $this->findAnnotations($str));
      }

      if ($type==T_CURLY_OPEN)
        $nesting++;

      if ($this->debug)
        echo "<tr><td>{$line}</td><td>".token_name($type)."</td><td>".htmlspecialchars($str)."</td><td>{$state}</td><td>{$nesting}</td></tr>\n";
    }

    if ($this->debug)
      echo '</table>';

    if (count($annotations))
    {
      throw new AnnotationException(__CLASS__."::parse() : unassociated annotation(s) at end of file {$path}: " . implode(",\r", $annotations));
    }

    $code = "return array(\n";
    foreach ($index as $key=>$array)
    {
      if (count($array))
        $code .= "  ".trim(var_export($key,true))." => array(\n    ".implode(",\n    ",$array)."\n  ),\n";
    }
    $code .= ");\n";

    return $code;
  }

  /**
   * @param string $path The full path of a PHP source code file
   * @return string PHP source code to construct the annotations of the given PHP source code
   * @see AttributeParser::parse()
   */
  public function parseFile($path)
  {
    return $this->parse(file_get_contents($path), $path);
  }

  /**
   * Scan a PHP source code comment for annotation data
   * @param string $str PHP comment containing annotations
   * @return array PHP source code snippets with annotation initialization arrays
   */
  protected function findAnnotations($str)
  {
    $str = trim(preg_replace('/^[\/\*\# \t]+/m', '', $str))."\n";
    $str = str_replace("\r\n", "\n", $str);

    $state = self::SCAN;
    $nesting = 0;
    $name = '';
    $value = '';

    $matches = array();

    for ($i=0; $i<strlen($str); $i++)
    {
      $char = substr($str,$i,1);

      switch ($state)
      {
        case self::SCAN:
          if ($char == '@')
          {
            $name = '';
            $value = '';
            $state = self::NAME;
          }
          else if ($char != "\n" && $char != " " && $char != "\t")
            $state = self::SKIP;
          break;

        case self::SKIP:
          if ($char == "\n")
            $state = self::SCAN;
          break;

        case self::NAME:
          if (preg_match('/[a-zA-Z\-\\\\]/', $char))
            $name .= $char;
          else if ($char == ' ')
            $state = self::COPY_LINE;
          else if ($char == '(')
          {
            $nesting++;
            $value = $char;
            $state = self::COPY_ARRAY;
          }
          else if ($char == "\n")
          {
            $matches[] = array($name, null);
            $state = self::SCAN;
          }
          else
            $state = self::SKIP;
          break;

        case self::COPY_LINE:
          if ($char == "\n")
          {
            $matches[] = array($name, $value);
            $state = self::SCAN;
          }
          else
            $value .= $char;
          break;

        case self::COPY_ARRAY:
          if ($char == '(')
            $nesting++;
          if ($char == ')')
            $nesting--;

          $value .= $char;

          if ($nesting == 0)
          {
            $matches[] = array($name, $value);
            $state = self::SCAN;
          }
      }
    }

    $annotations = array();

    foreach ($matches as $match)
    {
      $type = $this->manager->resolveName($match[0]);

      if ($type === false)
        continue;

      if (!class_exists($type, $this->autoload))
        throw new AnnotationException(__CLASS__."::findAnnotations('$str') : the annotation type {$type} does not exist");

      $value = $match[1];

      $quotedType = trim(var_export($type,true));

      if ($value === null)
      {
        # value-less annotation:
        $annotations[] = "array({$quotedType})";
      }
      else if (substr($value,0,1) == '(')
      {
        # array-style annotation:
        $annotations[] = "array({$quotedType}, ".substr($value,1);
      }
      else
      {
        # PHP-DOC-style annotation:
        if (!array_key_exists(__NAMESPACE__ . '\IAnnotationParser', class_implements($type, $this->autoload)))
          throw new AnnotationException(__CLASS__."::findAnnotations() : the {$type} Annotation does not support PHP-DOC style syntax (because it does not implement the ".__NAMESPACE__."\\IAnnotationParser interface)");

        $properties = $type::parseAnnotation($value);

        if (!is_array($properties))
          throw new AnnotationException(__CLASS__."::findAnnotations() : the {$type} Annotation did not parse correctly");

        $array = "array({$quotedType}";
        foreach ($properties as $name => $value)
          $array .= ", '{$name}' => ".trim(var_export($value,true));
        $array .= ")";

        $annotations[] = $array;
      }
    }

    return $annotations;
  }
}
