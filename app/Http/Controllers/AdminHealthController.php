<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class AdminHealthController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // Simple Health Check logic
        $status = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
            'server_time' => now()->toDateTimeString(),
        ];
        
        $overall = collect($status)->every(fn($val) => $val === true || is_string($val)) ? 'healthy' : 'degraded';

        return response()->json([
            'status' => $overall,
            'checks' => $status
        ]);
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkStorage()
    {
        try {
            Storage::disk('local')->put('health_check_temp', 'ok');
            Storage::disk('local')->delete('health_check_temp');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkCache()
    {
        try {
            Cache::put('health_check', 'ok', 10);
            return Cache::get('health_check') === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }
}
