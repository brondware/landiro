<?php
class OrderLog {
    private $dir;

    public function __construct() {
        $this->dir = DATA_PATH . '/orders';
        if (!is_dir($this->dir)) mkdir($this->dir, 0755, true);
    }

    public function save(string $slug, array $postData, string $abVariant = ''): array {
        $id = date('YmdHis') . '_' . substr(uniqid(), -4);
        // Extract UTMs from post data, keep user fields separately
        $utmFields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
        $utms = array_intersect_key($postData, array_flip($utmFields));
        $clean = array_diff_key($postData, array_flip(array_merge($utmFields, ['_hp', '_ab_variants'])));

        $entry = [
            'id'         => $id,
            'created_at' => date('c'),
            'status'     => 'new',
            'price'      => 0,
            'note'       => '',
            'data'       => $clean,
            'utms'       => $utms ?: null,
            'ab_variant' => $abVariant ?: null,
            'ip'         => $this->anonIp($_SERVER['REMOTE_ADDR'] ?? ''),
        ];

        $file = $this->dir . '/' . $slug . '.json';
        $orders = $this->loadRaw($file);
        array_unshift($orders, $entry);
        if (count($orders) > 2000) $orders = array_slice($orders, 0, 2000);
        file_put_contents($file, json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        return $entry;
    }

    public function getAll(string $slug, int $limit = 200, int $offset = 0): array {
        return array_slice($this->loadRaw($this->dir . '/' . $slug . '.json'), $offset, $limit);
    }

    public function count(string $slug): int {
        return count($this->loadRaw($this->dir . '/' . $slug . '.json'));
    }

    public function setPrice(string $slug, string $orderId, float $price): bool {
        return $this->updateField($slug, $orderId, 'price', max(0, round($price, 2)));
    }

    public function setStatus(string $slug, string $orderId, string $status): bool {
        $allowed = ['new', 'called', 'confirmed', 'canceled'];
        if (!in_array($status, $allowed)) return false;
        return $this->updateField($slug, $orderId, 'status', $status);
    }

    public function setNote(string $slug, string $orderId, string $note): bool {
        return $this->updateField($slug, $orderId, 'note', substr(strip_tags($note), 0, 500));
    }

    private function updateField(string $slug, string $orderId, string $field, $value): bool {
        $file = $this->dir . '/' . $slug . '.json';
        $orders = $this->loadRaw($file);
        $found = false;
        foreach ($orders as &$o) {
            if ($o['id'] === $orderId) {
                $o[$field] = $value;
                $found = true;
                break;
            }
        }
        unset($o);
        if (!$found) return false;
        return file_put_contents($file, json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false;
    }

    public function delete(string $slug, string $orderId): bool {
        $file = $this->dir . '/' . $slug . '.json';
        $orders = $this->loadRaw($file);
        $orders = array_values(array_filter($orders, function($o) use ($orderId) { return $o['id'] !== $orderId; }));
        return file_put_contents($file, json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false;
    }

    public function getAllCounts(): array {
        $result = [];
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $result[basename($file, '.json')] = count($this->loadRaw($file));
        }
        return $result;
    }

    public function getRevenueSummary(string $slug): array {
        $orders = $this->loadRaw($this->dir . '/' . $slug . '.json');
        $total = 0.0;
        $count = 0;
        foreach ($orders as $o) {
            if (isset($o['price']) && $o['price'] > 0) {
                $total += (float)$o['price'];
                $count++;
            }
        }
        return ['total' => $total, 'count' => $count, 'avg' => $count > 0 ? round($total / $count, 2) : 0];
    }

    public function getAllRevenue(): array {
        $result = [];
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $slug = basename($file, '.json');
            $summary = $this->getRevenueSummary($slug);
            if ($summary['total'] > 0) $result[$slug] = $summary;
        }
        return $result;
    }

    public function toCsv(string $slug): string {
        $orders = $this->getAll($slug, 2000);
        if (empty($orders)) return '';
        // Collect all field names from first orders
        $fields = ['id', 'created_at', 'status', 'price', 'note'];
        foreach (array_slice($orders, 0, 20) as $o) {
            foreach (array_keys($o['data'] ?? []) as $k) {
                if (!in_array($k, $fields)) $fields[] = $k;
            }
        }
        $fields = array_merge($fields, ['utm_source', 'utm_medium', 'utm_campaign', 'ab_variant']);
        $rows = [];
        $rows[] = implode(';', array_map(function($f) { return '"' . $f . '"'; }, $fields));
        foreach ($orders as $o) {
            $row = [];
            foreach ($fields as $f) {
                if ($f === 'id') $row[] = '"' . ($o['id'] ?? '') . '"';
                elseif ($f === 'created_at') $row[] = '"' . date('d.m.Y H:i', strtotime($o['created_at'] ?? '')) . '"';
                elseif ($f === 'status') $row[] = '"' . ($o['status'] ?? 'new') . '"';
                elseif ($f === 'price') $row[] = number_format((float)($o['price'] ?? 0), 2, '.', '');
                elseif ($f === 'note') $row[] = '"' . str_replace('"', '""', $o['note'] ?? '') . '"';
                elseif ($f === 'utm_source') $row[] = '"' . ($o['utms']['utm_source'] ?? '') . '"';
                elseif ($f === 'utm_medium') $row[] = '"' . ($o['utms']['utm_medium'] ?? '') . '"';
                elseif ($f === 'utm_campaign') $row[] = '"' . ($o['utms']['utm_campaign'] ?? '') . '"';
                elseif ($f === 'ab_variant') $row[] = '"' . ($o['ab_variant'] ?? '') . '"';
                else $row[] = '"' . str_replace('"', '""', $o['data'][$f] ?? '') . '"';
            }
            $rows[] = implode(';', $row);
        }
        return "\xEF\xBB\xBF" . implode("\n", $rows); // UTF-8 BOM for Excel
    }

    private function loadRaw(string $file): array {
        if (!file_exists($file)) return [];
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    private function anonIp(string $ip): string {
        // Anonymize last octet for GDPR
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        return substr($ip, 0, strrpos($ip, ':') ?: strlen($ip)) ?: $ip;
    }
}
