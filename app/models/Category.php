<?php
// ============================================
// app/models/Category.php
// ============================================

class Category
{
    public static function getAll(): array
    {
        return DB::fetchAll("SELECT * FROM service_categories ORDER BY sort_order ASC, id ASC");
    }

    public static function getActive(): array
    {
        return DB::fetchAll("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    }

    public static function findById(int $id): ?array
    {
        return DB::fetch("SELECT * FROM service_categories WHERE id = ?", [$id]);
    }

    public static function findByCode(string $code): ?array
    {
        return DB::fetch("SELECT * FROM service_categories WHERE code = ?", [$code]);
    }

    public static function create(string $code, string $name, ?string $iconClass, ?string $iconPath, int $sortOrder): int
    {
        DB::run("INSERT INTO service_categories (code, name, default_icon_class, custom_icon_path, sort_order) VALUES (?, ?, ?, ?, ?)",
            [$code, $name, $iconClass, $iconPath, $sortOrder]
        );
        $id = (int)DB::lastId();
        
        // Auto-inject default fields
        DB::run("INSERT INTO category_fields (category_code, field_key, field_label, field_type, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?)",
            [$code, 'ruangan', 'Ruangan', 'text', 1, 10]
        );
        DB::run("INSERT INTO category_fields (category_code, field_key, field_label, field_type, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?)",
            [$code, 'kendala_kebutuhan', 'Kendala / Kebutuhan', 'textarea', 1, 90]
        );

        return $id;
    }

    public static function update(int $id, string $code, string $name, ?string $iconClass, ?string $iconPath, int $sortOrder): void
    {
        DB::run("UPDATE service_categories SET code = ?, name = ?, default_icon_class = ?, custom_icon_path = ?, sort_order = ? WHERE id = ?",
            [$code, $name, $iconClass, $iconPath, $sortOrder, $id]
        );
    }

    public static function toggleActive(int $id): void
    {
        DB::run("UPDATE service_categories SET is_active = NOT is_active WHERE id = ?", [$id]);
    }

    public static function delete(int $id): void
    {
        DB::run("DELETE FROM service_categories WHERE id = ?", [$id]);
    }
}
