<?php
/*
 * Copyright (c) 2022. Jakub Turczyński
 *
 * Wszelkie prawa zastrzeżone. Poniższy kod źródłowy (zwany także programem komputerowym lub krótko - programem), zarówno w jego części twórczej, jak i całości, podlega ochronie na mocy prawa autorskiego jako utwór.
 * Użytkownikowi zezwala się na dostęp do kodu źródłowego oraz na jego użytkowanie w sposób, w jaki program został do tego przeznaczony. Kopiowanie, powielanie czy edytowanie całości lub części kodu źródłowego programu bez zgody jego autora jest zabronione.
 */

namespace Dc\Classes;
use TypedException;
use UnhandledMatchError;

class DB
{
    private $connectionType;
    private string $fetchType = "ASSOC";

    private $dbLocation;
    private $dbName;
    private $dbUser;
    private $dbPassword;

    private $handle;
    private $transaction;

    private int $lastRowCount = 0;

    private bool $connected = false;
    private bool $inTransaction = false;

    /**
     * @param $type
     * @throws TypedException
     */
    function __construct(string $type = "firebird", ?string $dbLocation = null, ?string $dbName = null, ?string $dbUser = null, ?string $dbPassword = null)
    {
        $this->changeConnType($type, $dbLocation, $dbName, $dbUser, $dbPassword);

        return $this;
    }

    /**
     *
     */
    function __destruct()
    {
        $this->terminateDatabaseConnection();
    }


    private function getConnectionTypeDbLocation($type = "mysql")
    {
        try {
            $r = match ($type) {
                "firebird" => null,
                "mysql" => null,
                "pdo" => null,
                "target" => null,
                default => null
            };
        } catch (UnhandledMatchError $e) {
            throw new TypedException("Nierozpoznany typ bazy danych: " . $type . PHP_EOL . $e->getMessage(), 500, "database_error");
        }

        return $r;
    }

    private function getConnectionTypeDbName($type = "mysql")
    {
        try {
            $r = match ($type) {
                "firebird" => null,
                "mysql" => null,
                "pdo" => null,
                default => null
            };
        } catch (UnhandledMatchError $e) {
            throw new TypedException("Nierozpoznany typ bazy danych: " . $type . PHP_EOL . $e->getMessage(), 500, "database_error");
        }

        return $r;
    }

    private function getConnectionTypeDbUser($type = "mysql")
    {
        try {
            $r = match ($type) {
                "firebird" => null,
                "mysql" => null,
                "pdo" => null,
                default => null
            };
        } catch (UnhandledMatchError $e) {
            throw new TypedException("Nierozpoznany typ bazy danych: " . $type . PHP_EOL . $e->getMessage(), 500, "database_error");
        }

        return $r;
    }

    private function getConnectionTypeDbPassword($type = "mysql")
    {
        try {
            $r = match ($type) {
                "firebird" => null,
                "mysql" => null,
                "pdo" => null,
                default => null
            };
        } catch (UnhandledMatchError $e) {
            throw new TypedException("Nierozpoznany typ bazy danych: " . $type . PHP_EOL . $e->getMessage(), 500, "database_error");
        }

        return $r;
    }


    /**
     * @return void
     * @throws TypedException
     */
    private function establishDatabaseConnection()
    {
        if ($this->inTransaction) {
            throw new TypedException("Nie mozna zmienic typu polaczenia podczas rozpoczetej transakcji! Zakoncz transakcje za pomoca metod 'rollbackTransaction()' lub 'commitTransaction()'.", 501, "database_error");
        }

        $this->terminateDatabaseConnection();

        $this->handle = match ($this->connectionType) {
            "firebird" => $this->_startIbaseConnection(), //ibase
            "mysql" => $this->_startMysqliConnection(), //mysql
            "pdo" => $this->_startPdoConnection(), //pdo
        };

        $this->connected = true;
    }

