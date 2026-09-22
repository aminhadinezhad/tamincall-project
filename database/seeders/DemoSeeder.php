<?php

namespace Database\Seeders;

use App\Enums\AcquisitionSource;
use App\Enums\CallStatus;
use App\Enums\CustomerType;
use App\Enums\NoPurchaseReason;
use App\Enums\UserRole;
use App\Models\Call;
use App\Models\Customer;
use App\Models\SalesAgent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * LOCAL ONLY: two sign-ins, four sales agents and two months of made-up calls, so the pages and
 * reports have something to show. Run with: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('DemoSeeder only runs on a local copy.');
        }

        $manager = User::updateOrCreate(['email' => 'manager@tamincall.local'], [
            'name' => 'مدیر نمونه', 'password' => 'Manager12345', 'role' => UserRole::Manager, 'is_active' => true,
        ]);
        $secretary = User::updateOrCreate(['email' => 'moradi@tamincall.local'], [
            'name' => 'خانم مرادی', 'password' => 'Moradi12345', 'role' => UserRole::Secretary, 'is_active' => true,
        ]);

        $agents = collect(['آقای رضایی', 'خانم کریمی', 'آقای احمدی', 'آقای نوری'])
            ->map(fn (string $name) => SalesAgent::firstOrCreate(['name' => $name]));

        $firstNames = ['علی', 'مریم', 'حسین', 'زهرا', 'محمد', 'فاطمه', 'رضا', 'سارا', 'مهدی', 'نرگس', 'امیر', 'لیلا'];
        $lastNames = ['محمدی', 'حسینی', 'رحیمی', 'موسوی', 'کاظمی', 'جعفری', 'صادقی', 'قاسمی', 'باقری', 'اکبری'];
        $companies = [null, 'شرکت پارس صنعت', null, 'بیمارستان آتیه', null, 'رستوران گلستان', null, null];
        $requests = [
            'استعلام قیمت دستمال کاغذی ۵۰ کارتن', 'خرید لیوان کاغذی چاپ دار', 'کاغذ A4 برای دفتر', 'مواد شوینده ی ماهانه ی شرکت',
            'اقلام پذیرایی برای همایش', 'چای و قهوه ی سازمانی', 'لوازم التحریر مدرسه', 'کیسه زباله ی صنعتی',
        ];

        // weighted, so the "how they found us" chart has a realistic shape
        $sources = [
            AcquisitionSource::Website->value => 30, AcquisitionSource::OnlineAds->value => 20, AcquisitionSource::BaleBot->value => 10,
            AcquisitionSource::Outdoor->value => 8, AcquisitionSource::AgentMarketing->value => 17, AcquisitionSource::Referral->value => 15,
        ];
        $pickSource = function () use ($sources): string {
            $roll = mt_rand(1, array_sum($sources));
            foreach ($sources as $source => $weight) {
                if (($roll -= $weight) <= 0) {
                    return $source;
                }
            }

            return AcquisitionSource::Website->value;
        };

        mt_srand(1405);

        for ($i = 0; $i < 60; $i++) {
            $company = $companies[$i % 8];
            $customer = Customer::firstOrCreate(
                ['phone' => '0912'.str_pad((string) (1000000 + $i * 7919 % 9000000), 7, '0', STR_PAD_LEFT)],
                [
                    'name' => $firstNames[$i % 12].' '.$lastNames[$i % 10],
                    'type' => $company ? CustomerType::Legal : CustomerType::Individual,
                    'company' => $company,
                ],
            );

            $receivedAt = Carbon::now()->subDays(mt_rand(0, 59))->setTime(mt_rand(8, 17), mt_rand(0, 59));
            $agent = $agents[mt_rand(0, 3)];

            $call = Call::create([
                'customer_id' => $customer->id,
                'sales_agent_id' => $agent->id,
                'received_by' => $secretary->id,
                'request' => $requests[mt_rand(0, 7)],
                'source' => $pickSource(),
                'follow_up_on' => Call::workingDayAfter(1, $receivedAt->copy()->startOfDay()),
            ]);
            $call->forceFill(['created_at' => $receivedAt, 'updated_at' => $receivedAt])->saveQuietly();

            // calls from the last two days are still waiting for their follow-up
            if ($receivedAt->gt(now()->subDays(2))) {
                continue;
            }

            if (mt_rand(1, 10) === 1) {
                foreach (range(1, Call::MAX_UNANSWERED_ATTEMPTS) as $attempt) {
                    $call->recordFollowUp(['answered' => false], $secretary);
                }

                continue;
            }

            // agents differ a little, so the report has something to compare
            $purchased = mt_rand(1, 100) <= [65, 50, 40, 55][$agents->search($agent)];
            $happy = $purchased ? mt_rand(3, 5) : mt_rand(1, 4);

            $call->recordFollowUp([
                'answered' => true,
                'purchased' => $purchased,
                'no_purchase_reason' => $purchased ? null : collect(NoPurchaseReason::cases())->random()->value,
                'agent_satisfaction' => min(5, $happy + mt_rand(0, 1)),
                'overall_satisfaction' => $happy,
                'notes' => null,
            ], $secretary);

            $call->followUps()->latest('id')->first()->forceFill([
                'created_at' => $receivedAt->copy()->addDay(), 'updated_at' => $receivedAt->copy()->addDay(),
            ])->saveQuietly();
        }

        $this->command?->info('Demo data ready. Sign in as manager@tamincall.local / Manager12345 or moradi@tamincall.local / Moradi12345');
        $this->command?->info('Calls: '.Call::count().', awaiting: '.Call::where('status', CallStatus::AwaitingFollowUp)->count());
    }
}
