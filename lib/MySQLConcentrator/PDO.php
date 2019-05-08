<?php

namespace MySQLConcentrator;

class PDO extends \PDO
{
    function query($statement)
    {
        $result = parent::query($statement);
        if ($result === false) {
            $error_info = $this->errorInfo();
            throw new PDOException("Error executing '$statement': {$error_info[0]}:{$error_info[1]}:{$error_info[2]}");
        }
        return $result;
    }
}
