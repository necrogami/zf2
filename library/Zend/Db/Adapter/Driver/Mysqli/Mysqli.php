<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Db
 */

namespace Zend\Db\Adapter\Driver\Mysqli;

use Zend\Db\Adapter\Driver\DriverInterface,
    Zend\Db\Adapter\Exception;

/**
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 */
class Mysqli implements DriverInterface
{

    /**
     * @var Connection
     */
    protected $connection = null;

    /**
     * @var Statement
     */
    protected $statementPrototype = null;

    /**
     * @var Result
     */
    protected $resultPrototype = null;

    /**
     * @var array
     */
    protected $options = array(
        'buffer_results' => false
    );

    /**
     * @param array|Connection|\mysqli $connection
     * @param null|Statement $statementPrototype
     * @param null|Result $resultPrototype
     */
    public function __construct($connection, Statement $statementPrototype = null, Result $resultPrototype = null, $options = null)
    {
        if (!$connection instanceof Connection) {
            $connection = new Connection($connection);
        }

        if (!$connection instanceof Connection) {
            throw new Exception\InvalidArgumentException('$connection must be an array of parameters or a Pdo\Connection object');
        }

        $this->registerConnection($connection);
        $this->registerStatementPrototype(($statementPrototype) ?: new Statement());
        $this->registerResultPrototype(($resultPrototype) ?: new Result());
    }

    /**
     * Register connection
     * 
     * @param  Connection $connection
     * @return Mysqli 
     */
    public function registerConnection(Connection $connection)
    {
        $this->connection = $connection;
        $this->connection->setDriver($this); // needs access to driver to createStatement()
        return $this;
    }

    /**
     * Register statement prototype
     * 
     * @param Statement $statementPrototype 
     */
    public function registerStatementPrototype(Statement $statementPrototype)
    {
        $this->statementPrototype = $statementPrototype;
        $this->statementPrototype->setDriver($this); // needs access to driver to createResult()
    }

    /**
     * @return null|Statement
     */
    public function getStatementPrototype()
    {
        return $this->statementPrototype;
    }

    /**
     * Register result prototype
     * 
     * @param Result $resultPrototype 
     */
    public function registerResultPrototype(Result $resultPrototype)
    {
        $this->resultPrototype = $resultPrototype;
    }

    /**
     * @return null|Result
     */
    public function getResultPrototype()
    {
        return $this->resultPrototype;
    }

    /**
     * Get database platform name
     * 
     * @param  string $nameFormat
     * @return string 
     */
    public function getDatabasePlatformName($nameFormat = self::NAME_FORMAT_CAMELCASE)
    {
        if ($nameFormat == self::NAME_FORMAT_CAMELCASE) {
            return 'Mysql';
        } else {
            return 'MySQL';
        }
    }

    /**
     * Check environment
     */
    public function checkEnvironment()
    {
        if (!extension_loaded('mysqli')) {
            throw new Exception\RuntimeException('The Mysqli extension is required for this adapter but the extension is not loaded');
        }
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $sql
     * @return Statement
     */
    public function createStatement($sqlOrResource = null)
    {
        if (is_resource($sqlOrResource) && !in_array($sqlOrResource, $this->resources, true)) {
            $this->resources[] = $sqlOrResource;
        }

        $statement = clone $this->statementPrototype;
        if (is_string($sqlOrResource)) {
            $statement->setSql($sqlOrResource);
        } elseif ($sqlOrResource instanceof \mysqli_stmt) {
            $statement->setResource($sqlOrResource);
        }
        $statement->initialize($this->connection->getResource());
        return $statement;
    }

    /**
     * @return Result
     */
    public function createResult($resource, $isBuffered = null)
    {
        $result = clone $this->resultPrototype;
        $result->initialize($resource, $this->connection->getLastGeneratedValue(), $isBuffered);
        return $result;
    }

    /**
     * @return array
     */
    public function getPrepareType()
    {
        return self::PARAMETERIZATION_POSITIONAL;
    }

    /**
     * @param $name
     * @return string
     */
    public function formatParameterName($name, $type = null)
    {
        return '?';
    }

    /**
     * @return mixed
     */
    public function getLastGeneratedValue()
    {
        return $this->getConnection()->getLastGeneratedValue();
    }

}
