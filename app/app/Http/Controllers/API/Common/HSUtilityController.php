<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Resources\HomestayUtilityResource;
use App\Repositories\HomestayUtility\HomestayUtilityRepositoryInterface;
use App\Validators\InputValidator;
use Illuminate\Http\Request;

class HSUtilityController extends BaseController
{
    protected $homestayUtilityRepo;

    public function __construct(
        HomestayUtilityRepositoryInterface $homestayUtilityRepo,
    )
    {
        $this->homestayUtilityRepo = $homestayUtilityRepo;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = $this->homestayUtilityRepo->getAll();
        return $this->sendResponse(HomestayUtilityResource::collection($data), true);
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $validator = InputValidator::homestayUtility($request);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $data = $this->homestayUtilityRepo->create($input);

        return $this->sendResponse(new HomestayUtilityResource($data));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $record = $this->homestayUtilityRepo->find($id);
        return $this->sendResponse(new HomestayUtilityResource($record));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = InputValidator::homestayUtility($request);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $record = $this->homestayUtilityRepo->find($id);

        if (is_null($record)) {
            return $this->sendError('Type not found.');
        }
        $record = $this->homestayUtilityRepo->update($id, $request->all());

        if($record === false) {
            $$record = ["status" => false];
        }

        return $this->sendResponse(new HomestayUtilityResource($record));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $result = $this->homestayUtilityRepo->delete($id);
        $response = ["status" => $result];
        return $this->sendResponse(new HomestayUtilityResource($response));
    }
}