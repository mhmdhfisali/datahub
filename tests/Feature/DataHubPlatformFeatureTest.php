<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\VisualQueryBuilder;
use App\Filament\Resources\Datasets\DatasetResource;
use App\Filament\Resources\Datasets\Pages\CreateDataset;
use App\Filament\Resources\Datasets\Pages\ListDatasets;
use App\Filament\Widgets\QuickActionsWidget;
use App\Models\Dataset;
use App\Models\User;
use App\Services\DataHealthProfiler;
use App\Services\DatasetParserService;
use App\Services\DatasetService;
use App\Services\DataTransformerService;
use App\Services\PiiMaskingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DataHubPlatformFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_v1_datasets_list(): void
    {
        Dataset::create([
            'title' => 'Test Ingestion Dataset',
            'category' => 'Analytics',
            'payload' => [
                ['name' => 'Alice', 'role' => 'Engineer'],
                ['name' => 'Bob', 'role' => 'Designer'],
            ],
            'record_count' => 2,
            'column_count' => 2,
            'health_score' => 100,
        ]);

        $response = $this->getJson(route('api.v1.datasets.index'));
        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_api_v1_dataset_data_query(): void
    {
        $dataset = Dataset::create([
            'title' => 'Sample Customers',
            'category' => 'Sales',
            'payload' => [
                ['name' => 'Charlie', 'email' => 'charlie@example.com'],
                ['name' => 'David', 'email' => 'david@example.com'],
            ],
            'record_count' => 2,
            'column_count' => 2,
            'health_score' => 100,
        ]);

        $response = $this->getJson(route('api.v1.datasets.data', ['id' => $dataset->id, 'search' => 'david']));
        $response->assertStatus(200)
            ->assertJsonPath('total_records', 1)
            ->assertJsonPath('records.0.name', 'David');
    }

    public function test_data_health_profiler(): void
    {
        $profiler = new DataHealthProfiler;
        $payload = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'John', 'age' => 30], // duplicate
            ['name' => 'Jane', 'age' => '-'], // missing
        ];

        $report = $profiler->profile($payload);

        $this->assertLessThan(100, $report['score']);
        $this->assertEquals(1, $report['duplicate_rows_count']);
        $this->assertNotEmpty($report['warnings']);
    }

    public function test_data_transformer_suite(): void
    {
        $transformer = new DataTransformerService;
        $payload = [
            ['name' => '  John  ', 'age' => '30'],
            ['name' => '  John  ', 'age' => '30'],
            ['name' => 'Jane', 'age' => '-'],
        ];

        // 1. Remove duplicates
        $dedup = $transformer->removeDuplicates($payload);
        $this->assertCount(2, $dedup['rows']);
        $this->assertEquals(1, $dedup['removed_count']);

        // 2. Trim whitespace
        $trimmed = $transformer->trimWhitespace($dedup['rows']);
        $this->assertEquals('John', $trimmed['rows'][0]['name']);

        // 3. Fill missing
        $filled = $transformer->fillMissingValues($trimmed['rows'], 'N/A');
        $this->assertEquals('N/A', $filled['rows'][1]['age']);
    }

    public function test_api_v1_openapi_spec_endpoint(): void
    {
        $response = $this->getJson(route('api.v1.openapi'));
        $response->assertStatus(200)
            ->assertJsonPath('openapi', '3.0.0')
            ->assertJsonStructure([
                'openapi',
                'info' => ['title', 'version', 'description'],
                'servers',
                'paths' => [
                    '/datasets',
                    '/datasets/{id}',
                    '/datasets/{id}/data',
                    '/tokens/create',
                ],
            ]);
    }

    public function test_pii_masking_service(): void
    {
        $masker = new PiiMaskingService;
        $payload = [
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@company.co.id',
                'phone' => '081234567890',
                'nik' => '3201011234560001',
            ],
        ];

        $res = $masker->mask($payload);
        $maskedRow = $res['rows'][0];

        $this->assertNotEquals('budi.santoso@company.co.id', $maskedRow['email']);
        $this->assertStringContainsString('***', $maskedRow['email']);
        $this->assertStringContainsString('***', $maskedRow['phone']);
        $this->assertStringContainsString('***', $maskedRow['nik']);
    }

    public function test_dataset_view_page_renders_custom_components(): void
    {
        $user = User::factory()->create();
        $dataset = Dataset::create([
            'title' => 'Customer Demographic Records',
            'category' => 'Demographics',
            'file_type' => 'xlsx',
            'payload' => [
                ['nik' => '3201010101', 'nama' => 'Budi Santoso', 'kota' => 'Jakarta'],
                ['nik' => '3201010102', 'nama' => 'Siti Nurhaliza', 'kota' => 'Bandung'],
            ],
            'record_count' => 2,
            'column_count' => 3,
            'health_score' => 95,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(DatasetResource::getUrl('view', ['record' => $dataset]));
        $response->assertStatus(200)
            ->assertSee('Customer Demographic Records')
            ->assertSee('Dataset Overview')
            ->assertSee('Data Quality Health Score')
            ->assertSee('Interactive Payload Inspector')
            ->assertSee('Budi Santoso');
    }

    public function test_dashboard_page_renders_quick_actions_and_widgets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('filament.admin.pages.dashboard'));
        $response->assertStatus(200);

        Livewire::actingAs($user)
            ->test(QuickActionsWidget::class)
            ->assertSee('Quick Command')
            ->assertSee('Upload Dataset')
            ->assertSee('Paste JSON')
            ->assertSee('Export Catalog')
            ->assertSee('Generate API Key')
            ->assertSee('Run AI Insights');
    }

    public function test_visualizer_page_renders_chart_canvas(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(VisualQueryBuilder::getUrl());
        $response->assertStatus(200)
            ->assertSee('Dataset Query Playground', false)
            ->assertSee('Live Rendered Preview', false)
            ->assertSee('visualizerCanvas', false);
    }

    public function test_dataset_create_form_renders_with_file_upload_and_sections(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(DatasetResource::getUrl('create'));
        $response->assertStatus(200)
            ->assertSee('Informasi &amp; Metadata Dataset', false)
            ->assertSee('Impor Data Spreadsheet / JSON', false)
            ->assertSee('Upload File Excel / CSV', false);
    }

    public function test_dataset_can_be_created_with_json_payload(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateDataset::class)
            ->fillForm([
                'title' => 'Test Sales Pipeline',
                'category' => 'Revenue',
                'payload' => json_encode([
                    ['deal_id' => 'D001', 'amount' => 5000, 'stage' => 'Closed Won'],
                    ['deal_id' => 'D002', 'amount' => 7500, 'stage' => 'Negotiation'],
                ]),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('datasets', [
            'title' => 'Test Sales Pipeline',
            'record_count' => 2,
        ]);
    }

    public function test_dataset_can_be_created_with_csv_file_upload(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $csvContent = "id,nama,role\n1,Alice,Engineer\n2,Bob,Product Manager";
        $file = UploadedFile::fake()->createWithContent('team.csv', $csvContent);

        Livewire::actingAs($user)
            ->test(CreateDataset::class)
            ->fillForm([
                'title' => 'Team Roster CSV',
                'category' => 'HR',
                'excel_file' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('datasets', [
            'title' => 'Team Roster CSV',
            'record_count' => 2,
        ]);
    }

    public function test_dataset_service_lifecycle_and_version_rollback(): void
    {
        $user = User::factory()->create();
        $service = app(DatasetService::class);

        // 1. Ingest
        $formData = [
            'title' => 'Service Ingested Dataset',
            'category' => 'Finance',
            'payload' => json_encode([
                ['trx_id' => 'TX100', 'amount' => 500],
                ['trx_id' => 'TX101', 'amount' => 750],
            ]),
        ];

        $mutated = $service->ingestFormData($formData, $user->id);
        $this->assertEquals(2, $mutated['record_count']);
        $this->assertEquals(100, $mutated['health_score']);

        $dataset = Dataset::create(array_merge($formData, $mutated));
        $service->handleCreated($dataset, $user->id);

        $this->assertDatabaseHas('dataset_versions', [
            'dataset_id' => $dataset->id,
            'version_number' => 1,
            'record_count' => 2,
        ]);

        // 2. Update Payload
        $newPayload = [
            ['trx_id' => 'TX100', 'amount' => 500],
            ['trx_id' => 'TX101', 'amount' => 750],
            ['trx_id' => 'TX102', 'amount' => 1200],
        ];

        $updated = $service->updatePayload($dataset, $newPayload, 'Appended TX102', $user->id);
        $this->assertEquals(2, $updated->current_version);
        $this->assertEquals(3, $updated->record_count);

        $this->assertDatabaseHas('dataset_versions', [
            'dataset_id' => $dataset->id,
            'version_number' => 2,
            'record_count' => 3,
        ]);

        // 3. Rollback Version
        $rolledBack = $service->rollbackVersion($updated, 1, $user->id);
        $this->assertEquals(3, $rolledBack->current_version);
        $this->assertEquals(2, $rolledBack->record_count);
    }

    public function test_dataset_parser_preserves_column_order_and_normalizes_duplicate_headers(): void
    {
        Storage::fake('local');
        $csvContent = "No,Nama Lengkap (Sesuai KTP),Kota Asal,Nama Lengkap (Sesuai KTP),Status\n1,Budi Santoso,Jakarta,Budi,Aktif\n2,Siti Aminah,Bandung,Siti,Nonaktif";
        $file = UploadedFile::fake()->createWithContent('peserta.csv', $csvContent);

        $parser = app(DatasetParserService::class);
        $result = $parser->parse($file);

        $this->assertEquals(2, $result['record_count']);
        $this->assertEquals(5, $result['column_count']);

        $row1 = $result['payload'][0];
        $keys = array_keys($row1);

        // Memastikan urutan kolom A sampai E dipertahankan 100% dan duplikat diberi penanda _1
        $this->assertEquals(['no', 'nama_lengkap', 'kota_asal', 'nama_lengkap_1', 'status'], $keys);
        $this->assertEquals('Budi Santoso', $row1['nama_lengkap']);
        $this->assertEquals('Budi', $row1['nama_lengkap_1']);
        $this->assertEquals('Jakarta', $row1['kota_asal']);

        // Verifikasi pada View Payload bahwa kolom 'no' disaring dari loop agar tidak double numbering dengan kolom '#'
        $user = User::factory()->create();
        $dataset = Dataset::create([
            'title' => 'Daftar Peserta',
            'category' => 'Event',
            'payload' => $result['payload'],
            'record_count' => 2,
            'column_count' => 5,
            'health_score' => 100,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(DatasetResource::getUrl('view', ['record' => $dataset]));
        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
        $response->assertSee('Interactive Payload Inspector');
    }

    public function test_dataset_parser_auto_detects_headers_and_strips_empty_columns(): void
    {
        $filePath = storage_path('app/private/excel-imports/Data Peserta PAC Duren Sawit.xlsx');
        $this->assertFileExists($filePath);

        $parser = app(DatasetParserService::class);
        $result = $parser->parse($filePath);

        $this->assertEquals(56, $result['record_count']);
        $this->assertEquals(4, $result['column_count']);

        $row1 = $result['payload'][0];
        $this->assertEquals(['no', 'nama', 'alamat', 'no_hp'], array_keys($row1));
        $this->assertEquals('Andi Waluyo', $row1['nama']);
        $this->assertEquals('Duren Sawit', $row1['alamat']);
        $this->assertEquals('081510216571', $row1['no_hp']);
    }

    public function test_trash_tab_displays_soft_deleted_datasets(): void
    {
        $user = User::factory()->create();

        $activeDataset = Dataset::create([
            'title' => 'Active Dataset Alpha',
            'category' => 'Testing',
            'payload' => [['col' => 'val1']],
            'record_count' => 1,
            'column_count' => 1,
            'health_score' => 100,
            'user_id' => $user->id,
        ]);

        $trashedDataset = Dataset::create([
            'title' => 'Deleted Dataset Item',
            'category' => 'Testing',
            'payload' => [['col' => 'val2']],
            'record_count' => 1,
            'column_count' => 1,
            'health_score' => 100,
            'user_id' => $user->id,
        ]);
        $trashedDataset->delete();

        Livewire::actingAs($user)
            ->test(ListDatasets::class)
            ->assertSee('Active Dataset Alpha')
            ->assertDontSee('Deleted Dataset Item')
            ->set('activeTab', 'trash')
            ->assertSee('Deleted Dataset Item')
            ->assertDontSee('Active Dataset Alpha');
    }

    public function test_custom_login_page_renders_and_authenticates(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@enterprise.io',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->get(route('filament.admin.auth.login'));
        $response->assertStatus(200)
            ->assertSee('Unified Enterprise')
            ->assertSee('Data Intelligence')
            ->assertSee('Masuk ke Akun Anda');

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'admin@enterprise.io',
                'password' => 'wrong-pass',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'admin@enterprise.io',
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }
}
