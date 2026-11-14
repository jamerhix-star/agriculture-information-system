<?php
// models/User.php
class User {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array {
        return $this->db->select('users', '*', $filters, $options);
    }

    public function find(int $id): ?array {
        return $this->db->find('users', $id, 'user_id');
    }

    public function create(array $data): array {
        return $this->db->insert('users', $data);
    }

    public function update(int $id, array $data): array {
        return $this->db->update('users', $data, ['user_id=eq.' . $id]);
    }

    public function delete(int $id): array {
        return $this->db->delete('users', ['user_id=eq.' . $id]);
    }

    public function search(string $term): array {
        $filters = [];
        foreach (preg_split('/\s+/', trim($term)) as $word) {
            if ($word === '') continue;
            $w = rawurlencode($word);
            $filters[] = 'or=(username.ilike.%' . $w . '%,full_name.ilike.%' . $w . '%)';
        }
        return $this->all($filters);
    }

    public function count(): int {
        return count($this->db->select('users', 'user_id'));
    }
}
