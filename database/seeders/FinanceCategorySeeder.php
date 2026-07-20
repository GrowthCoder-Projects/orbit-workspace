<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Income categories
            [
                'name' => 'Gaji',
                'type' => 'income',
                'icon' => 'Briefcase',
                'color' => '#10b981',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Freelance / Proyek',
                'type' => 'income',
                'icon' => 'Laptop',
                'color' => '#3b82f6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Investasi (Masuk)',
                'type' => 'income',
                'icon' => 'TrendingUp',
                'color' => '#8b5cf6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hadiah / Hibah',
                'type' => 'income',
                'icon' => 'Gift',
                'color' => '#ec4899',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pemasukan Lainnya',
                'type' => 'income',
                'icon' => 'DollarSign',
                'color' => '#6b7280',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Expense categories
            [
                'name' => 'Makanan & Minuman',
                'type' => 'expense',
                'icon' => 'Utensils',
                'color' => '#f97316',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Transportasi',
                'type' => 'expense',
                'icon' => 'Car',
                'color' => '#eab308',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tagihan & Langganan',
                'type' => 'expense',
                'icon' => 'CreditCard',
                'color' => '#ef4444',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hiburan',
                'type' => 'expense',
                'icon' => 'Gamepad2',
                'color' => '#a855f7',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Belanja',
                'type' => 'expense',
                'icon' => 'ShoppingBag',
                'color' => '#f43f5e',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kesehatan',
                'type' => 'expense',
                'icon' => 'HeartPulse',
                'color' => '#14b8a6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Investasi (Keluar)',
                'type' => 'expense',
                'icon' => 'Coins',
                'color' => '#6366f1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pengeluaran Lainnya',
                'type' => 'expense',
                'icon' => 'HelpCircle',
                'color' => '#9ca3af',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('finance_categories')->insert($categories);
    }
}
