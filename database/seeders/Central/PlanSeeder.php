<?php

namespace Database\Seeders\Central;

use App\Models\Central\Plan;
use App\Models\Central\PlanFeature;
use App\Models\Central\PlanPrice;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Free Plan
        $freePlan = Plan::create([
            'code' => 'FREE',
            'name' => 'Free Plan',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'currency' => 'EGP',
            'is_active' => true,
        ]);

        // Free Plan Prices
        PlanPrice::create([
            'plan_id' => $freePlan->id,
            'cycle' => 'monthly',
            'price' => 0,
            'currency' => 'EGP',
        ]);

        PlanPrice::create([
            'plan_id' => $freePlan->id,
            'cycle' => 'yearly',
            'price' => 0,
            'currency' => 'EGP',
        ]);

        // Free Plan Features
        $freeFeatures = [
            'users' => '1',
            'leads' => '50',
            'contacts' => '100',
            'projects' => '1',
            'campaigns' => '0',
            'actions' => 'unlimited',
            'comments' => 'unlimited',
            'activity_logs' => 'limited',
            'project_stages' => '3',
            'roles' => '1',
            'search' => 'basic',
            'api_calls' => '1000',
            'storage_gb' => '1',
            'email_integration' => 'no',
            'sms_integration' => 'no',
            'automation' => 'no',
            'advanced_reporting' => 'no',
            'custom_fields' => '5',
            'bulk_operations' => 'no',
            'team_collaboration' => 'no',
        ];

        foreach ($freeFeatures as $key => $value) {
            PlanFeature::create([
                'plan_id' => $freePlan->id,
                'key' => $key,
                'value' => $value,
            ]);
        }

        // Basic Plan
        $basicPlan = Plan::create([
            'code' => 'BASIC',
            'name' => 'Basic Plan',
            'price' => 299,
            'billing_cycle' => 'monthly',
            'currency' => 'EGP',
            'is_active' => true,
        ]);

        // Basic Plan Prices
        PlanPrice::create([
            'plan_id' => $basicPlan->id,
            'cycle' => 'monthly',
            'price' => 299,
            'currency' => 'EGP',
        ]);

        PlanPrice::create([
            'plan_id' => $basicPlan->id,
            'cycle' => 'yearly',
            'price' => 2990,
            'currency' => 'EGP',
            'discount_percent' => 16.67, // ~2 months free
        ]);

        // Basic Plan Features
        $basicFeatures = [
            'users' => '3',
            'leads' => '500',
            'contacts' => '1000',
            'projects' => '5',
            'campaigns' => '2',
            'actions' => 'unlimited',
            'comments' => 'unlimited',
            'activity_logs' => 'unlimited',
            'project_stages' => '5',
            'roles' => '3',
            'search' => 'advanced',
            'api_calls' => '10000',
            'storage_gb' => '5',
            'email_integration' => 'yes',
            'sms_integration' => 'no',
            'automation' => 'basic',
            'advanced_reporting' => 'no',
            'custom_fields' => '20',
            'bulk_operations' => 'yes',
            'team_collaboration' => 'yes',
        ];

        foreach ($basicFeatures as $key => $value) {
            PlanFeature::create([
                'plan_id' => $basicPlan->id,
                'key' => $key,
                'value' => $value,
            ]);
        }

        // Business Plan
        $businessPlan = Plan::create([
            'code' => 'BUSINESS',
            'name' => 'Business Plan',
            'price' => 799,
            'billing_cycle' => 'monthly',
            'currency' => 'EGP',
            'is_active' => true,
        ]);

        // Business Plan Prices
        PlanPrice::create([
            'plan_id' => $businessPlan->id,
            'cycle' => 'monthly',
            'price' => 799,
            'currency' => 'EGP',
        ]);

        PlanPrice::create([
            'plan_id' => $businessPlan->id,
            'cycle' => 'yearly',
            'price' => 7990,
            'currency' => 'EGP',
            'discount_percent' => 16.67, // ~2 months free
        ]);

        // Business Plan Features
        $businessFeatures = [
            'users' => '15',
            'leads' => '5000',
            'contacts' => '10000',
            'projects' => '50',
            'campaigns' => '20',
            'actions' => 'unlimited',
            'comments' => 'unlimited',
            'activity_logs' => 'unlimited',
            'project_stages' => '10',
            'roles' => '10',
            'search' => 'advanced',
            'api_calls' => '100000',
            'storage_gb' => '50',
            'email_integration' => 'yes',
            'sms_integration' => 'yes',
            'automation' => 'advanced',
            'advanced_reporting' => 'yes',
            'custom_fields' => '100',
            'bulk_operations' => 'yes',
            'team_collaboration' => 'yes',
        ];

        foreach ($businessFeatures as $key => $value) {
            PlanFeature::create([
                'plan_id' => $businessPlan->id,
                'key' => $key,
                'value' => $value,
            ]);
        }

        // Enterprise Plan
        $enterprisePlan = Plan::create([
            'code' => 'ENTERPRISE',
            'name' => 'Enterprise Plan',
            'price' => 2499,
            'billing_cycle' => 'monthly',
            'currency' => 'EGP',
            'is_active' => true,
        ]);

        // Enterprise Plan Prices
        PlanPrice::create([
            'plan_id' => $enterprisePlan->id,
            'cycle' => 'monthly',
            'price' => 2499,
            'currency' => 'EGP',
        ]);

        PlanPrice::create([
            'plan_id' => $enterprisePlan->id,
            'cycle' => 'yearly',
            'price' => 24990,
            'currency' => 'EGP',
            'discount_percent' => 16.67, // ~2 months free
        ]);

        // Enterprise Plan Features
        $enterpriseFeatures = [
            'users' => 'unlimited',
            'leads' => 'unlimited',
            'contacts' => 'unlimited',
            'projects' => 'unlimited',
            'campaigns' => 'unlimited',
            'actions' => 'unlimited',
            'comments' => 'unlimited',
            'activity_logs' => 'unlimited',
            'project_stages' => 'unlimited',
            'roles' => 'unlimited',
            'search' => 'advanced',
            'api_calls' => 'unlimited',
            'storage_gb' => '500',
            'email_integration' => 'yes',
            'sms_integration' => 'yes',
            'automation' => 'advanced',
            'advanced_reporting' => 'yes',
            'custom_fields' => 'unlimited',
            'bulk_operations' => 'yes',
            'team_collaboration' => 'yes',
        ];

        foreach ($enterpriseFeatures as $key => $value) {
            PlanFeature::create([
                'plan_id' => $enterprisePlan->id,
                'key' => $key,
                'value' => $value,
            ]);
        }
    }
}