    /**
     * @return void
     */
    private function terminateDatabaseConnection()
    {
        if ($this->connected) {
            match ($this->connectionType) {
                "firebird" => ibase_close($this->handle),
                "mysql" => $this->handle->close(),
                "pdo" => true,
            };

            $this->handle = null;
            $this->transaction = null;
            $this->connected = false;
        }
    }


    /**
     * @return void
     * @throws TypedException
     */
    private function _startIbaseConnection()
    {
        $dbh = ibase_connect($this->dbLocation, $this->dbUser, $this->dbPassword, 'WIN1250', 0, 1);
        if (!$dbh) {
            throw new TypedException("Blad przy probie polaczenia z baza danych {$this->connectionType}.", 502, "database_error");
        }

        return $dbh;
    }

    /**
     * @return void
     * @throws TypedException
     */
    private function _startMysqliConnection()
    {
        $mysqli = mysqli_connect($this->dbLocation, $this->dbUser, $this->dbPassword, $this->dbName);
        if (!$mysqli) {
            throw new TypedException("Blad przy probie polaczenia z baza danych {$this->connectionType}.", 480, 'mysqli_connection_error');
        }
        if ($mysqli->connect_errno) {
            throw new TypedException("Blad przy probie polaczenia z baza danych {$this->connectionType}: " . 'mysqli connection error: ' . $mysqli->connect_error, 481, 'mysqli_connection_error');
        }

        return $mysqli;
    }

    /**
     * @return void
     * @throws TypedException
     */
    private function _startPdoConnection()
    {
        try {
            $pdo = new PDO("sqlsrv:Server=" . $this->dbLocation . ";Database=" . $this->dbName, $this->dbUser, $this->dbPassword);
        } catch (PDOException $e) {
            throw new TypedException("Blad przy probie polaczenia z baza danych {$this->connectionType}: " . $e->getMessage(), 490, 'sqlserver_connection_error');
        }

        return $pdo;
    }

    /**
     * @param $dbh
     * @param $type
     * @return false|resource
     */
    private function _getIbaseTransaction($type = "READ", $dbh = null)
    {
        if (is_null($dbh)) {
            $dbh = $this->handle;
        }
        $tr = ($type == "READ") ? ibase_trans($dbh, IBASE_READ | IBASE_CONCURRENCY | IBASE_WAIT) : ibase_trans($dbh, IBASE_WRITE | IBASE_CONCURRENCY | IBASE_WAIT);

        $this->transaction = $tr;

        return $this->transaction;
    }


    /**
     * @param $type
     * @return false|resource
     * @throws TypedException
     */
    public function beginTransaction($type = "READ")
    {
        $r = false;

        if (!$this->inTransaction) {
            if (!$this->connected) {
                $this->establishDatabaseConnection();
            }

            $r = match ($this->connectionType) {
                "firebird" => $this->_getIbaseTransaction($type),
                "mysql" => $this->handle->begin_transaction(),
                "pdo" => $this->handle->beginTransaction(),
            };

            $this->inTransaction = true;
        }

        return $r;
    }

    /**
     * @return bool
     */
    public function commitTransaction()
    {
        $r = false;

        if ($this->inTransaction) {
            $r = match ($this->connectionType) {
                "firebird" => ibase_commit($this->transaction),
                "mysql" => $this->handle->commit(),
                "pdo" => $this->handle->commit()
            };

            $this->inTransaction = false;
            //$this->terminateDatabaseConnection();
        }

        return $r;
    }

    public function commitTransactionRet()
    {
        $r = false;

        if ($this->inTransaction) {
            $r = match ($this->connectionType) {
                "firebird" => ibase_commit_ret($this->transaction),
                "mysql" => $this->handle->commit(),
                "pdo" => $this->handle->commit()
            };

            //$this->terminateDatabaseConnection();
        }

        return $r;
    }

    /**
     * @return bool
     */
    public function rollbackTransaction()
    {
        $r = false;

        if ($this->inTransaction) {
            $r = match ($this->connectionType) {
                "firebird" => ibase_rollback($this->transaction),
                "mysql" => $this->handle->rollback(),
                "pdo" => $this->handle->rollback(),
            };

            $this->inTransaction = false;
            //$this->terminateDatabaseConnection();
        }

        return $r;
    }


