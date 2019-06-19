<?php

namespace App\Http\Controllers;
use DB;
use App\User;
use GooglePeople;
use Carbon\Carbon;
use Socialite;
use Google_Client;
use Google_Service_People;
use App\GoogleAccount;
use Google_Service_Oauth2;
use Google_Service_Calendar;
use Illuminate\Http\Request;
use Google_Service_Analytics;
use Google_Service_Plus_Person;
use Google_Service_PeopleService;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;

class gCalendarController extends Controller
{
    protected $client;

    public function __construct()      
    {
        $client = new Google_Client();
        // $client->addScope(Google_Service_PeopleService::CONTACTS);
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
        $client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
        // $client->addScope(Google_Service_PeopleService::USER_EMAILS_READ);
        $client->setIncludeGrantedScopes(true);
        $client->setAuthConfig('client_secret.json');
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $client->setPrompt('consent');
        $guzzleClient   = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false)));
        $client->setHttpClient($guzzleClient);
        /*getClient*/
        $this->projectName      = "app-mom";
        $this->client           = $client;
        $this->jsonKeyFilePath  = 'client_secret.json';
        // $rurl                   = action('gCalendarController@googleClients');
        // $this->redirectUri      = $rurl;
        $this->tokenFile        = 'credential.json';
    }

    public function googleClients(Request $request)
    {
       $client = new Google_Client();
       $client->setApplicationName($this->projectName);
       $client->addScope(Google_Service_Calendar::CALENDAR);
       $client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
       $client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
       $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false)));
       $client->setHttpClient($guzzleClient);
       $client->setAuthConfig('client_secret.json');
       $credential  = $request->email;
       $user        = $request->user_id;
       $rurl        = action('gCalendarController@googleClients');
       $client->setRedirectUri($rurl);
       $client->setState($user);
       $client->setAccessType('offline');
       $client->setApprovalPrompt('force');
       $this->client    = $client;
       $google=DB::table('google_account')
       ->where('user_id', '=', $user)
       ->where('email', '=', $credential)
       ->count();

        if($google > 0)
        {
            $users = DB::table('google_account')->where('email', $credential)->select('id', 'email', 'access_token', 'refresh_token', 'expires_in')->get();
            //convert object into array
            for ($i = 0, $c = count($users); $i < $c; ++$i) 
            {
                $users[$i] = (array) $users[$i];
            }
            $client->setAccessToken($users[0]);
            if ($client->isAccessTokenExpired()) 
            {
                // save refresh token to some variable
                $refreshTokenSaved = $client->getRefreshToken();
               
                // update access token
                $client->fetchAccessTokenWithRefreshToken($refreshTokenSaved);

                // pass access token to some variable
                $accessTokenUpdated = $client->getAccessToken();
              
                // append refresh token
                $accessTokenUpdated['refresh_token'] = $refreshTokenSaved;
    
                //new access token
                $newAccessToken = $accessTokenUpdated['access_token'];
    
                //update into account table
                $oauthService   = new Google_Service_Oauth2($client);    
                $userInfo       = $oauthService->userinfo_v2_me->get();   
    
                $update = GoogleAccount::where('email', $userInfo->email)->update
                ([
                'access_token'  => $newAccessToken,
                'refresh_token' => $refreshTokenSaved
                ]);
            
                //Set the new acces token
                $accessToken = $refreshTokenSaved;
                $client->setAccessToken($accessToken);
               
                $service = new Google_Service_Calendar($client);   
                $calendarId = 'primary';
                $results = $service->events->listEvents($calendarId);               
                return $results->getItems();
                // // save to file
                // file_put_contents($tokenFile, 
                // json_encode($accessTokenUpdated));
            }
            $service = new Google_Service_Calendar($client);
            $calendarId = 'primary';
            $results = $service->events->listEvents($calendarId);
            return $results->getItems();
        }
        else 
        {  
            $rurl = action('gCalendarController@googleClients');
            $this->client->setRedirectUri($rurl);
            if (!isset($_GET['code'])) {    
                $auth_url = $this->client->createAuthUrl();
                $filtered_url = filter_var($auth_url, FILTER_SANITIZE_URL);
                return redirect($filtered_url);
            }
            else 
            {
                $this->client->authenticate($_GET['code']);   
            }    
        } 
        if (isset($_GET['code'])) 
        {    
            $accessToken    = $this->client->getAccessToken();
            $oauthService   = new Google_Service_Oauth2($client);    
            $userInfo       = $oauthService->userinfo_v2_me->get();                    
            $accountexist   = GoogleAccount::where('email', $userInfo->email)->first();
            $userexist      = GoogleAccount::where('user_id', $_GET['state'])->first();
            
            if(!$userexist == null && !$accountexist == null)
            {
                $account = GoogleAccount::where('email', $userInfo->email)->update
                ([
                'user_id'       => $_GET['state'],
                'picture'       => $userInfo->picture,
                'name'          => $userInfo->name,
                'access_token'  =>  $accessToken['access_token'],
                'refresh_token' =>  $accessToken['refresh_token'],
                'expires_in'    =>  $accessToken['expires_in']
                ]);   
            }  
            else
            {  
                $account = GoogleAccount::Create
                ([
                'user_id'       =>  $_GET['state'],
                'email'         =>  $userInfo->email,
                'picture'       =>  $userInfo->picture,
                'name'          =>  $userInfo->name,
                'access_token'  =>  $accessToken['access_token'],
                'refresh_token' =>  $accessToken['refresh_token'],
                'expires_in'    =>  $accessToken['expires_in']
                ]);
            }
            
            $client->setAccessToken($accessToken);
            $service = new Google_Service_Calendar($client);
            $calendarId = 'primary';
            $results = $service->events->listEvents($calendarId);
            return $results->getItems();  
            //return redirect(" http://localhost:4200/accountlist");
        }    
    }

    public function create()
    {
        return view('calendar.createEvent');
    }

    public function store(Request $request)
    {
        session_start();
        $startDateTime = $request->start_date;
        $endDateTime = $request->end_date;

        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $this->client->setAccessToken($_SESSION['access_token']);
            $service = new Google_Service_Calendar($this->client);

            $calendarId = 'primary';
            $event = new Google_Service_Calendar_Event([
                'summary' => $request->title,
                'description' => $request->description,
                'start' => ['dateTime' => $startDateTime],
                'end' => ['dateTime' => $endDateTime],
                'reminders' => ['useDefault' => true],
            ]);
            $results = $service->events->insert($calendarId, $event);
            if (!$results) {
                return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
            }
            return response()->json(['status' => 'success', 'message' => 'Event Created']);
        } else {
            return redirect()->route('oauthCallback');
        }
    }

    public function show($account, $eventId)
    {
        $users = DB::table('google_account')->where('email', $account)->select('id', 'email', 'access_token', 'refresh_token', 'expires_in')->get();
        //convert object into array
        for ($i = 0, $c = count($users); $i < $c; ++$i) {
            $users[$i] = (array) $users[$i];
        }
        $this->client->setAccessToken($users[0]);
        $service = new Google_Service_Calendar($this->client);
        $event = $service->events->get('primary', $eventId);

        if (!$event) 
        {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
        }
        return response()->json(['status' => 'success', 'data' => $event]);
    }

    public function update(Request $request, $eventId)
    {
        session_start();
        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $this->client->setAccessToken($_SESSION['access_token']);
            $service = new Google_Service_Calendar($this->client);

            $startDateTime = Carbon::parse($request->start_date)->toRfc3339String();

            $eventDuration = 30; //minutes

            if ($request->has('end_date')) {
                $endDateTime = Carbon::parse($request->end_date)->toRfc3339String();

            } else {
                $endDateTime = Carbon::parse($request->start_date)->addMinutes($eventDuration)->toRfc3339String();
            }

            // retrieve the event from the API.
            $event = $service->events->get('primary', $eventId);

            $event->setSummary($request->title);

            $event->setDescription($request->description);

            //start time
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($startDateTime);
            $event->setStart($start);

            //end time
            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($endDateTime);
            $event->setEnd($end);

            $updatedEvent = $service->events->update('primary', $event->getId(), $event);


            if (!$updatedEvent) {
                return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
            }
            return response()->json(['status' => 'success', 'data' => $updatedEvent]);

        } else {
            return redirect()->route('oauthCallback');
        }
    }

    public function destroy($eventId)
    {
        session_start();
        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $this->client->setAccessToken($_SESSION['access_token']);
            $service = new Google_Service_Calendar($this->client);

            $service->events->delete('primary', $eventId);

        } else {
            return redirect()->route('oauthCallback');
        }
    }

    /*
    public function showEvent($access_token, $eventId)
    {
        session_start();

        $_SESSION['access_token'] = $access_token;

        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $this->client->setAccessToken($_SESSION['access_token']);

            $service = new Google_Service_Calendar($this->client);
            $event = $service->events->get('primary', $eventId);

            if (!$event) {
                return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
            }
            return response()->json(['status' => 'success', 'data' => $event]);

        } else {
            return redirect()->route('oauthCallback');
        }
    }
    */

     /*
    public function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName($this->projectName);
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
        $client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
        $client->setAuthConfig($this->jsonKeyFilePath);
        $client->setRedirectUri($this->redirectUri);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');

       // Load previously authorized credentials from a file.
       if (file_exists($this->tokenFile)) 
        {
            $accessToken = json_decode(file_get_contents($this->tokenFile), true);
        } 
        else 
        {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));

        if (isset($_GET['code']))      
        {
            $authCode = $_GET['code'];
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            header('Location: ' . filter_var($this->redirectUri, FILTER_SANITIZE_URL));
            if(!file_exists(dirname($this->tokenFile))) 
            {
                mkdir(dirname($this->tokenFile), 0700, true);
            }

            file_put_contents($this->tokenFile, json_encode($accessToken));
        }
        else
        {
            exit('No code found');
        }
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if (!$client->isAccessTokenExpired()) {
            
            // save refresh token to some variable
            $refreshTokenSaved = $client->getRefreshToken();
           
            // update access token
            $client->fetchAccessTokenWithRefreshToken($refreshTokenSaved);

            // pass access token to some variable
            $accessTokenUpdated = $client->getAccessToken();

            // append refresh token
            $accessTokenUpdated['refresh_token'] = $refreshTokenSaved;

            //Set the new acces token
            $accessToken = $refreshTokenSaved;
            $client->setAccessToken($accessToken);

            // save to file
            file_put_contents($this->tokenFile, 
        json_encode($accessTokenUpdated));
        }
        return $client;
    }
    */

    /*
    public function getEvents($access_token)
    {
        if ($access_token) {
          
            $this->client->setAccessToken($access_token);
    
            $service = new Google_Service_Calendar($this->client);

            $calendarId = 'primary';

            $results = $service->events->listEvents($calendarId);
            //return $results->getItems();
            return response()->json($results->getItems());
        } 

        else {
            return redirect()->route('oauthCallback');
        }

    }
    */

    /*
    public function oauthCopy($user)
    {
        $rurl = action('gCalendarController@oauthCopy',[$user]);
       
        $this->client->setRedirectUri($rurl);
        if (!isset($_GET['code'])) {    
            
            $auth_url = $this->client->createAuthUrl();
            $filtered_url = filter_var($auth_url, FILTER_SANITIZE_URL);
            return redirect($filtered_url);
        }
        else {
        $this->client->authenticate($_GET['code']);
        
        $access = $this->client->getAccessToken();

        $this->client->setAccessToken($access);
        
        if (!$this->client->isAccessTokenExpired()) 
        {
            // save refresh token to some variable
            $refreshTokenSaved = $this->client->getRefreshToken();
            
            // update access token
            $this->client->fetchAccessTokenWithRefreshToken($refreshTokenSaved);
            
            // pass access token to some variable
            $accessTokenUpdated = $client->getAccessToken();
          
            // append refresh token
            $accessTokenUpdated['refresh_token'] = $refreshTokenSaved;

            //new access token
            $newAccessToken = $accessTokenUpdated['access_token'];

            //update into account table
            $oauthService   = new Google_Service_Oauth2($client);    
            $userInfo       = $oauthService->userinfo_v2_me->get();   

            $update = GoogleAccount::where('email', $userInfo->email)->update
            ([
            'access_token'  => $newAccessToken,
            'refresh_token' => $refreshTokenSaved
            ]);
        
            //Set the new acces token
            $accessToken = $refreshTokenSaved;
            $client->setAccessToken($accessToken);
           
            $service = new Google_Service_Calendar($client);

            $calendarId = 'primary';
    
            $results = $service->events->listEvents($calendarId);
                        
            return $results->getItems();
            // save to file
            file_put_contents($tokenFile, 
            json_encode($accessTokenUpdated));
        }

        $refresh        = $access['refresh_token'];
            
        $accessToken    = $access['access_token'];
            
        $expiry         = $access['expires_in'];
       
        // $inHours     = gmdate('H:i:s', $expiry);
        
        $oauthService   = new Google_Service_Oauth2($this->client);
        
        $userInfo       = $oauthService->userinfo_v2_me->get();
        
        $userexist      = GoogleAccount::where('email', $userInfo->email)->first();
        
        if($userexist === null)
        {  
            $account = GoogleAccount::Create([
                                    'user_id'       =>$user,
                                    'email'         => $userInfo->email,
                                    'picture'       => $userInfo->picture,
                                    'name'          => $userInfo->name,
                                    'access_token'  => $accessToken,
                                    'refresh_token' => $refresh,
                                    'expires_in'   => $expiry
                                     ]);
        }

        $account = GoogleAccount::where('email', $userInfo->email)->update([
                                    'user_id'       =>$user,
                                    'picture'       => $userInfo->picture,
                                    'name'          => $userInfo->name,
                                    'access_token'  => $accessToken,
                                    'refresh_token' => $refresh,
                                    'expires_in'    => $expiry
                                    ]);

        return redirect()->route('cal.edit', $accessToken);
        }

    }
    */

    /*
    public function oauth()
    {
        session_start();
       
        $rurl = action('gCalendarController@oauth');
        $this->client->setRedirectUri($rurl);
        if (!isset($_GET['code'])) {

            $auth_url = $this->client->createAuthUrl();
            $filtered_url = filter_var($auth_url, FILTER_SANITIZE_URL);
            return redirect($filtered_url);
        }
        else {
        $this->client->authenticate($_GET['code']);
        $data['access_token'] = $this->client->getAccessToken();
      
        $accessToken = $data['access_token'];

        $token       = $accessToken['access_token'];
       
        $oauthService = new Google_Service_Oauth2($this->client);
        $userInfo = $oauthService->userinfo_v2_me->get();
        
        $account = GoogleAccount::updateOrCreate(
            ['email' => $userInfo->email, 'picture' => $userInfo->picture],
            ['name' => $userInfo->name, 'access_token' => $token]
        );
        
        $google_token= json_decode($_SESSION['access_token']);
        $client->refreshToken($google_token->refresh_token);

            return redirect()->route('cal.index');
        }
    }
    */  

    /*
    public function index()
    {           
        session_start();
       
        $_SESSION['access_token'] = $access_token;

        if (isset($access_token['access_token']) && $access_token['access_token']) {
            $this->client->setAccessToken($access['access_token']);

            $service = new Google_Service_Calendar($this->client);

            $calendarId = 'primary';
            
            $results = $service->events->listEvents($calendarId);

            return $results->getItems();
            
            // return redirect('localhost:4200/accountlist');
        } 
        else {
            return redirect()->route('oauthCallback');
        }

    }
    */
   
    /*
    public function edit($accessToken)
    {   
        if ($accessToken) 
        {               
            $this->client->setAccessToken($accessToken);

            $service = new Google_Service_Calendar($this->client);

            $calendarId = 'primary';

            $results = $service->events->listEvents($calendarId);
                 
            return $results->getItems();

            // return redirect('localhost:4200/accountlist');

        } else {
            return redirect()->route('oauthCallbacks');
        }
    }
    */
    
    /*
    public function getClients($user, $credential = '')
    {
       $client = new Google_Client();
       $client->setApplicationName($this->projectName);
       $client->addScope(Google_Service_Calendar::CALENDAR);
       $client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
       $client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
       $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false)));
       $client->setHttpClient($guzzleClient);
       $client->setAuthConfig('client_secret.json');
       $rurl  = action('gCalendarController@getClients', [$user, $credential]);
       $client->setRedirectUri($rurl);
       $client->setAccessType('offline');
       $client->setApprovalPrompt('force');
       $tokenFile       = $credential;
       $this->client    = $client;

        // Load previously authorized credentials from a file.
        if (file_exists($tokenFile)) 
        {   
            $accessToken = json_decode(file_get_contents($tokenFile), true);
            $client->setAccessToken($accessToken);
            if (!$client->isAccessTokenExpired()) 
            {
                // save refresh token to some variable
                $refreshTokenSaved = $client->getRefreshToken();
               
                // update access token
                $client->fetchAccessTokenWithRefreshToken($refreshTokenSaved);

                // pass access token to some variable
                $accessTokenUpdated = $client->getAccessToken();
              
                // append refresh token
                $accessTokenUpdated['refresh_token'] = $refreshTokenSaved;
    
                //new access token
                $newAccessToken = $accessTokenUpdated['access_token'];
    
                //update into account table
                $oauthService   = new Google_Service_Oauth2($client);    
                $userInfo       = $oauthService->userinfo_v2_me->get();   
    
                $update = GoogleAccount::where('email', $userInfo->email)->update
                ([
                'access_token'  => $newAccessToken,
                'refresh_token' => $refreshTokenSaved
                ]);
            
                //Set the new acces token
                $accessToken = $refreshTokenSaved;
                $client->setAccessToken($accessToken);
               
                $service = new Google_Service_Calendar($client);   
                $calendarId = 'primary';
                $results = $service->events->listEvents($calendarId);               
                return $results->getItems();
                // save to file
                file_put_contents($tokenFile, 
                json_encode($accessTokenUpdated));
            }
            $service = new Google_Service_Calendar($client);
            $calendarId = 'primary';
            $results = $service->events->listEvents($calendarId);
            return $results->getItems();
        }
        else 
        {  
            $rurl = action('gCalendarController@getClients',[$user, $credential]);

            $this->client->setRedirectUri($rurl);
            if (!isset($_GET['code'])) {    
                
                $auth_url = $this->client->createAuthUrl();
                $filtered_url = filter_var($auth_url, FILTER_SANITIZE_URL);
                return redirect($filtered_url);
            }
            else 
            {
                $this->client->authenticate($_GET['code']);   
            }    
        }  
        if (isset($_GET['code'])) 
        {    
            $accessToken = $this->client->getAccessToken();
             
            $oauthService   = new Google_Service_Oauth2($client);    
            $userInfo       = $oauthService->userinfo_v2_me->get();   
           
            if($tokenFile == '') 
            {          
                $emailFile      = $userInfo->email;
                fopen($emailFile, 'w') or die("Can't create file");
                
                $refresh        = $accessToken['refresh_token'];          
                $access_token   = $accessToken['access_token'];            
                $expiry         = $accessToken['expires_in'];                
                $userexist      = GoogleAccount::where('email', $userInfo->email)->first();
        
                if($userexist === null)
                {    
                    $account = GoogleAccount::Create
                    ([
                    'user_id'       =>  $user,
                    'email'         =>  $userInfo->email,
                    'picture'       =>  $userInfo->picture,
                    'name'          =>  $userInfo->name,
                    'access_token'  =>  $access_token,
                    'refresh_token' =>  $refresh,
                    'expires_in'    =>  $expiry
                    ]);
                }
        
                $account = GoogleAccount::where('email', $userInfo->email)->update
                ([
                'user_id'       => $user,
                'picture'       => $userInfo->picture,
                'name'          => $userInfo->name,
                'access_token'  => $access_token,
                'refresh_token' => $refresh,
                'expires_in'    => $expiry
                ]);
                
                file_put_contents($emailFile, json_encode($accessToken));
            }
            $client->setAccessToken($accessToken);
            $service = new Google_Service_Calendar($client);
            $calendarId = 'primary';
            $results = $service->events->listEvents($calendarId);
            return $results->getItems();  
        }    
    }
    */
}
