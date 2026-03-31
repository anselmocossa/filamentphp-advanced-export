<?php

/**
 * Tests for HasExportQuery trait.
 *
 * CRITICAL FIX #2: applyCustomOrdering must validate that $orderColumn
 * is a valid column on the model or in the exportColumns list.
 * Dot notation columns (relationships) should be handled gracefully.
 *
 * CRITICAL FIX #3: $orderDirection must be validated to only accept 'asc' or 'desc'.
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Create a test model for these tests
beforeEach(function () {
    Schema::create('test_export_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_export_models');
});

// --- CRITICAL #2: applyCustomOrdering validates column ---

test('applyCustomOrdering rejects invalid column names', function () {
    $model = new class extends Model
    {
        protected $table = 'test_export_models';

        protected $guarded = [];
    };

    $query = $model->newQuery();

    // Create a test class that uses the trait
    $exporter = new class
    {
        use \Filament\AdvancedExport\Concerns\HasExportQuery;

        public array $exportColumns = [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'created_at' => 'Created At',
        ];

        public function testApplyOrdering($query, $column, $direction): void
        {
            $this->applyCustomOrdering($query, $column, $direction);
        }

        protected function getExportColumns(): array
        {
            return $this->exportColumns;
        }
    };

    // SQL injection attempt - should not be applied
    $query2 = $model->newQuery();
    $exporter->testApplyOrdering($query2, 'name; DROP TABLE users;--', 'asc');

    // The query should NOT contain the malicious column
    $sql = $query2->toSql();
    expect($sql)->not->toContain('DROP TABLE');
});

test('applyCustomOrdering accepts valid columns that exist on the table', function () {
    $model = new class extends Model
    {
        protected $table = 'test_export_models';

        protected $guarded = [];
    };

    $query = $model->newQuery();

    $exporter = new class
    {
        use \Filament\AdvancedExport\Concerns\HasExportQuery;

        public function testApplyOrdering($query, $column, $direction): void
        {
            $this->applyCustomOrdering($query, $column, $direction);
        }

        protected function getExportColumns(): array
        {
            return ['id' => 'ID', 'name' => 'Name', 'created_at' => 'Created At'];
        }
    };

    // Valid column should be applied
    $exporter->testApplyOrdering($query, 'name', 'asc');
    $sql = $query->toSql();
    expect($sql)->toContain('order by');
});

test('applyCustomOrdering handles dot notation columns gracefully', function () {
    $model = new class extends Model
    {
        protected $table = 'test_export_models';

        protected $guarded = [];
    };

    $query = $model->newQuery();

    $exporter = new class
    {
        use \Filament\AdvancedExport\Concerns\HasExportQuery;

        public function testApplyOrdering($query, $column, $direction): void
        {
            $this->applyCustomOrdering($query, $column, $direction);
        }

        protected function getExportColumns(): array
        {
            return [
                'id' => 'ID',
                'client.name' => 'Client Name',
            ];
        }
    };

    // Dot notation column should be ignored gracefully (no exception)
    $exporter->testApplyOrdering($query, 'client.name', 'asc');

    // Should not throw an exception - test passes if we reach here
    expect(true)->toBeTrue();
});

// --- CRITICAL #3: orderDirection must be validated ---

test('orderDirection only accepts asc or desc', function () {
    $model = new class extends Model
    {
        protected $table = 'test_export_models';

        protected $guarded = [];
    };

    $query = $model->newQuery();

    $exporter = new class
    {
        use \Filament\AdvancedExport\Concerns\HasExportQuery;

        public function testApplyOrdering($query, $column, $direction): void
        {
            $this->applyCustomOrdering($query, $column, $direction);
        }

        protected function getExportColumns(): array
        {
            return ['name' => 'Name'];
        }
    };

    // Invalid direction should fallback to 'desc'
    $exporter->testApplyOrdering($query, 'name', 'INVALID');
    $sql = $query->toSql();

    // Should not contain the invalid direction
    expect($sql)->not->toContain('INVALID');
});

test('orderDirection accepts asc case-insensitively', function () {
    $model = new class extends Model
    {
        protected $table = 'test_export_models';

        protected $guarded = [];
    };

    $query = $model->newQuery();

    $exporter = new class
    {
        use \Filament\AdvancedExport\Concerns\HasExportQuery;

        public function testApplyOrdering($query, $column, $direction): void
        {
            $this->applyCustomOrdering($query, $column, $direction);
        }

        protected function getExportColumns(): array
        {
            return ['name' => 'Name'];
        }
    };

    // 'ASC' should be normalized to 'asc'
    $exporter->testApplyOrdering($query, 'name', 'ASC');
    $sql = $query->toSql();
    expect($sql)->toContain('order by');
});
