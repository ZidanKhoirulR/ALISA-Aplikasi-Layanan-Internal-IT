<?php
// ============================================
// app/models/CategoryField.php
// ============================================

class CategoryField
{
    public static function getByCategoryCode(string $categoryCode): array
    {
        return DB::fetchAll("SELECT * FROM category_fields WHERE category_code = ? ORDER BY sort_order ASC, id ASC", [$categoryCode]);
    }

    public static function getAllGrouped(): array
    {
        $fields = DB::fetchAll("SELECT * FROM category_fields WHERE is_active = 1 ORDER BY category_code ASC, sort_order ASC, id ASC");
        $grouped = [];
        foreach ($fields as $f) {
            $code = $f['category_code'];
            if (!isset($grouped[$code])) {
                $grouped[$code] = [];
            }
            $grouped[$code][] = $f;
        }
        return $grouped;
    }

    public static function findById(int $id): ?array
    {
        return DB::fetch("SELECT * FROM category_fields WHERE id = ?", [$id]);
    }

    public static function create(string $categoryCode, string $fieldKey, string $fieldLabel, string $fieldType, ?string $fieldOptions, int $isRequired, int $isActive, int $sortOrder): int
    {
        DB::run("INSERT INTO category_fields (category_code, field_key, field_label, field_type, field_options, is_required, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$categoryCode, $fieldKey, $fieldLabel, $fieldType, $fieldOptions, $isRequired, $isActive, $sortOrder]
        );
        return (int)DB::lastId();
    }

    public static function update(int $id, string $fieldKey, string $fieldLabel, string $fieldType, ?string $fieldOptions, int $isRequired, int $isActive, int $sortOrder): void
    {
        DB::run("UPDATE category_fields SET field_key = ?, field_label = ?, field_type = ?, field_options = ?, is_required = ?, is_active = ?, sort_order = ? WHERE id = ?",
            [$fieldKey, $fieldLabel, $fieldType, $fieldOptions, $isRequired, $isActive, $sortOrder, $id]
        );
    }

    public static function delete(int $id): void
    {
        DB::run("DELETE FROM category_fields WHERE id = ?", [$id]);
    }
}
