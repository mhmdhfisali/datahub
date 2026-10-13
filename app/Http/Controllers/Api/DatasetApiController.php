<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatasetApiController extends Controller
{
    /**
     * List all datasets with metadata summary.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Dataset::query();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('search')) {
            $term = $request->query('search');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%");
            });
        }

        $datasets = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $datasets->items(),
            'pagination' => [
                'current_page' => $datasets->currentPage(),
                'per_page' => $datasets->perPage(),
                'total' => $datasets->total(),
                'last_page' => $datasets->lastPage(),
            ],
        ]);
    }

    /**
     * Get single dataset info and metadata.
     */
    public function show(int $id): JsonResponse
    {
        $dataset = Dataset::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $dataset->id,
                'title' => $dataset->title,
                'category' => $dataset->category,
                'record_count' => $dataset->record_count,
                'column_count' => $dataset->column_count,
                'health_score' => $dataset->health_score,
                'health_report' => $dataset->health_report,
                'file_type' => $dataset->file_type,
                'file_size_bytes' => $dataset->file_size_bytes,
                'current_version' => $dataset->current_version,
                'last_ingested_at' => $dataset->last_ingested_at,
                'created_at' => $dataset->created_at,
                'updated_at' => $dataset->updated_at,
            ],
        ]);
    }

    /**
     * Query dataset payload directly (GET /api/v1/datasets/{id}/data)
     */
    public function data(Request $request, int $id): JsonResponse
    {
        $dataset = Dataset::findOrFail($id);
        $payload = $dataset->payload ?? [];

        // In-memory filter on payload keys if query param present
        if ($request->filled('search')) {
            $search = strtolower($request->query('search'));
            $payload = array_values(array_filter($payload, function ($row) use ($search) {
                if (! is_array($row)) {
                    return false;
                }
                foreach ($row as $val) {
                    if (str_contains(strtolower((string) $val), $search)) {
                        return true;
                    }
                }

                return false;
            }));
        }

        $totalRecords = count($payload);
        $page = max(1, $request->integer('page', 1));
        $perPage = max(1, min(500, $request->integer('per_page', 50)));
        $offset = ($page - 1) * $perPage;

        $sliced = array_slice($payload, $offset, $perPage);

        ActivityLogger::log('api_query', "API queried dataset: {$dataset->title}", $dataset, [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        return response()->json([
            'status' => 'success',
            'dataset_id' => $dataset->id,
            'dataset_title' => $dataset->title,
            'total_records' => $totalRecords,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalRecords / $perPage),
            'records' => $sliced,
        ]);
    }

    /**
     * OpenAPI 3.0 Specification for developer integration
     */
    public function openApiSpec(): JsonResponse
    {
        return response()->json([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'DataHub Intelligence Platform API',
                'version' => '1.0.0',
                'description' => 'Enterprise REST API endpoints to query structured datasets, payloads, and metadata.',
            ],
            'servers' => [
                [
                    'url' => url('/api/v1'),
                    'description' => 'DataHub v1 API Gateway',
                ],
            ],
            'paths' => [
                '/datasets' => [
                    'get' => [
                        'summary' => 'List all datasets',
                        'parameters' => [
                            ['name' => 'category', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
                            ['name' => 'search', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
                            ['name' => 'per_page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'default' => 15]],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Successful dataset catalog response'],
                        ],
                    ],
                ],
                '/datasets/{id}' => [
                    'get' => [
                        'summary' => 'Get dataset metadata',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Dataset details and quality profile'],
                            '404' => ['description' => 'Dataset not found'],
                        ],
                    ],
                ],
                '/datasets/{id}/data' => [
                    'get' => [
                        'summary' => 'Query paginated JSON payload records',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                            ['name' => 'page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'default' => 1]],
                            ['name' => 'per_page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'default' => 50]],
                            ['name' => 'search', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Paginated dataset records'],
                            '404' => ['description' => 'Dataset not found'],
                        ],
                    ],
                ],
                '/tokens/create' => [
                    'post' => [
                        'summary' => 'Generate Sanctum API Bearer Token',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['email'],
                                        'properties' => [
                                            'email' => ['type' => 'string', 'format' => 'email'],
                                            'token_name' => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Bearer token issued'],
                            '404' => ['description' => 'User not found'],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
