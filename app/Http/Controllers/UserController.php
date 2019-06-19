<?php

namespace App\Http\Controllers;
use App\User;
use App\ActionItem;
use App\UserDetails;
use App\DiscussionPoints;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\ActionItemCollection;
use App\Http\Resources\UserDetailsCollection;
use App\Http\Resources\DiscussionPointsCollection;
use App\Http\Resources\ActionItem as ActionItemResource;
use App\Http\Resources\UserDetails as UserDetailsResource;
use App\Http\Resources\DiscussionPoints as DiscussionPointsResource;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new UserDetailsCollection(UserDetails::all());
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
        //      
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data   = new UserDetailsResource(UserDetails::findOrFail($id));

        $email  = User::where('id', $id)->value('email');

        if($email){
            return response()->json([compact('data', 'email'), 'profile' => 'profile found successfully'], 200);        
        }else{
            return response()->json(['profile' => 'profile not found'], 400);
        }
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
        //
    }
    
    public function showBlob($eventId)
    {        
            $discussPoints  = DB::table('discussion_points')->select('description')
                                                            ->where('meeting_id', $eventId)
                                                            ->get();
            
            $actionItems    = DB::table('action_item')->select('description')
                                                      ->where('meeting_id', $eventId)
                                                      ->get();
                                                      
            if(!($discussPoints->isEmpty()) && !($actionItems->isEmpty()))
            {
                return response()->json(['success'=> true, compact('discussPoints','actionItems')]);        
            }
            else
            {
                return response()->json(['Error' => 'data not available'], 201);         
            }
    }

    public function sendMeetDetails(Request $request)
    {
        $meeting_id         = $request->meeting_id;
        $actiondescription  = $request->action_item;
        $discussdescription = $request->discussion_points;
        $attendees          = $request->recipients;
        //$attendeename       = $request->attendeename;

        ActionItem::where('meeting_id', $meeting_id)->updateOrCreate(['meeting_id' => $meeting_id, 'description' => $actiondescription]);
        
        $action = ActionItem::where('meeting_id', $meeting_id)->value('description');

        DiscussionPoints::where('meeting_id', $meeting_id)->updateOrCreate(['meeting_id' => $meeting_id, 'description' => $discussdescription]);
        
        $discuss    = DiscussionPoints::where('meeting_id', $meeting_id)->value('description');

        $subject = "The Meeting Details are";
       
        Mail::send('email.attendee', ['action' => $action, 'discuss' => $discuss],
                    function($mail) use ($attendees, $action, $discuss, $subject){
                            $mail->subject($subject);
                            $mail->from('loganatan94@gmail.com', "QR Solutions");  
                            $mail->to($attendees);
                        });

        return response()->json(['success'=> true, 'message'=> 'Thanks for join the meeting...please update if any queries']);     
    }
}
