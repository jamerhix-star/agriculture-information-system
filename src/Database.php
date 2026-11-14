<?php
// src/Database.php
class Database {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function select(string $table, string $cols = '*', array $filters = [], array $options = []): array {
        $sql = "SELECT $cols FROM $table";
        [$where, $params] = $this->buildWhere($filters);
        if ($where) $sql .= ' WHERE ' . $where;
        if (!empty($options['order'])) $sql .= ' ORDER BY ' . $options['order'];
        if (isset($options['limit'])) $sql .= ' LIMIT ' . (int)$options['limit'];
        if (isset($options['offset'])) $sql .= ' OFFSET ' . (int)$options['offset'];
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function find(string $table, int $id, string $pk): ?array {
        $st = $this->pdo->prepare("SELECT * FROM $table WHERE $pk = ?");
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function insert(string $table, array $data): array {
        $cols = implode(',', array_keys($data));
        $ph = implode(',', array_fill(0, count($data), '?'));
        $st = $this->pdo->prepare("INSERT INTO $table ($cols) VALUES ($ph) RETURNING *");
        $st->execute(array_values($data));
        return $st->fetch() ?: [];
    }

    public function update(string $table, array $data, array $filters): array {
        $set = implode(',', array_map(fn($k) => "$k = ?", array_keys($data)));
        [$where, $params] = $this->buildWhere($filters);
        $sql = "UPDATE $table SET $set";
        if ($where) $sql .= ' WHERE ' . $where;
        $sql .= ' RETURNING *';
        $st = $this->pdo->prepare($sql);
        $st->execute(array_merge(array_values($data), $params));
        return $st->fetchAll();
    }

    public function delete(string $table, array $filters): array {
        [$where, $params] = $this->buildWhere($filters);
        $sql = "DELETE FROM $table";
        if ($where) $sql .= ' WHERE ' . $where;
        $sql .= ' RETURNING *';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    private function buildWhere(array $filters): array {
        $parts = []; $params = [];
        foreach ($filters as $f) {
            // simple eq filter like "crop_id=eq.1"
            if (preg_match('/^(\w+)=eq\.(.+)$/', $f, $m)) {
                $parts[] = $m[1] . ' = ?';
                $params[] = urldecode($m[2]);
                continue;
            }
            // or=(a.ilike.%x%,b.ilike.%y%)
            if (preg_match('/^or=\((.+)\)$/', $f, $m)) {
                $conds = [];
                foreach (explode(',', $m[1]) as $c) {
                    if (preg_match('/^(\w+)\.ilike\.(.+)$/', $c, $cm)) {
                        $conds[] = $cm[1] . ' ILIKE ?';
                        $params[] = str_replace('%', '', urldecode($cm[2])); // placeholder
                    }
                }
                // rebuild like pattern properly
                // redo params with % wildcards
                $conds = [];
                $params = [];
                foreach (explode(',', $m[1]) as $c) {
                    if (preg_match('/^(\w+)\.ilike\.(.+)$/', $c, $cm)) {
                        $conds[] = $cm[1] . ' ILIKE ?';
                        $val = urldecode($cm[2]);
                        $params[] = '%' . str_replace('%', '', $val) . '%';
                    }
                }
                $parts[] = '(' . implode(' OR ', $conds) . ')';
                continue;
            }
            // plain column=eq.value already handled; skip unknown
        }
        return [implode(' AND ', $parts), $params];
    }
}
