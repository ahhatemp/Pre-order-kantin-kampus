<?php
// Jam operasional kantin dipakai bersama oleh endpoint publik, admin, dan
// validasi waktu pengambilan saat order dibuat.

function kantin_day_names(): array {
    return [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];
}

function kantin_hours_fetch(PDO $pdo): array {
    $stmt = $pdo->query('SELECT day_of_week, is_open, open_time, close_time FROM kantin_hours ORDER BY day_of_week');
    $names = kantin_day_names();
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $day = (int)$row['day_of_week'];
        $rows[] = [
            'day_of_week' => $day,
            'hari' => $names[$day] ?? '',
            'is_open' => (int)$row['is_open'] === 1,
            'open_time' => substr((string)$row['open_time'], 0, 5),
            'close_time' => substr((string)$row['close_time'], 0, 5),
        ];
    }
    return $rows;
}

function kantin_hours_for_day(PDO $pdo, int $dayOfWeek): ?array {
    foreach (kantin_hours_fetch($pdo) as $row) {
        if ($row['day_of_week'] === $dayOfWeek) return $row;
    }
    return null;
}
