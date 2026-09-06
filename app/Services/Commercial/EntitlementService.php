<?php

namespace App\Services\Commercial;

class EntitlementService
{
    /**
     * Canonical catalog of all officially supported commercial entitlement keys in Pilot POS.
     *
     * @return array<string, array{key: string, label: string, label_ar: string, description: string, description_ar: string, category: string}>
     */
    public function catalog(): array
    {
        return [
            'pos' => [
                'key' => 'pos',
                'label' => 'Core Point of Sale',
                'label_ar' => 'نقطة البيع الأساسية',
                'description' => 'Direct ordering, item catalog, modifiers, and payment handling',
                'description_ar' => 'استقبال الطلبات، الكتالوج، الملاحظات، وتسجيل المدفوعات',
                'category' => 'operations',
                'implementation_status' => 'implemented',
                'sellable_now' => true,
            ],
            'kds' => [
                'key' => 'kds',
                'label' => 'Kitchen Display System',
                'label_ar' => 'شاشات المطبخ الرقمية',
                'description' => 'Real-time kitchen tickets, prep timers, station routing, and kitchen screen advances',
                'description_ar' => 'تذاكر المطبخ اللحظية، محطات التجهيز، وتتبع زمن التحضير',
                'category' => 'operations',
                'implementation_status' => 'implemented',
                'sellable_now' => true,
            ],
            'tables' => [
                'key' => 'tables',
                'label' => 'Dining Table Management',
                'label_ar' => 'إدارة الصالة والطاولات',
                'description' => 'Dine-in table floor plans, open checks, check splitting, and table transfers',
                'description_ar' => 'مخطط الصالة، فتح الحسابات، تجزئة الشيكات، ونقل الطاولات',
                'category' => 'operations',
                'implementation_status' => 'implemented',
                'sellable_now' => true,
            ],
            'delivery' => [
                'key' => 'delivery',
                'label' => 'Delivery Management',
                'label_ar' => 'إدارة طلبات التوصيل',
                'description' => 'Customer address books, delivery dispatcher, and driver assignments',
                'description_ar' => 'عناوين العملاء، توجيه الطلبات، وتعيين كباتن التوصيل',
                'category' => 'operations',
                'implementation_status' => 'implemented',
                'sellable_now' => true,
            ],
            'inventory' => [
                'key' => 'inventory',
                'label' => 'Inventory & Cost Control',
                'label_ar' => 'المخزون والتكاليف',
                'description' => 'Stock balances, recipes/BOM, waste logging, purchase receipts, and audits',
                'description_ar' => 'أرصدة الأصناف، وصفات المنتجات، تسجيل الهالك، واستلام الشراء',
                'category' => 'inventory',
                'implementation_status' => 'implemented',
                'sellable_now' => true,
            ],
            'employees' => [
                'key' => 'employees',
                'label' => 'Staff & Attendance',
                'label_ar' => 'الموظفون وسجلات الحضور',
                'description' => 'Staff profiles, PIN management, clock-in/out tracking, and attendance audit',
                'description_ar' => 'ملفات الموظفين، إدارة PIN، تسجيل الحضور والانصراف، والتدقيق',
                'category' => 'staff',
                'implementation_status' => 'implemented',
                'sellable_now' => true,
            ],
            'payroll' => [
                'key' => 'payroll',
                'label' => 'Payroll & Advances',
                'label_ar' => 'الرواتب والسلف',
                'description' => 'Salary period calculation, staff cash advances, installments, and payment ledger',
                'description_ar' => 'احتساب فترات الأجور، سلف الموظفين، أقساط السداد، والصرف',
                'category' => 'staff',
                'implementation_status' => 'partial',
                'sellable_now' => false,
            ],
            'menu_builder' => [
                'key' => 'menu_builder',
                'label' => 'Visual Menu Builder',
                'label_ar' => 'منشئ القوائم والتصميم',
                'description' => 'Classic and modern price boards, section customization, and printable menu generation',
                'description_ar' => 'تصميم بورد الأسعار والقوائم المصورة، وتخصيص الأقسام للطباعة',
                'category' => 'catalog',
                'implementation_status' => 'implemented',
                'sellable_now' => true,
            ],
            'advanced_reports' => [
                'key' => 'advanced_reports',
                'label' => 'Advanced Analytics & Reports',
                'label_ar' => 'التقارير المتقدمة والتحليلات',
                'description' => 'Product mix, shift reconciliation, discount analytics, hourly trends, and financial exports',
                'description_ar' => 'مبيعات الأصناف، تسوية الورديات، تحليلات الخصومات، والتصدير المالي',
                'category' => 'analytics',
                'implementation_status' => 'implemented',
                'sellable_now' => true,
            ],
            'promotions' => [
                'key' => 'promotions',
                'label' => 'Promotions & Combo Bundles',
                'label_ar' => 'العروض الترويجية والكومبو',
                'description' => 'Rules engine, promo codes, Buy-X-Get-Y, bundle meals, and automatic discounts',
                'description_ar' => 'محرك العروض، أكواد الخصم، باقات الوجبات، والتخفيضات التلقائية',
                'category' => 'marketing',
                'implementation_status' => 'implemented',
                'sellable_now' => true,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function validKeys(): array
    {
        return array_keys($this->catalog());
    }

    public function isValidKey(string $key): bool
    {
        return array_key_exists($key, $this->catalog());
    }

    /**
     * Normalize feature entitlements dictionary, ensuring unknown keys fail closed.
     *
     * @return array<string, bool>
     */
    public function normalize(mixed $features): array
    {
        $catalog = $this->catalog();
        $defaults = config('commercial.default_entitlements', []);

        // Start with all catalog keys initialized to their configured default or false
        $result = [];
        foreach ($catalog as $key => $meta) {
            $result[$key] = (bool) ($defaults[$key] ?? false);
        }

        if (! is_array($features)) {
            return $result;
        }

        if (array_is_list($features)) {
            $features = array_fill_keys(array_map('strval', $features), true);
        }

        foreach ($features as $key => $enabled) {
            $keyStr = (string) $key;
            if ($this->isValidKey($keyStr)) {
                $result[$keyStr] = (bool) $enabled;
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $verification
     */
    public function allows(array $verification, string $feature): bool
    {
        if (! $this->isValidKey($feature)) {
            return false;
        }

        return in_array($verification['state'] ?? null, [LicenseLeaseVerifier::ACTIVE, LicenseLeaseVerifier::GRACE], true)
            && ($verification['claims']['entitlements'][$feature] ?? false) === true;
    }
}