    /**
     * @param $queryString
     * @param $singleRow
     * @param $singleValue
     * @return array|bool|mixed
     * @throws TypedException
     */
    public function query($queryString, $singleRow = false, $singleValue = false)
    {
        if (!$this->connected) {
            $this->establishDatabaseConnection();
        }

        $r = match ($this->connectionType) {
            "firebird" => $this->ibaseQuery($queryString, $singleRow, $singleValue),
            "mysql" => $this->mysqliQuery($queryString, $singleRow, $singleValue),
            "pdo" => $this->pdoQuery($queryString, $singleRow, $singleValue),
        };

        if (!$this->inTransaction) {
            $this->terminateDatabaseConnection();
        }

        return $r;
    }

    /**
     * @param $queryString
     * @param $sRow
     * @return array|bool|mixed
     * @throws TypedException
     */
    private function ibaseQuery($queryString, $sRow = false, $sValue = false)
    {
        $r = ibase_query((($this->inTransaction) ? $this->transaction : $this->handle), $queryString);
        $errMsg = ibase_errmsg();

        //if concurent transaction
        if ($errMsg !== false && str_contains($errMsg, 'deadlock update conflicts with concurrent update concurrent transaction')) {
            sleep(2);
            $r = ibase_query((($this->inTransaction) ? $this->transaction : $this->handle), $queryString);
            $errMsg = ibase_errmsg();
        }

        if (!$r) {
            throw new TypedException("Błąd podczas wykonywania zapytania: " . $errMsg . $queryString, 510, "database_error");
        }

        if (is_bool($r)) {
            $this->lastRowCount = -1;
            return $r;
        } else if (is_int($r)) {
            $this->lastRowCount = $r;
            return boolval($r);
        }

        $fechFunc = match ($this->fetchType) {
            "ASSOC" => "ibase_fetch_assoc",
            "OBJECT" => "ibase_fetch_object",
            "ROW" => "ibase_fetch_row"
        };

        $d = [];
        if ($sRow) {
            if ($row = call_user_func($fechFunc, $r, IBASE_TEXT)) {
                foreach ($row as $k => $v) {
                    $row[$k] = iconv("Windows-1250", "UTF-8//IGNORE", $v);
                }
                $d = ($sValue) ? array_shift($row) : $row;

                $this->lastRowCount = 1;
            } else {
                $this->lastRowCount = 0;
                return false;
            }
        } else {
            while ($row = call_user_func($fechFunc, $r)) {
                foreach ($row as $k => $v) {
                    $row[$k] = iconv("Windows-1250", "UTF-8//IGNORE", $v);
                }
                $d[] = $row;
            }

            //primitive way of getting row count, ibase do not have built in count method - Jakub T.
            $this->lastRowCount = count($d);
        }
        ibase_free_result($r);

        return $d;
    }

    /**
     * @param $queryString
     * @param $sRow
     * @return array|bool
     * @throws TypedException
     */
    private function mysqliQuery($queryString, $sRow = false, $sValue = false)
    {
        $r = @$this->handle->query($queryString);

        if (!$r) {
            throw new TypedException("Błąd podczas wykonywania zapytania: " . $this->handle->error, 520, "database_error");
        } else if (is_bool($r)) {
            $this->lastRowCount = -1;
            return $r;
        }

        $fechFunc = match ($this->fetchType) {
            "ASSOC" => "fetch_assoc",
            "OBJECT" => "fetch_object",
            "ROW" => "fetch_row"
        };

        $d = [];
        if ($sRow) {
            if ($row = $r->$fechFunc()) {
                $d = ($sValue) ? array_shift($row) : $row;

                $this->lastRowCount = 1;
            } else {
                $this->lastRowCount = 0;
                return false;
            }
        } else {
            while ($row = $r->$fechFunc()) {
                $d[] = $row;
            }

            $this->lastRowCount = $r->num_rows;
        }
        $r->free();

        return $d;
    }

