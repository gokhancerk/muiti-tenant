<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectsController extends Controller
{
    public function index(): JsonResponse {
        // DİKKAT: Burada where('tenant_id') yok. 
        // Global Scope bunu veritabanı seviyesinde kendisi enjekte eder.
        // A firması istek atarsa sadece A'nın projeleri döner.
        $projects = Project::all();

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        // DİKKAT: tenant_id manuel olarak request'ten alınmıyor veya kaydedilmiyor.
        // Model'in creating event'i, bunu otomatik olarak TenantManager'dan alıp ekler.
        $project = Project::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return response()->json($project, 201);
    }
}
