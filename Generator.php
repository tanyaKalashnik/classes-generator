<?php
namespace ClassesGenerator;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\PropertyGenerator;

class Generator
{
    public $sourceDir = '.';

    public $destinationDir = '.';

    public $namespace = null;

    public $rootClassName = null;

    public $showRequires = false;

    public $mappingClassesPropertyName = 'mappingClasses';

    public $mappingPropertyName = 'propNameMap';

    public $rootClassNameForCollection = null;

    public $rootClassNamespace = null;

    public $rootClassForCollectionNamespace = null;

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        if (isset($params['sourceDir']) && $params['sourceDir']) {
            $this->sourceDir = $params['sourceDir'];
        }
        if (isset($params['destinationDir']) && $params['destinationDir']) {
            $this->destinationDir = $params['destinationDir'];
        }
        if (isset($params['namespace']) && $params['namespace']) {
            $this->namespace = $params['namespace'];
        }
        if (isset($params['rootClassName']) && $params['rootClassName']) {
            $this->rootClassName = $params['rootClassName'];
        }
        if (isset($params['rootClassNameForCollection']) && $params['rootClassNameForCollection']) {
            $this->rootClassNameForCollection = $params['rootClassNameForCollection'];
        }
        if (isset($params['rootClassNamespace']) && $params['rootClassNamespace']) {
            $this->rootClassNamespace = $params['rootClassNamespace'];
        }
        if (isset($params['rootClassForCollectionNamespace']) && $params['rootClassForCollectionNamespace']) {
            $this->rootClassForCollectionNamespace = $params['rootClassForCollectionNamespace'];
        }
        if (isset($params['showRequires']) && $params['showRequires']) {
            $this->showRequires = $params['showRequires'];
        }
    }

    /**
     * Generate classes from json
     */
    public function generate()
    {
        $files = glob(realpath($this->sourceDir) . '/*.json');
        $classes = array();

        foreach ($files as $file) {
            $path = $this->generateClass($file);
            $classes[$path] = $path;
        }

        foreach ($classes as $classPath) {
            if ($this->showRequires) {
                echo "require_once '" . $classPath . "';\n";
            } else {
                echo $classPath . "\n";
            }
        }
    }


    /**
     * @param string $property
     * @param string $type
     * @return array
     */
    protected function generateGetMethod($property, $type)
    {
        return array(
            'name' => 'get' . ucfirst($property),
            'body' => 'return $this->' . lcfirst($property) . ';',
            'docblock' => DocBlockGenerator::fromArray(
                array(
                    'shortDescription' => 'Retrieve the ' . $property . ' property',
                    'longDescription' => null,
                    'tags' => array(
                        new Tag\ReturnTag(
                            array(
                                'datatype' => $type . '|null',
                            )
                        ),
                    ),
                )
            ),
        );
    }

    /**
     * @param string $property
     * @param string $type
     * @return array
     */
    protected function generateSetMethod($property, $type)
    {
        return array(
            'name' => 'set' . ucfirst($property),
            'parameters' => array(lcfirst($property)),
            'body' => '$this->' . lcfirst($property) . ' = $' . lcfirst($property) . ';' . "\n"
                . 'return $this;',
            'docblock' => DocBlockGenerator::fromArray(
                array(
                    'shortDescription' => 'Set the ' . $property . ' property',
                    'longDescription' => null,
                    'tags' => array(
                        new Tag\ParamTag($property, $type),
                        new Tag\ReturnTag(
                            array(
                                'datatype' => '$this',
                            )
                        ),
                    )
                )
            ),
        );
    }


    /**
     * @param string $sourceFile
     * @return string
     */
    protected function generateClass($sourceFile)
    {
        $sourceContent = json_decode(file_get_contents($sourceFile));
        $class = new ClassGenerator();

        $className = null;
        $mappingClasses = array();
        $propNameMap = $this->setPropNameMap($sourceContent);
        $isCollection = false;

        foreach ($sourceContent as $property => $value) {
            $ourPropertyName = array_search($property, $propNameMap);
            if ($ourPropertyName) {
                $property = $ourPropertyName;
            }
            if ($property === '@name') {
                //Class name
                $className = $value;
                if ($this->namespace) {
                    $class->setNamespaceName($this->namespace);
                }

                $class->setName($value);

            } elseif ($property === '@type') {
                continue;
            } elseif ($value === 'number' || $value === 'int' || $value === 'integer') {
                //Create property type number
                $class->addProperty($property, null, PropertyGenerator::FLAG_PROTECTED);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, 'int')),
                        MethodGenerator::fromArray($this->generateSetMethod($property, 'int')),
                    )
                );
            } elseif ($value === 'float' || $value === 'double' || $value === 'real') {
                //Create property type number
                $class->addProperty($property, null, PropertyGenerator::FLAG_PROTECTED);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, $value)),
                        MethodGenerator::fromArray($this->generateSetMethod($property, $value)),
                    )
                );
            } elseif ($value === 'string') {
                //Create property type string
                $class->addProperty($property, null, PropertyGenerator::FLAG_PROTECTED);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, $value)),
                        MethodGenerator::fromArray($this->generateSetMethod($property, $value)),
                    )
                );
            } elseif ($value === 'date') {
                //Create property type date
                $class->addProperty($property, null, PropertyGenerator::FLAG_PROTECTED);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, 'string')),
                        MethodGenerator::fromArray($this->generateSetMethod($property, 'string')),
                    )
                );
            } elseif ($value === 'array') {
                //Create property type date
                $class->addProperty($property, null, PropertyGenerator::FLAG_PROTECTED);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, 'array')),
                        MethodGenerator::fromArray($this->generateSetMethod($property, 'array')),
                    )
                );
            } elseif ($value === 'boolean' || $value === 'bool') {
                //Create property type boolean
                $class->addProperty($property, null, PropertyGenerator::FLAG_PROTECTED);
                $class->addMethods(
                    array(
                        MethodGenerator::fromArray($this->generateGetMethod($property, $value)),
                        MethodGenerator::fromArray($this->generateSetMethod($property, $value)),
                    )
                );
            } elseif ($property === "@model") {

                if ($this->namespace) {
                    $class->addUse($this->namespace . '\\' . ucfirst($value));
                }

            } elseif ($property === "@collection") {
                $isCollection = true;
                $class->addProperty('collection', array(), PropertyGenerator::FLAG_PROTECTED);
                $class->addMethods($this->getMethodsForCollection($value->model));
            } elseif ($property === "@parent") {
                //"@parent": "\\Classes\\Items",
                $class->setExtendedClass($value);
            } elseif (strpos($value, '@') === 0) {

                if ($className !== ucfirst(substr($value, 1))) {
                    if ($this->namespace) {
                        $class->addUse($this->namespace . '\\' . ucfirst(substr($value, 1)));
                    }
                }
                if ($this->namespace) {
                    $mappingClasses[$property] = $this->namespace . '\\' . ucfirst(substr($value, 1));
                } else {
                    $mappingClasses[$property] = ucfirst(substr($value, 1));
                }

                //Create property type Class
                $class->addProperty($property, null, PropertyGenerator::FLAG_PROTECTED);
                $class->addMethods(
                    array(
                        // Method passed as array
                        MethodGenerator::fromArray($this->generateGetMethod($property, ucfirst(substr($value, 1)))),
                        MethodGenerator::fromArray($this->generateSetMethod($property, ucfirst(substr($value, 1)))),
                    )
                );
            } else {
                var_dump($value, $property);
                exit;
            }
        }

        if ($isCollection === true) {
            if ($this->rootClassNameForCollection) {
                $class->setExtendedClass($this->rootClassNameForCollection);
                $class->addUse($this->rootClassForCollectionNamespace);
            }
        } else {
            if ($this->rootClassName) {
                $class->setExtendedClass($this->rootClassName);
                $class->addUse($this->rootClassNamespace);
            }
        }

        $class->addProperty($this->mappingClassesPropertyName, $mappingClasses, PropertyGenerator::FLAG_PROTECTED);
        $class->addProperty($this->mappingPropertyName, $propNameMap, PropertyGenerator::FLAG_PROTECTED);

        $file = new FileGenerator(
            array(
                'classes' => array($class),
            )
        );

        $code = $file->generate();

        $path = realpath($this->destinationDir) . '/' . ucfirst($className) . '.php';
        $code = str_replace("\n\n}\n", '}', $code);
        file_put_contents($path, $code);
        return $path;
    }

    /**
     * @param string $modelName
     * @return array
     */
    protected function getMethodsForCollection($modelName)
    {
        return array(
            // Method passed as array
            MethodGenerator::fromArray(
                array(
                    'name' => 'add',
                    'parameters' => array(lcfirst($modelName)),
                    'body' => '
if (is_array($' . lcfirst($modelName) . ')) {
    $this->collection[] = new ' . ucfirst($modelName) . '($' . lcfirst($modelName) . ');
} elseif (is_object($' . lcfirst($modelName) . ') && $' . lcfirst($modelName) . ' instanceof ' . ucfirst($modelName)
        . ') {
    $this->collection[] = $' . lcfirst($modelName) . ';
}

return $this;
',
                    'docblock' => DocBlockGenerator::fromArray(
                        array(
                            'shortDescription' => 'Add item',
                            'longDescription' => null,
                            new Tag\ParamTag($modelName, ucfirst($modelName)),
                            new Tag\ReturnTag(
                                array(
                                    'datatype' => '$this',
                                )
                            ),
                        )
                    ),
                )
            ),
            MethodGenerator::fromArray(
                array(
                    'name' => 'getAll',
                    'body' => 'return $this->collection;',
                    'docblock' => DocBlockGenerator::fromArray(
                        array(
                            'shortDescription' => 'Get items',
                            'longDescription' => null,
                            new Tag\ParamTag($modelName, ucfirst($modelName)),
                            new Tag\ReturnTag(
                                array(
                                    'datatype' => '$this',
                                )
                            ),
                        )
                    ),
                )
            )
        );
    }

    /**
     * Check property to under score style
     *
     * @param $param
     * @return bool
     */
    private function isDashExist($param)
    {
        return (bool)strpos($param, "_") || (bool)strpos($param, "-");
    }

    /**
     * Convert word to upper case format
     *
     * @param $word
     * @return string
     */
    private function toUpperCaseFormat($word)
    {
        $wordInUpperCase = $word;
        $wordInUpperCase = str_replace("_", "", mb_convert_case($wordInUpperCase, MB_CASE_TITLE));

        if (strpos($wordInUpperCase, "-")) {
            $wordInUpperCase = str_replace("-", "", mb_convert_case($wordInUpperCase, MB_CASE_TITLE));
        }

        return lcfirst($wordInUpperCase);
    }

    /**
     * Set property name map
     *
     * Needed for under score property name
     *
     * @param array $data
     * @return array
     */
    private function setPropNameMap($data)
    {
        $propertyNameMap = array();

        foreach ($data as $property => $val) {
            $mapValue = $property;
            if ($this->isDashExist($property) == true) {
                $property = $this->toUpperCaseFormat($property);
                $propertyNameMap[$property] = $mapValue;
            }
        }

        return $propertyNameMap;
    }
}
