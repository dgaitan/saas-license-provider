<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Artisan;

/**
 * Controller for handling API documentation
 */
class DocumentationController extends Controller
{
    /**
     * Display the API documentation
     */
    public function index(): View
    {
        return view('scramble::docs');
    }

    /**
     * Get the OpenAPI specification JSON
     */
    public function specification()
    {
        $specPath = storage_path('app/scramble/api.json');

        if (!file_exists($specPath)) {
            // Generate the specification if it doesn't exist
            Artisan::call('scramble:generate');
        }

        return response()->file($specPath);
    }
}
