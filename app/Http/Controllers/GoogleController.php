<?php

namespace App\Http\Controllers;

use App\GoogleAccount;
use Illuminate\Http\Request;
use App\Http\Resources\GoogleAccount as GoogleAccountResource;
use App\Http\Resources\GoogleAccountCollection;
use Illuminate\Support\Facades\DB;

class GoogleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
   {
        return new GoogleAccountCollection(GoogleAccount::all());
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
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       // return new GoogleAccountResource(GoogleAccount::whereUserId($id)->findOrFail('email'));

        $users = DB::table('google_account')->select('id', 'user_id', 'name', 'email', 'picture', 'access_token')
                                            ->where('user_id', $id)
                                            ->get();
        return $users;                                      
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {   
        $account = GoogleAccount::findOrFail($id);
       
        if($account)
        {
            $delete = $account->delete();
            if($delete)
            {
                echo 'id:'. $id .' is deleted Successfully';
            }
            return response()->json(null, 204);
        }
        else
        {
            echo"asdjsndajs";
            exit;
          
            return respondNotFound()->json(null, 200);
        }
    }
}
