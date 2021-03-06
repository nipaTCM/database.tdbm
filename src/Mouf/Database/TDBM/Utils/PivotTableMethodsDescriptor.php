<?php

namespace Mouf\Database\TDBM\Utils;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;

class PivotTableMethodsDescriptor implements MethodDescriptorInterface
{
    /**
     * @var Table
     */
    private $pivotTable;

    private $useAlternateName = false;

    /**
     * @var ForeignKeyConstraint
     */
    private $localFk;

    /**
     * @var ForeignKeyConstraint
     */
    private $remoteFk;

    /**
     * @param Table                $pivotTable The pivot table
     * @param ForeignKeyConstraint $localFk
     * @param ForeignKeyConstraint $remoteFk
     */
    public function __construct(Table $pivotTable, ForeignKeyConstraint $localFk, ForeignKeyConstraint $remoteFk)
    {
        $this->pivotTable = $pivotTable;
        $this->localFk = $localFk;
        $this->remoteFk = $remoteFk;
    }

    /**
     * Requests the use of an alternative name for this method.
     */
    public function useAlternativeName()
    {
        $this->useAlternateName = true;
    }

    /**
     * Returns the name of the method to be generated.
     *
     * @return string
     */
    public function getName() : string
    {
        if (!$this->useAlternateName) {
            return 'get'.TDBMDaoGenerator::toCamelCase($this->remoteFk->getForeignTableName());
        } else {
            return 'get'.TDBMDaoGenerator::toCamelCase($this->remoteFk->getForeignTableName()).'By'.TDBMDaoGenerator::toCamelCase($this->pivotTable->getName());
        }
    }

    /**
     * Returns the plural name.
     *
     * @return string
     */
    private function getPluralName() : string
    {
        if (!$this->useAlternateName) {
            return TDBMDaoGenerator::toCamelCase($this->remoteFk->getForeignTableName());
        } else {
            return TDBMDaoGenerator::toCamelCase($this->remoteFk->getForeignTableName()).'By'.TDBMDaoGenerator::toCamelCase($this->pivotTable->getName());
        }
    }

    /**
     * Returns the singular name.
     *
     * @return string
     */
    private function getSingularName() : string
    {
        if (!$this->useAlternateName) {
            return TDBMDaoGenerator::toCamelCase(TDBMDaoGenerator::toSingular($this->remoteFk->getForeignTableName()));
        } else {
            return TDBMDaoGenerator::toCamelCase(TDBMDaoGenerator::toSingular($this->remoteFk->getForeignTableName())).'By'.TDBMDaoGenerator::toCamelCase($this->pivotTable->getName());
        }
    }

    /**
     * Returns the code of the method.
     *
     * @return string
     */
    public function getCode() : string
    {
        $singularName = $this->getSingularName();
        $pluralName = $this->getPluralName();
        $remoteBeanName = TDBMDaoGenerator::getBeanNameFromTableName($this->remoteFk->getForeignTableName());
        $variableName = '$'.TDBMDaoGenerator::toVariableName($remoteBeanName);
        $pluralVariableName = $variableName.'s';

        $str = '    /**
     * Returns the list of %s associated to this bean via the %s pivot table.
     *
     * @return %s[]
     */
    public function get%s()
    {
        return $this->_getRelationships(%s);
    }
';

        $getterCode = sprintf($str, $remoteBeanName, $this->pivotTable->getName(), $remoteBeanName, $pluralName, var_export($this->remoteFk->getLocalTableName(), true));

        $str = '    /**
     * Adds a relationship with %s associated to this bean via the %s pivot table.
     *
     * @param %s %s
     */
    public function add%s(%s %s)
    {
        return $this->addRelationship(%s, %s);
    }
';

        $adderCode = sprintf($str, $remoteBeanName, $this->pivotTable->getName(), $remoteBeanName, $variableName, $singularName, $remoteBeanName, $variableName, var_export($this->remoteFk->getLocalTableName(), true), $variableName);

        $str = '    /**
     * Deletes the relationship with %s associated to this bean via the %s pivot table.
     *
     * @param %s %s
     */
    public function remove%s(%s %s)
    {
        return $this->_removeRelationship(%s, %s);
    }
';

        $removerCode = sprintf($str, $remoteBeanName, $this->pivotTable->getName(), $remoteBeanName, $variableName, $singularName, $remoteBeanName, $variableName, var_export($this->remoteFk->getLocalTableName(), true), $variableName);

        $str = '    /**
     * Returns whether this bean is associated with %s via the %s pivot table.
     *
     * @param %s %s
     * @return bool
     */
    public function has%s(%s %s) : bool
    {
        return $this->hasRelationship(%s, %s);
    }
';

        $hasCode = sprintf($str, $remoteBeanName, $this->pivotTable->getName(), $remoteBeanName, $variableName, $singularName, $remoteBeanName, $variableName, var_export($this->remoteFk->getLocalTableName(), true), $variableName);

        $str = '    /**
     * Sets all relationships with %s associated to this bean via the %s pivot table.
     * Exiting relationships will be removed and replaced by the provided relationships.
     *
     * @param %s[] %s
     */
    public function set%s(array %s)
    {
        return $this->setRelationships(%s, %s);
    }
';

        $setterCode = sprintf($str, $remoteBeanName, $this->pivotTable->getName(), $remoteBeanName, $pluralVariableName, $pluralName, $pluralVariableName, var_export($this->remoteFk->getLocalTableName(), true), $pluralVariableName);

        $code = $getterCode.$adderCode.$removerCode.$hasCode.$setterCode;

        return $code;
    }

    /**
     * Returns an array of classes that needs a "use" for this method.
     *
     * @return string[]
     */
    public function getUsedClasses() : array
    {
        return [TDBMDaoGenerator::getBeanNameFromTableName($this->remoteFk->getForeignTableName())];
    }

    /**
     * Returns the code to past in jsonSerialize.
     *
     * @return string
     */
    public function getJsonSerializeCode() : string
    {
        $remoteBeanName = TDBMDaoGenerator::getBeanNameFromTableName($this->remoteFk->getForeignTableName());
        $variableName = '$'.TDBMDaoGenerator::toVariableName($remoteBeanName);

        return '        if (!$stopRecursion) {
            $array[\''.lcfirst($this->getPluralName()).'\'] = array_map(function ('.$remoteBeanName.' '.$variableName.') {
                return '.$variableName.'->jsonSerialize(true);
            }, $this->'.$this->getName().'());
        }
';
    }
}
