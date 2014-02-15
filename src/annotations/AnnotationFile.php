<?php

/**
 * This file is part of the php-annotation framework.
 *
 * (c) Rasmus Schultz <rasmus@mindplay.dk>
 *
 * This software is licensed under the GNU LGPL license
 * for more information, please see:
 *
 * <https://github.com/mindplay-dk/php-annotations>
 */

namespace mindplay\annotations;

/**
 * This class represents a php source-code file inspected for Annotations.
 */
class AnnotationFile
{
    /**
     * @var array hash where member name => annotation data
     */
    public $data;

    /**
     * @var string $path absolute path to php source-file
     */
    public $path;

    /**
     * @var string $namespace fully qualified namespace
     */
    public $namespace;

    /**
     * @var string[] $uses hash where local class-name => fully qualified class-name
     */
    public $uses;

    /**
     * @param string $path absolute path to php source-file
     * @param array $data annotation data (as provided by AnnotationParser)
     */
    public function __construct($path, array $data)
    {
        $this->path = $path;
        $this->data = $data;
        $this->namespace = $data['#namespace'];
        $this->uses = $data['#uses'];
    }

    /**
     * Transforms not fully qualified class/interface name into fully qualified one.
     *
     * @param string $raw_type Raw type.
     *
     * @return string
     * @see http://www.phpdoc.org/docs/latest/for-users/phpdoc/types.html#abnf
     */
    public function resolveType($raw_type)
    {
        $type_parts = explode('[]', $raw_type, 2);
        $type = $type_parts[0];

        if (!$this->isSimple($type)) {
            if (isset($this->uses[$type])) {
                $type_parts[0] = $this->uses[$type];
            } elseif ($this->namespace && substr($type, 0, 1) != '\\') {
                $type_parts[0] = $this->namespace . '\\' . $type;
            }
        }

        return implode('[]', $type_parts);
    }

    /**
     * Determines if given data type is scalar.
     *
     * @param string $type Type.
     *
     * @return boolean
     */
    protected function isSimple($type)
    {
        $data_types = array(
            'bool', 'int',
            'mixed', 'string', 'boolean', 'integer', 'float', 'double', 'array',
        );

        return in_array(strtolower($type), $data_types);
    }
}
