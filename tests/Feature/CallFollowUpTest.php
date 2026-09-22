<?php

namespace Tests\Feature;

use App\Enums\CallStatus;
use App\Enums\NoPurchaseReason;
use App\Enums\UserRole;
use App\Filament\Resources\Calls\Pages\ListCalls;
use App\Models\Call;
use App\Models\Customer;
use App\Models\SalesAgent;
use App\Models\User;
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
            'notes' => 'قیمت رقیب ارزان‌تر بود',
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

    public function test_secretaries_cannot_open_manager_pages(): void
    {
        $this->actingAs($this->secretary());

        $this->get('/sales-agents')->assertForbidden();
        $this->get('/users')->assertForbidden();
        $this->get('/calls')->assertOk();
    }
}
