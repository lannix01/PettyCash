<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\BikeService;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Support\PettyAccess;

class BikeMasterController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = strtolower(trim((string) $request->query('status', '')));
        $sort = strtolower(trim((string) $request->query('sort', 'latest')));
        $allowedSorts = ['latest', 'oldest', 'plate_asc', 'plate_desc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'latest';
        }

        $bikesQuery = Bike::query()
            ->when($status !== '', fn ($query) => $query->where('status', Bike::normalizeStatus($status)))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('plate_no', 'like', '%' . $q . '%')
                        ->orWhere('model', 'like', '%' . $q . '%')
                        ->orWhere('status', 'like', '%' . $q . '%');
                });
            });

        match ($sort) {
            'oldest' => $bikesQuery->orderBy('id'),
            'plate_asc' => $bikesQuery->orderBy('plate_no')->orderByDesc('id'),
            'plate_desc' => $bikesQuery->orderByDesc('plate_no')->orderByDesc('id'),
            default => $bikesQuery->orderByDesc('id'),
        };

        $bikes = $bikesQuery->paginate(20)->withQueryString();
        $statusOptions = Bike::statusOptions();

        return view('pettycash::bikes_master.index', compact('bikes', 'statusOptions', 'q', 'status', 'sort'));
    }

    public function create()
    {
        $statusOptions = Bike::statusOptions();

        return view('pettycash::bikes_master.create', compact('statusOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plate_no' => ['required','string','max:50','unique:petty_bikes,plate_no'],
            'model' => ['nullable','string','max:120'],
            'status' => ['required','string','in:' . implode(',', array_keys(Bike::statusOptions()))],
        ]);

        $data['status'] = Bike::normalizeStatus((string) $data['status']);
        Bike::create($data);

        return redirect()->route('petty.bikes_master.index')->with('success', 'Bike added.');
    }

    public function edit(Bike $bike)
    {
        $statusOptions = Bike::statusOptions();

        return view('pettycash::bikes_master.edit', compact('bike', 'statusOptions'));
    }

    public function update(Request $request, Bike $bike)
    {
        $data = $request->validate([
            'plate_no' => ['required','string','max:50','unique:petty_bikes,plate_no,'.$bike->id],
            'model' => ['nullable','string','max:120'],
            'status' => ['required','string','in:' . implode(',', array_keys(Bike::statusOptions()))],
        ]);

        $data['status'] = Bike::normalizeStatus((string) $data['status']);
        $bike->update($data);

        return redirect()->route('petty.bikes_master.index')->with('success', 'Bike updated.');
    }

    public function destroy(Bike $bike)
    {
        abort_unless(PettyAccess::isAdmin(auth('petty')->user()), 403);

        $hasSpendings = Spending::query()
            ->where('type', 'bike')
            ->where('related_id', $bike->id)
            ->exists();
        $hasServices = BikeService::query()
            ->where('bike_id', $bike->id)
            ->exists();

        if ($hasSpendings || $hasServices) {
            $bike->status = Bike::STATUS_DISABLED;
            $bike->save();

            return redirect()->route('petty.bikes_master.index')->with(
                'success',
                'Bike has existing records, so it was disabled instead. Old spendings remain visible, but the bike will not appear in new spending selection.'
            );
        }

        $bike->delete();

        return redirect()->route('petty.bikes_master.index')->with('success', 'Bike deleted.');
    }
}
