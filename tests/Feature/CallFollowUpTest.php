<?php

namespace Tests\Feature;

use App\Enums\AcquisitionSource;
use App\Enums\CallStatus;
use App\Enums\CustomerType;
use App\Enums\NoPurchaseReason;
use App\Enums\UserRole;
use App\Filament\Resources\Calls\Pages\CreateCall;
use App\Filament\Resources\Calls\Pages\ListCalls;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Widgets\AcquisitionSourceChart;
use App\Filament\Widgets\ReportOverview;
use App\Models\Call;
use App\Models\Customer;
use App\Models\SalesAgent;
use App\Models\User;
use App\Support\Persian;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CallFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private function makeCall(array $overrides = []): Call
    {
        static $n = 0;
        $customer = Customer::create(['name' => 'علی محمدی', 'phone' => '0912123456'.($n++ % 10)]);
        $agent = SalesAgent::create(['name' => 'آقای رضایی']);

        return Call::create(array_merge([
            'customer_id' => $customer->id,
            'sales_agent_id' => $agent->id,
            'request' => 'دستمال کاغذی',
            'follow_up_on' => today(),
        ], $overrides));
    }

    private function secretary(): User
    {
        return User::create(['name' => 'خانم مرادی', 'email' => 'moradi@test.local', 'password' => 'secret123', 'role' => UserRole::Secretary]);
    }

    public function test_the_next_working_day_skips_friday(): void
    {
        $thursday = Carbon::parse('2026-09-24'); // a Thursday

        $this->assertTrue($thursday->isThursday());
        $this->assertSame('2026-09-26', Call::workingDayAfter(1, $thursday)->toDateString()); // Saturday
        $this->assertSame('2026-09-23', Call::workingDayAfter(1, Carbon::parse('2026-09-22'))->toDateString());
    }

    public function test_phone_numbers_are_normalised(): void
    {
        foreach (['۰۹۱۲ ۱۲۳ ۴۵۶۷', '+989121234567', '00989121234567', '9121234567', '0912-123-4567'] as $typed) {
            $this->assertSame('09121234567', Customer::normalizePhone($typed), $typed);
        }
    }

    public function test_an_answered_follow_up_records_the_result_and_closes_the_call(): void
    {
        $call = $this->makeCall();

        $call->recordFollowUp([
            'answered' => true,
            'purchased' => false,
            'no_purchase_reason' => NoPurchaseReason::Price->value,
            'agent_satisfaction' => 4,
            'overall_satisfaction' => 3,
            'notes' => 'قیمت رقیب ارزان تر بود',
        ], $this->secretary());

        $call->refresh();
        $this->assertSame(CallStatus::Done, $call->status);
        $this->assertFalse($call->result->purchased);
        $this->assertSame(NoPurchaseReason::Price, $call->result->no_purchase_reason);
        $this->assertSame(4, $call->result->agent_satisfaction);
    }

    public function test_a_purchase_drops_any_no_purchase_reason(): void
    {
        $call = $this->makeCall();

        $call->recordFollowUp(['answered' => true, 'purchased' => true, 'no_purchase_reason' => 'price', 'agent_satisfaction' => 5, 'overall_satisfaction' => 5]);

        $this->assertNull($call->fresh()->result->no_purchase_reason);
    }

    public function test_calling_again_keeps_the_call_in_the_queue(): void
    {
        $call = $this->makeCall();

        $call->recordFollowUp(['answered' => true, 'purchased' => false, 'no_purchase_reason' => 'undecided', 'agent_satisfaction' => 4, 'overall_satisfaction' => 4], null, 3);

        $call->refresh();
        $this->assertSame(CallStatus::AwaitingFollowUp, $call->status);
        $this->assertTrue($call->follow_up_on->gte(today()->addDays(3)));
    }

    public function test_unanswered_calls_are_retried_then_closed_after_three_attempts(): void
    {
        $call = $this->makeCall();

        $call->recordFollowUp(['answered' => false]);
        $call->refresh();
        $this->assertSame(CallStatus::AwaitingFollowUp, $call->status);
        $this->assertTrue($call->follow_up_on->gt(today()));
        $this->assertNull($call->result);

        $call->recordFollowUp(['answered' => false]);
        $call->recordFollowUp(['answered' => false]);
        $call->refresh();
        $this->assertSame(CallStatus::Unreachable, $call->status);
        $this->assertSame(3, $call->followUps()->count());
    }

    public function test_due_calls_include_overdue_ones_only(): void
    {
        $this->makeCall(['follow_up_on' => today()->subDays(2)]);
        $done = $this->makeCall(['follow_up_on' => today()]);
        $done->update(['status' => CallStatus::Done]);

        $this->assertSame(1, Call::query()->dueBy(today())->count());
    }

    public function test_the_secretary_records_a_result_from_the_calls_table(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->secretary());
        $call = $this->makeCall();

        Livewire::test(ListCalls::class, ['activeTab' => 'today'])
            ->callTableAction('recordFollowUp', $call, [
                'answered' => true,
                'purchased' => true,
                'agent_satisfaction' => 5,
                'overall_satisfaction' => 5,
                'call_again_in' => 0,
            ])
            ->assertHasNoTableActionErrors();

        $call->refresh();
        $this->assertSame(CallStatus::Done, $call->status);
        $this->assertTrue($call->result->purchased);
        $this->assertSame(auth()->id(), $call->result->user_id);
    }

    public function test_the_new_call_form_saves_source_and_notes_and_requires_the_source(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->secretary());
        $customer = Customer::create(['name' => 'زهرا موسوی', 'phone' => '09125550000', 'type' => CustomerType::Individual]);
        $agent = SalesAgent::create(['name' => 'خانم کریمی']);

        Livewire::test(CreateCall::class)
            ->fillForm(['customer_id' => $customer->id, 'sales_agent_id' => $agent->id, 'request' => 'کاغذ A4', 'follow_up_in' => 1])
            ->call('create')
            ->assertHasFormErrors(['source' => 'required']);

        Livewire::test(CreateCall::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'sales_agent_id' => $agent->id,
                'request' => 'کاغذ A4',
                'source' => AcquisitionSource::Referral->value,
                'notes' => 'از طرف آقای رحیمی',
                'follow_up_in' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $call = Call::sole();
        $this->assertSame(AcquisitionSource::Referral, $call->source);
        $this->assertSame('از طرف آقای رحیمی', $call->notes);
    }

    public function test_a_legal_customer_needs_a_company_name(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->secretary());

        Livewire::test(CreateCustomer::class)
            ->fillForm(['name' => 'علی رضایی', 'phone' => '09127770000', 'type' => CustomerType::Legal->value])
            ->call('create')
            ->assertHasFormErrors(['company' => 'required']);

        Livewire::test(CreateCustomer::class)
            ->fillForm(['name' => 'علی رضایی', 'phone' => '۰۹۱۲۷۷۷۰۰۰۰', 'type' => CustomerType::Legal->value, 'company' => 'شرکت آریا'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(CustomerType::Legal, Customer::where('phone', '09127770000')->sole()->type);
    }

    public function test_the_source_and_customer_type_charts_count_purchases_correctly(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::create(['name' => 'مدیر', 'email' => 'm@test.local', 'password' => 'secret123', 'role' => UserRole::Manager]));

        // three referrals: two bought; three from the website: one bought
        foreach ([['referral', true], ['referral', true], ['referral', false], ['website', true], ['website', false], ['website', false]] as [$source, $bought]) {
            $call = $this->makeCall(['source' => $source]);
            $call->recordFollowUp(['answered' => true, 'purchased' => $bought, 'no_purchase_reason' => $bought ? null : 'price', 'agent_satisfaction' => 4, 'overall_satisfaction' => 4]);
        }

        Livewire::test(AcquisitionSourceChart::class)
            ->assertSee('بیشترین نرخ خرید: معرف (۶۷٪ از مشتریان پیگیری شده)')
            ->assertSee('سایت · ۵۰٪', false);
    }

    public function test_a_deleted_call_leaves_the_reports_but_can_be_brought_back(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::create(['name' => 'مدیر', 'email' => 'reports@test.local', 'password' => 'secret123', 'role' => UserRole::Manager]));

        $kept = $this->makeCall();
        $kept->recordFollowUp(['answered' => true, 'purchased' => true, 'agent_satisfaction' => 5, 'overall_satisfaction' => 5]);
        $deleted = $this->makeCall();
        $deleted->recordFollowUp(['answered' => true, 'purchased' => true, 'agent_satisfaction' => 5, 'overall_satisfaction' => 5]);

        Livewire::test(ReportOverview::class)->assertSee('۲ خرید از ۲ مشتری پیگیری شده', false);

        $deleted->delete();

        // gone from the lists and from the figures, still in the database
        $this->assertSame(1, Call::query()->count());
        $this->assertSame(2, Call::withTrashed()->count());
        Livewire::test(ReportOverview::class)->assertSee('۱ خرید از ۱ مشتری پیگیری شده', false);

        $deleted->restore();

        $this->assertSame(2, Call::query()->count());
        Livewire::test(ReportOverview::class)->assertSee('۲ خرید از ۲ مشتری پیگیری شده', false);
    }

    public function test_deleting_a_customer_hides_their_calls_and_restoring_brings_them_back(): void
    {
        $call = $this->makeCall();
        $customer = $call->customer;

        $customer->delete();

        $this->assertSame(0, Call::query()->count());
        $this->assertSame(0, Customer::query()->count());
        $this->assertNotNull(Call::withTrashed()->find($call->id)->deleted_at);

        $customer->restore();

        $this->assertSame(1, Call::query()->count());
        $this->assertSame(1, Customer::query()->count());
    }

    public function test_searching_the_calls_by_customer_name_narrows_the_list(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->secretary());

        $wanted = $this->makeCall();
        $wanted->customer->update(['name' => 'بهرام کریمی']);
        $other = $this->makeCall();
        $other->customer->update(['name' => 'سعید نوری']);

        Livewire::test(ListCalls::class, ['activeTab' => 'all'])
            ->searchTable('بهرام')
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$other]);

        // a number still finds its call, whatever way it is typed
        Livewire::test(ListCalls::class, ['activeTab' => 'all'])
            ->searchTable(Persian::digits($wanted->customer->phone))
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_a_manager_cannot_take_away_their_own_role(): void
    {
        Filament::setCurrentPanel('admin');
        $manager = User::create(['name' => 'مدیر', 'email' => 'boss@test.local', 'password' => 'secret123', 'role' => UserRole::Manager]);
        $this->actingAs($manager);

        Livewire::test(ManageUsers::class)
            ->mountTableAction('edit', $manager)
            ->setTableActionData(['role' => UserRole::Secretary->value, 'is_active' => false])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $manager->refresh();
        $this->assertSame(UserRole::Manager, $manager->role, 'a manager must not be able to demote themselves');
        $this->assertTrue($manager->is_active);
    }

    public function test_secretaries_cannot_open_manager_pages(): void
    {
        $this->actingAs($this->secretary());

        $this->get('/sales-agents')->assertForbidden();
        $this->get('/users')->assertForbidden();
        $this->get('/calls')->assertOk();
    }
}