    /**
     * @param $queryString
     * @param $sRow
     * @return array|bool
     * @throws TypedException
     */
    private function pdoQuery($queryString, $sRow = false, $sValue = false)
    {
        try {
            $stm = @$this->handle->prepare($queryString);
            $r = $stm->execute();
        } catch (PDOException $e) {
            throw new TypedException("Błąd podczas wykonywania zapytania: " . $e->getMessage(), 530, "database_error");
        }
        if (!$r) {
            throw new TypedException("Błąd podczas wykonywania zapytania: " . $this->handle->errorInfo(), 531, "database_error");
        }
        // DDL/DCL/DML/TCL else DQL
        if ($stm->columnCount() == 0) {
            $this->lastRowCount = -1;
            return true;
        }

        $fechFunc = match ($this->fetchType) {
            "ASSOC" => PDO::FETCH_ASSOC,
            "OBJECT" => PDO::FETCH_OBJ,
            "ROW" => PDO::FETCH_NUM
        };

        $d = [];
        if ($sRow) {
            if ($row = $stm->fetch($fechFunc)) {
                $d = ($sValue) ? array_shift($row) : $row;

                $this->lastRowCount = 1;
            } else {
                $this->lastRowCount = 0;
                return false;
            }
        } else {
            while ($row = $stm->fetch($fechFunc)) {
                $d[] = $row;
            }

            //primitive way of getting row count, PDO rowCount() method do not work with every db type - Jakub T.
            $this->lastRowCount = count($d);
        }
        $stm->closeCursor();

        return $d;
    }


    /**
     * @return string
     */
    public function getConnType()
    {
        return $this->connectionType;
    }

    /**
     * @param $type
     * @return $this
     * @throws TypedException
     */
    public function changeConnType(string $type, ?string $dbLocation = null, ?string $dbName = null, ?string $dbUser = null, ?string $dbPassword = null)
    {
        try {
            $r = match ($type) {
                "firebird" => true,
                "mysql" => true,
                "pdo" => true,
                default => false
            };
        } catch (UnhandledMatchError $e) {
            throw new TypedException("Nierozpoznany typ bazy danych: " . $type . PHP_EOL . $e->getMessage(), 500, "database_error");
        }

        if ($r) {
            $this->dbLocation = (is_null($dbLocation)) ? $this->getConnectionTypeDbLocation($type) : $dbLocation;
            $this->dbName = (is_null($dbName)) ? $this->getConnectionTypeDbName($type) : $dbName;
            $this->dbUser = (is_null($dbUser)) ? $this->getConnectionTypeDbUser($type) : $dbUser;
            $this->dbPassword = (is_null($dbPassword)) ? $this->getConnectionTypeDbPassword($type) : $dbPassword;

            $this->connectionType = $type;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getFechType()
    {
        return $this->fetchType;
    }

    /**
     * @return int
     */
    public function getRowCount()
    {
        return $this->lastRowCount;
    }

    /**
     * @return mixed
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @return mixed
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * @return bool
     */
    public function hasTransaction()
    {
        return $this->inTransaction;
    }


    /**
     * @return false|string
     */
    public function getTargetBlApiToken()
    {
        return APITOKEN1;
    }

    /**
     * @return false|string
     */
    public function getUnimetBlApiToken()
    {
        return APITOKEN2;
    }

    /**
     * @param mixed $fetchType
     */
    public function setFetchType($fetchType): void
    {
        $this->fetchType = $fetchType;
    }

    /**
     * @param mixed $handle
     */
    public function setHandle($handle): void
    {
        $this->handle = $handle;
    }

    /**
     * @param mixed $transaction
     */
    public function setTransaction($transaction): void
    {
        $this->transaction = $transaction;
    }

    /**
     * @return $this
     */
    public function close()
    {
        $this->rollbackTransaction();
        $this->terminateDatabaseConnection();

        return $this;
    }
}