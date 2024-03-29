<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\API\Admin\AdminBaseController;
use App\Http\Resources\HomestayResource;
use App\Mail\MailHomestayApprove;
use App\Models\Homestay;
use App\Models\Location;
use App\Models\User;
use App\Repositories\Homestay\HomestayRepositoryInterface;
use App\Repositories\Location\LocationRepositoryInterface;
use App\Validators\InputValidator;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class HomestayController extends AdminBaseController
{

    protected $homestayRepo;

    protected $locationRepo;

    public function __construct(
        HomestayRepositoryInterface $homestayRepo,
        LocationRepositoryInterface $locationRepo
    )
    {
        $this->homestayRepo = $homestayRepo;
        $this->locationRepo = $locationRepo;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {  
        $homestays = Homestay::all();

        if ($request->has('_start')) {
            $homestay = Homestay::offset($request->_start);
            if ($request->has('_end')) {
                $homestay->limit((int) $request->_end - (int) $request->_start); 
            }
            return $this->sendResponse(HomestayResource::collection($homestay->get()), true, $homestays->count());

        }
        
        return $this->sendResponse(HomestayResource::collection($homestays), true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = InputValidator::storeHomestayPharse1($request);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $data = $this->storePharse1($request);

        return $this->sendResponse(new HomestayResource($data));
    }

    public function storePharse1($request) {
        try{

            $locationData = [
                'province_id' => $request->province_id,
                'district_id' => $request->district_id,
                'ward_id' => $request->ward_id,
            ];
            $locationResult = $this->locationRepo->create($locationData);

            $homestayData = [
                'name' => $request->name,
                'type_id' => $request->type_id,
                'location' => $request->location,
                'location_id' => $locationResult->id,
                'user_id' => $request->user()->id,
                'des' => $request->des
            ];
            return $this->homestayRepo->create($homestayData);

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $homestayResult = $this->homestayRepo->find($id);
        $locationResult = $this->locationRepo->find($homestayResult->location_id);
        $homestayResult->location_info = $locationResult;
        $homestayResult->count_num_vote = count(json_decode($homestayResult->voting, true));

        return response()->json($homestayResult);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if ($request->user()['user_type'] == User::ADMIN) {
            $record = $this->homestayRepo->find($id);
            if ($record->approved == 2 && $request->approved != 2) {
                $this->sendMail($record, $request->approved);
            }
    
            $homestay = $this->homestayRepo->update($id, $request->all());
            return $this->sendResponse(new HomestayResource($homestay));
        }   

        $validator = InputValidator::storeHomestayPharse1($request);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $record = $this->homestayRepo->find($id);

        if (is_null($record)) {
            return $this->sendError('Record not found.');
        }
        

        $homestay = $this->homestayRepo->update($id, $request->all());
        $location = $this->locationRepo->update($homestay->location_id, $request->all());

        if($record === false) {
            $$record = ["status" => false];
        }

        return $this->sendResponse(new HomestayResource($record));
    }

    protected function sendMail($homestay, $status)
    {
        $email = User::find($homestay->user_id);
        return Mail::to($email)->send(new MailHomestayApprove($status, $homestay));
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function suggested()
    {
        $homestay = Homestay::inRandomOrder()->limit(6)->get();
        return response()->json(['homestay' => $homestay]);
    }

    public function getByPlace(Request $request)
    {
        if ($request->has('id')) {
            $id = $request->get('id');
            if ($request->get('type') == 'district') {
                $locations = $this->locationRepo->findManyBy('district_id', $id);
            } else {
                $locations = $this->locationRepo->findManyBy('province_id', $id);
            }
            
            $hs = $this->homestayRepo->findByLocation($locations->pluck('id'));
            // $ids = $hs->pluck('id');
            $total = $hs->count();
            
            $result = $this->rankingResult($hs);
            if ($request->has('_start')) {
                // $hs = $hs->slice((int) $request->_start, (int) $request->_end - (int) $request->_start)->values();
                $hs = array_slice($result, (int) $request->_start, (int) $request->_end - (int) $request->_start);
            }
            // $ids = $hs->pluck('id');
            $ids = array_column($hs, 'id');
            
            return response()->json([
                'ids' => $ids,
                'hs' => $hs,
                'total' => $total
            ]);
        }
        return response()->json(['status' => false]);
    }

    protected function rankingResult($hs)
    {
        foreach($hs as $key => $one) {
            $hs[$key]['count_order'] = $one->orders()->count();
        }

        $hs = $hs->toArray();
        // dd($hs);

        usort($hs, function ($a, $b) {
            if ($a['count_order'] == $b['count_order']) {
                return 0;
            }
            return ($a['count_order'] > $b['count_order']) ? -1 : 1;
        });

        return $hs;
    }

    public function filterHsType(Request $request)
    {
        if ($request->has('ids')) {
            // $hs = Homestay::whereIn('homestays.id', $request->ids)->join('homestay_prices', 'homestay_prices.homestay_id', '=', 'homestays.id')->with(['images', 'utilities', 'type', 'prices'])->selectRaw('homestays.*')->orderBy('price_normal', $type)->get();
            
            $hs = $this->homestayRepo->filterHsType($request->ids, $request->type);
            return response()->json([
                'ids' => $hs->pluck('id'),
                'hs' => $hs
            ]);
        }
        return response()->json(['status' => false]);
    }

    public function searchFilterSort(Request $request)
    {
        if (!$request->has('ids')) {
            return response()->json(['status' => false]);
        }

        $sortType = "";
        if ($request->sort_type == "1") {
            $sortType = 'desc';
        } else if ($request->sort_type == "2"){
            $sortType = 'asc';
        }

        $basic = Homestay::whereIn('homestays.id', $request->ids)
        ->with(['images', 'utilities', 'type', 'prices']);
        
        if (!empty($request->idUtils)) {
            $basic->join('homestay_utilities', 'homestays.id', '=', 'homestay_utilities.homestay_id')
            ->join('homestay_utility_types','homestay_utility_types.id', '=','homestay_utilities.utility_id')
            ->whereIn('homestay_utility_types.id', $request->idUtils);
        }
        
        if ($request->hs_type != "0") {
            $basic->where('type_id', (int) $request->hs_type);
        }

        $hs = $basic->selectRaw('homestays.*')->distinct()->get();

        if (!empty($sortType)) {
            $hs = $basic->join('homestay_prices', 'homestay_prices.homestay_id', '=', 'homestays.id')
            ->select('homestay_prices.price_normal as price_normal','homestays.*')
            ->orderBy('price_normal', $sortType)->get();
        } else {
            $hs = $basic->selectRaw('homestays.*')->distinct()->get();
        }

        return response()->json([
            'ids' => $hs->pluck('id'),
            'hs' => $hs
        ]);
    

    }

    public function requestApprove(Request $request)
    {
        $result = $this->homestayRepo->update($request->homestay_id, [
            'approved' => Homestay::REQUEST_APPROVE,
            'request_approve_at' => Carbon::now()
        ]);
        return response()->json([
            'result' => $result,
        ]);
        
    }

    public function getByIds(Request $request)
    {
        $result = Homestay::whereIn('id', $request->ids)->get();
        return response()->json([
            'result' => $result,
        ]);
        
    }

    public function ratingHomestay(Request $request)
    {
        if (!$request->user()) {
            return;
        }
        $hs = Homestay::find($request->id);
        // $current = json_decode($hs->voting);
        if (empty($hs->voting)) {
            $currentList = [];
        } else {
            $currentList = json_decode($hs->voting, true);
        }

        $currentList[$request->user()->id] = $request->voting;
        if(count($currentList)) {
            $hs->average_star = array_sum($currentList)/count($currentList);
        }
        $hs->voting = $currentList;

        return response()->json([
            'result' => $hs->save(),
        ]);
    }
}
