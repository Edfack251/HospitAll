<?php
namespace App\Repositories;

use PDO;

abstract class BaseRepository
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function beginTransaction() { return $this->pdo->beginTransaction(); }
    public function commit() { return $this->pdo->commit(); }
    public function rollBack() { return $this->pdo->rollBack(); }
    public function getPDO() { return $this->pdo; }

    /**
     * Appends a soft delete condition to a SQL query.
     * 
     * @param string $sql The SQL query.
     * @param string $alias The table alias (optional).
     * @return string
     */
    protected function applySoftDeleteFilter(string $sql, string $alias = ''): string
    {
        $condition = $alias ? "{$alias}.deleted_at IS NULL" : "deleted_at IS NULL";
        
        // Check if query already has a WHERE clause
        if (stripos($sql, 'WHERE') !== false) {
            // Find insertion point before GROUP BY, ORDER BY, or LIMIT
            $keywords = ['GROUP BY', 'ORDER BY', 'LIMIT'];
            $insertionPoint = strlen($sql);
            
            foreach ($keywords as $keyword) {
                $pos = stripos($sql, $keyword);
                if ($pos !== false && $pos < $insertionPoint) {
                    $insertionPoint = $pos;
                }
            }
            
            $head = substr($sql, 0, $insertionPoint);
            $tail = substr($sql, $insertionPoint);
            
            return $head . " AND " . $condition . " " . $tail;
        } else {
            // No WHERE clause, append it before other keywords
            $keywords = ['GROUP BY', 'ORDER BY', 'LIMIT'];
            $insertionPoint = strlen($sql);
            
            foreach ($keywords as $keyword) {
                $pos = stripos($sql, $keyword);
                if ($pos !== false && $pos < $insertionPoint) {
                    $insertionPoint = $pos;
                }
            }
            
            $head = substr($sql, 0, $insertionPoint);
            $tail = substr($sql, $insertionPoint);
            
            return $head . " WHERE " . $condition . " " . $tail;
        }
    }

    /**
     * Performs a soft delete on a record.
     * 
     * @param string $table The table name.
     * @param int $id The record ID.
     * @return bool
     */
    protected function softDelete(string $table, int $id): bool
    {
        $sql = "UPDATE {$table} SET deleted_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Gets a record by id, including soft-deleted rows.
     *
     * @param string $table The table name.
     * @param int $id The record ID.
     * @return array|null
     */
    protected function findByIdIncludingDeleted(string $table, int $id): ?array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT * FROM {$table} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    /**
     * Restores a soft-deleted record by setting deleted_at to NULL.
     *
     * @param string $table The table name.
     * @param int $id The record ID.
     * @return bool
     */
    protected function restoreRecord(string $table, int $id): bool
    {
        $sql = "UPDATE {$table} SET deleted_at = NULL WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Returns only soft-deleted records for a given table.
     *
     * @param string $table The table name.
     * @param string $columns Columns to select.
     * @param string $orderBy Optional ORDER BY clause (without the keyword).
     * @return array
     */
    protected function getDeletedRecords(string $table, string $columns = '*', string $orderBy = ''): array
    {
        $sql = "SELECT {$columns} FROM {$table} WHERE deleted_at IS NOT NULL";
        if ($orderBy !== '') {
            $sql .= " ORDER BY {$orderBy}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
