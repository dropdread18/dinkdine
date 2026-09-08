@extends('layouts.app', ['title' => 'Payments'])

@section('content')
    <x-page-header title="Payments" />

    <form method="GET" action="{{ route('manage.payments.index') }}" class="flex flex-wrap gap-2 mb-6 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search customer, phone, email, or booking #"
               class="rounded-lg border-slate-300 shadow-sm w-64 focus:border-blue-500 focus:ring-blue-500">

        <select name="status" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Any Status</option>
            @foreach (\App\Enums\PaymentStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <x-button type="submit">Filter</x-button>
        <x-button tag="a" href="{{ route('manage.payments.index') }}" variant="ghost" class="self-center">Clear</x-button>
    </form>

    @if ($payments->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No payments match these filters.</x-card>
    @else
        <form method="POST" action="{{ route('manage.payments.bulk-mark-paid') }}" id="bulk-form"
              x-data="{
                  count: 0,
                  refreshCount() { this.count = document.querySelectorAll('.payment-checkbox:checked').length; },
                  toggleAll(checked) {
                      document.querySelectorAll('.payment-checkbox').forEach(cb => cb.checked = checked);
                      this.refreshCount();
                  },
                  toggleGroup(el, checked) {
                      // Sibling <tr> rows can't share a wrapping element in
                      // a <table>, so group membership is matched by
                      // comparing each row's data-group-key value directly
                      // (not .closest(), which would only ever find the
                      // single row the checkbox itself sits in) - plain
                      // string equality, not a CSS attribute selector, so a
                      // reference number containing a quote character can't
                      // break anything.
                      const key = el.closest('tr').dataset.groupKey;
                      document.querySelectorAll('.payment-checkbox').forEach(cb => {
                          if (cb.closest('tr').dataset.groupKey === key) cb.checked = checked;
                      });
                      this.refreshCount();
                  },
                  confirmDelete() {
                      if (! confirm(`Permanently delete ${this.count} booking(s) and their payment records? This cannot be undone.`)) {
                          return false;
                      }
                      this.$root.action = '{{ route('manage.payments.bulk-delete') }}';
                      this.$root.querySelector('[name=_method]').value = 'DELETE';
                      return true;
                  },
              }"
              @change="refreshCount()">
            @csrf
            <input type="hidden" name="_method" value="PATCH">

            <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="py-3 pl-4 pr-2">
                                <input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded border-slate-300">
                            </th>
                            <th class="text-left font-medium text-slate-500 py-3 pr-4">Booking</th>
                            <th class="text-left font-medium text-slate-500 py-3 pr-4">Customer</th>
                            <th class="text-left font-medium text-slate-500 py-3 pr-4">Amount</th>
                            <th class="text-left font-medium text-slate-500 py-3 pr-4">Method</th>
                            <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                            <th class="text-left font-medium text-slate-500 py-3 pr-4">Paid At</th>
                            <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $groupKey => $groupPayments)
                            @php $isGroup = $groupPayments->count() > 1; @endphp
                            @if ($isGroup)
                                <tr class="border-t border-slate-200 bg-blue-50/40" data-group-key="{{ $groupKey }}">
                                    <td class="py-2.5 pl-4 pr-2">
                                        <input type="checkbox" @change="toggleGroup($el, $event.target.checked)" class="rounded border-slate-300">
                                    </td>
                                    <td class="py-2.5 pr-4 text-slate-500" colspan="2">
                                        <span class="font-semibold text-slate-900">{{ $groupPayments->first()->booking->user->name }}</span>
                                        <span class="text-xs text-blue-700 font-semibold ml-1">{{ $groupPayments->count() }} bookings, same payment</span>
                                    </td>
                                    <td class="py-2.5 pr-4 text-slate-900 font-semibold">₱{{ number_format($groupPayments->sum('amount'), 2) }}</td>
                                    <td class="py-2.5 pr-4 text-slate-500" colspan="3">Ref: {{ $groupPayments->first()->reference_number }}</td>
                                    <td class="py-2.5 pr-4"></td>
                                </tr>
                            @endif
                            @foreach ($groupPayments as $payment)
                                <tr class="border-t border-slate-100 hover:bg-slate-50/60 {{ $isGroup ? 'bg-blue-50/15' : '' }}" @if ($isGroup) data-group-key="{{ $groupKey }}" @endif>
                                    <td class="py-3 pl-4 pr-2 {{ $isGroup ? 'pl-8' : '' }}">
                                        <input type="checkbox" name="payment_ids[]" value="{{ $payment->id }}" class="payment-checkbox rounded border-slate-300">
                                    </td>
                                    <td class="py-3 pr-4 text-slate-500">PB-{{ $payment->booking->id }}</td>
                                    <td class="py-3 pr-4 text-slate-900 font-medium">{{ $isGroup ? $payment->booking->court->name.' · '.\Illuminate\Support\Carbon::createFromFormat('H:i:s', $payment->booking->start_time)->format('g:i A') : $payment->booking->user->name }}</td>
                                    <td class="py-3 pr-4 text-slate-600">₱{{ number_format($payment->amount, 2) }}</td>
                                    <td class="py-3 pr-4 text-slate-600">{{ $payment->method ?: '—' }}</td>
                                    <td class="py-3 pr-4 text-slate-600">{{ $payment->status->label() }}</td>
                                    <td class="py-3 pr-4 text-slate-600 whitespace-nowrap">{{ $payment->paid_at?->format('M j, Y g:i A') ?: '—' }}</td>
                                    <td class="py-3 pr-4"><a href="{{ route('bookings.show', $payment->booking) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">View</a></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $payments->links() }}</div>

            <div x-show="count > 0" x-cloak
                 class="fixed bottom-0 left-0 right-0 lg:left-[240px] px-5 py-4 flex flex-wrap items-center gap-3"
                 style="background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -8px 24px rgba(15,23,42,0.08);">
                <span class="text-sm font-semibold text-slate-700" x-text="count + ' selected'"></span>

                <select name="method" required class="rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="cash">Cash</option>
                    <option value="gcash">GCash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="other">Other</option>
                </select>
                <input type="text" name="notes" placeholder="Notes (optional)" class="rounded-lg border-slate-300 shadow-sm text-sm w-48 focus:border-blue-500 focus:ring-blue-500">

                <x-button type="submit">Approve Selected</x-button>

                @if ($canBulkDelete)
                    <x-button type="submit" variant="danger" @click="if (! confirmDelete()) $event.preventDefault()">Delete Selected</x-button>
                @endif
            </div>
        </form>
    @endif
@endsection
