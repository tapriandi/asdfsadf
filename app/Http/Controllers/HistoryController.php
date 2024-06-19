<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = History::query();
        if ($request->has('search')) {
            $query->where('symbol', 'like', '%' . $request->search . '%');
        }

        $coins = $query->select('symbol', DB::raw('count(*) as total_coin'))
            ->groupBy('symbol')
            ->get();

        return view('history', compact('coins'));
    }

    public function show($coin)
    {
        $dataCoin = History::where('symbol', $coin)->get();
        return view('historyDetail', compact('dataCoin', 'coin'));
    }
}
