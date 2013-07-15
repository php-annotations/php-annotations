<?php

/*
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
 * This class represents a context information discovered during inspection of Annotations.
 */
class AnnotationContext
{
    /**
     * @param AnnotationFile $file Information about file, where annotation was parsed from.
     */
    public function __construct(AnnotationFile $file)
    {
        $this->file = $file;
    }

    /**
     * @var AnnotationFile
     */
    public $file;

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
            if (isset($this->file->uses[$type])) {
                $type_parts[0] = $this->file->uses[$type];
            }
            elseif ($this->file->namespace && substr($type, 0, 1) != '\\') {
                $type_parts[0] = $this->file->namespace . '\\' . $type;
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
