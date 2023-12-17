<?php

namespace App\Http\Controllers;
use App\Models\ActiveToken;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Models\checkin;
use App\Models\Folder;
use App\Models\belongtogroup;
use App\Models\upload;
use App\Models\Queue;
use App\Models\group;

use App\Models\user;

class book extends Controller
{
    public function checkin(Request $req){

        $token=json_decode(base64_decode($req->header('token')));
        $tokendb=ActiveToken::where('token',$req->header('token'))->first();
        $user=user::where('id',$token->user_id)->first();
        $Q=Queue::where('file_id',$req->file_id)->first();

        if(!$Q)     return response()->json(["message"=>"Forbidden"],405);

        else{
            //$Q->delete();

            //dd($Q->User_id);
            if($Q->User_id == $user->id){
                $checknew=new checkin();
                $checknew->token_id=$tokendb->id;
                $checknew->file_id=$req->file_id;
                $checknew->save();
                $Q->delete();
                return response()->json(["messsage" => "File Locked Successfully"],200);
                //return redirect('api/file/get');
            }
            else{
                $Q->delete();

                //wait or delete

                return response()->json(["message"=>"This file isn't currerntly 00available"], 404);
            }
        }
    }
    public function createFile(Request $request){
        ////creating a new file
        /*
            required:
                Folder Name if exist or creating a new folder if not via redirect
                new File Name
                the new file must pelong to a specific user-id


        */
        // checking if the folder exists
        $token=json_decode(base64_decode($request->header('token')));

        $folder=Folder::where('Folder',$request->Folder_name)->first();
        if(!$folder){
            $folder=new Folder();
            $folder->Folder=$request->Folder_name;
            //group
            $folder->save();
        }
        //Checking if the File exists in the specific folder
        $file=upload::where('name',$request->fileName)->where('Folder_id',$folder->id)->first();

        if($file)       return response()->json(["message"=>"File is already exists"], 400);
        //Checking if the Group dose Exist
        $group=group::where('id',$request->group_id)->first();

        if(!$group)     return response()->json(["message"=>"Invalid Group ID"], 400);
        //Checking if the user is a member of this group
        $belong=belongtogroup::where('group_id',$group->id)->where('user_id',$token->user_id)->first();

        if(!$belong)      return response()->json(["message" => "you are not a member of this group"], 401);

//        try and catch
        $file=new Upload();
        $file->name=$request->fileName;
        $file->Folder_id=$folder->id;
        $file->owner_id=$token->user_id;
        $file->group_id=$request->group_id;
        $file->File_Path='uploads\\'.$request->fileName;
        Storage::put('uploads\\'.$request->fileName.".txt", "V 1.0 \n");
        $file->save();

        return response()->json(
                        ["message"=>"File Created succesfuly",
                         "path" => $file->File_Path
                        ],
                          200);

    }

    public function getFile(Request $request){
        $token=json_decode(base64_decode($request->header('token')));

        $folder=Folder::where('Folder',$request->Folder_name)->first();
        if(!$folder){
            return response()->json(["message" => "Invalid Folder Name"], 404);
        }

        // Checking If the required File Exists

        $file=upload::where('name',$request->fileName)->where('Folder_id',$folder->id)->first();

        if(!$file)       return response()->json(["message"=>"In Vlaid"], 404);
        //Checking if the Group dose Exist

        $group=group::where('id',$file->group_id)->first();

        if(!$group)     return response()->json(["message"=>"Invalid Group ID"], 400);
        //Checking if the user is a member of this group
        $belongto=belongtogroup::where('group_id',$group->id)->where('user_id',$token->user_id)->first();

        if(!$belongto)      return response()->json(["message" => "you are not a member of this group"], 401);

        $filepath='uploads\\'.$request->fileName.".txt";
        $file=Storage::get($filepath);
        Storage::move($filepath, 'public/');

        return Response::make($file, 200);
        }

    public function replace(Request $request){
        // checkin
        // replace
    }

}
