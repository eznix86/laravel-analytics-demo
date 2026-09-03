<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Database\Seeder;

class LedgerSeeder extends Seeder
{
    public function run(): void
    {
        Account::query()->insert([
            ['id' => 1, 'code' => '1000', 'name' => 'Cash', 'type' => 'asset'],
            ['id' => 2, 'code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset'],
            ['id' => 3, 'code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['id' => 4, 'code' => '3000', 'name' => 'Owner Equity', 'type' => 'equity'],
            ['id' => 5, 'code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue'],
            ['id' => 6, 'code' => '5000', 'name' => 'Rent Expense', 'type' => 'expense'],
        ]);

        JournalEntry::query()->insert([
            ['id' => 1, 'txn_date' => '2026-01-05', 'description' => 'Owner capital injection', 'posted' => true],
            ['id' => 2, 'txn_date' => '2026-01-10', 'description' => 'Invoice ACME', 'posted' => true],
            ['id' => 3, 'txn_date' => '2026-01-15', 'description' => 'January rent', 'posted' => true],
            ['id' => 4, 'txn_date' => '2026-01-20', 'description' => 'ACME settles invoice', 'posted' => true],
            ['id' => 5, 'txn_date' => '2026-02-01', 'description' => 'Draft: February rent', 'posted' => false],
        ]);

        JournalLine::query()->insert([
            ['journal_entry_id' => 1, 'account_id' => 1, 'debit' => 500_000, 'credit' => 0],
            ['journal_entry_id' => 1, 'account_id' => 4, 'debit' => 0, 'credit' => 500_000],

            ['journal_entry_id' => 2, 'account_id' => 2, 'debit' => 120_000, 'credit' => 0],
            ['journal_entry_id' => 2, 'account_id' => 5, 'debit' => 0, 'credit' => 120_000],

            ['journal_entry_id' => 3, 'account_id' => 6, 'debit' => 45_000, 'credit' => 0],
            ['journal_entry_id' => 3, 'account_id' => 1, 'debit' => 0, 'credit' => 45_000],

            ['journal_entry_id' => 4, 'account_id' => 1, 'debit' => 120_000, 'credit' => 0],
            ['journal_entry_id' => 4, 'account_id' => 2, 'debit' => 0, 'credit' => 120_000],

            ['journal_entry_id' => 5, 'account_id' => 6, 'debit' => 99_999, 'credit' => 0],
            ['journal_entry_id' => 5, 'account_id' => 1, 'debit' => 0, 'credit' => 99_999],
        ]);
    }
}
