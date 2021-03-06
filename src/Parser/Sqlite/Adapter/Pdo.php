<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\ParserConfigurationException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;

/**
 * Class Pdo
 *
 * @package Crossjoin\Browscap\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Pdo extends AdapterAbstract implements AdapterInterface, AdapterFactoryInterface
{
    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * Pdo constructor.
     *
     * @inheritdoc
     *
     * @throws ParserConditionNotSatisfiedException
     */
    public function __construct(string $fileName)
    {
        if (!$this->checkConditions()) {
            throw new ParserConditionNotSatisfiedException('PDO extension with Sqlite support missing.');
        }

        parent::__construct($fileName);
    }

    /**
     * @return bool
     */
    protected function checkConditions() : bool
    {
        return (class_exists('\PDO') && in_array('sqlite', \PDO::getAvailableDrivers(), true));
    }

    /**
     * @return \PDO
     * @throws ParserConfigurationException
     */
    protected function getConnection() : \PDO
    {
        if ($this->connection === null) {
            try {
                $this->connection = new \PDO('sqlite:' . $this->getFileName());
                $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                throw new ParserConfigurationException(
                    "Could not connect to database '" . $this->getFileName() . "'.", 0, $e
                );
            }
        }

        return $this->connection;
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function beginTransaction() : bool
    {
        try {
            return $this->getConnection()->beginTransaction();
        } catch (\PDOException $e) {
            throw new ParserRuntimeException('Transaction could not be started.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function commitTransaction() : bool
    {
        try {
            return $this->getConnection()->commit();
        } catch (\PDOException $e) {
            throw new ParserRuntimeException('Transaction could not be committed.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function query(string $statement) : array
    {
        try {
            return $this->getConnection()->query($statement)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new ParserRuntimeException('Statement could not be executed.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function prepare(string $statement) : PreparedStatementInterface
    {
        try {
            $preparedStatement = $this->getConnection()->prepare($statement);
            return new PdoPreparedStatement($preparedStatement);
        } catch (\PDOException $e) {
            throw new ParserRuntimeException('Statement could not be prepared.', 0, $e);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function exec(string $statement) : bool
    {
        try {
            return ($this->getConnection()->exec($statement) !== false);
        } catch (\PDOException $e) {
            throw new ParserRuntimeException('Statement could not be executed.', 0, $e);
        }
    }

    public function __destruct()
    {
        $this->connection = null;
    }
}
