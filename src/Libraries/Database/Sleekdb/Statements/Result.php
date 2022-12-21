<?php

/**
 * Quantum PHP Framework
 *
 * An open source software development framework for PHP
 *
 * @package Quantum
 * @author Arman Ag. <arman.ag@softberg.org>
 * @copyright Copyright (c) 2018 Softberg LLC (https://softberg.org)
 * @link http://quantum.softberg.org/
 * @since 2.9.0
 */

namespace Quantum\Libraries\Database\Sleekdb\Statements;

use Quantum\Libraries\Database\DbalInterface;

/**
 * Trait Result
 * @package Quantum\Libraries\Database\Sleekdb\Statements
 */
trait Result
{
    /**
     * @inheritDoc
     * @return array
     * @throws \Quantum\Exceptions\DatabaseException
     * @throws \Quantum\Exceptions\ModelException
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    public function get(?int $returnType = DbalInterface::TYPE_ARRAY): array
    {
        return $this->getBuilder()->getQuery()->fetch();
    }

    /**
     * @inheritDoc
     * @return DbalInterface
     * @throws \Quantum\Exceptions\DatabaseException
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    public function findOne(int $id): DbalInterface
    {
        $result = $this->getOrmModel()->findById($id);

        $this->data = $result;
        $this->modifiedFields = $result;
        $this->isNew = false;

        return $this;
    }

    /**
     * @inheritDoc
     * @throws \Quantum\Exceptions\DatabaseException
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    public function findOneBy(string $column, $value): DbalInterface
    {
        $result = $this->getOrmModel()->findOneBy([$column, '=', $value]);

        $this->data = $result;
        $this->modifiedFields = $result;
        $this->isNew = false;

        return $this;
    }

    /**
     * @inheritDoc
     * @throws \Quantum\Exceptions\DatabaseException
     * @throws \Quantum\Exceptions\ModelException
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    public function first(): DbalInterface
    {
        $result = $this->getBuilder()->getQuery()->first();

        $this->data = $result;
        $this->modifiedFields = $result;
        $this->isNew = false;

        return $this;
    }

    /**
     * @inheritDoc
     * @throws \Quantum\Exceptions\DatabaseException
     * @throws \Quantum\Exceptions\ModelException
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    public function count(): int
    {
        return count($this->getBuilder()->getQuery()->fetch());
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        return $this->data ?: [];
    }

}