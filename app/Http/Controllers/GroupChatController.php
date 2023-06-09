<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupChat;
use Illuminate\Http\Request;
use App\Events\GroupChatEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Events\FileAddGroupChatEvent;
use App\Events\GroupChatMessageDelete;
use App\Events\GroupMessageUpdateEvent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GroupChatController extends Controller
{
    public function index(){
        $user=User::find(Auth::id());
        $count = DB::table('notifications')->where('notifiable_id', Auth::id())->where('read_at', Null)->count();

        $groups=Group::where('creator_id',Auth::id())->get();
        $join_groups=DB::table('group_members')->where('user_id',Auth::id())->pluck('group_id')->toArray();

        $other_groups=Group::whereIn('id',$join_groups)->get();

        return view('frontend.group.groupChat',compact('groups','other_groups','user','count'));
    }

    public function chatStore(Request $request){
        $data=$request->all();
        // dd($data);
        $sender_id=Auth::id();
       $chatGroup= GroupChat::create([
           'group_id'=>$data['group_id'],
           'message'=>$data['message'],
           'sender_id'=>$sender_id,
        ]);


            if(array_key_exists('file',$data)){
                foreach ($data['file'] as $key => $file) {

                    $size=$file->getSize()/1024/1024;
                    $fileName=$file->getClientoriginalName();
                    $exploaded=explode('.',$fileName);
                    $extension=$exploaded[count($exploaded)-1];
                    // dd($extension);
                    if($extension=="mp4"){
                        $chatGroup->addMedia($file)->toMediaCollection('group_chat_video');
                    }
                    if($extension=="pdf"){
                        $chatGroup->addMedia($file)->toMediaCollection('group_chat_pdf');
                    }
                    if($extension=="png" || $extension=="jpg" || $extension=="jpeg" || $extension=="gif"){

                        $chatGroup->addMedia($file)->toMediaCollection('group_chat_image');
                    }
                }


        }
//
    //   $image=$chatGroup->hasMedia('group_chat_image') ? $chatGroup->getMedia('group_chat_image'):'';
    //   $video=$chatGroup->hasMedia('group_chat_video') ? $chatGroup->getMedia('group_chat_video'):'';
    //   $pdf=$chatGroup->hasMedia('group_chat_pdf') ? $chatGroup->getMedia('group_chat_pdf'):'';

    //   $img_arr=[];
    //     if($image){
    //         foreach($chatGroup->getMedia('group_chat_image') as $img){
    //         array_push($img_arr,$img->getUrl());
    //         }
    //     }
    //     $video_arr=[];
    //     if($video){
    //         foreach($chatGroup->getMedia('group_chat_video') as $video){
    //         array_push($video_arr,$video->getUrl());
    //         }
    //     }
    //     $pdf_arr=[];
    //     if($pdf){
    //         foreach($chatGroup->getMedia('group_chat_pdf') as $pdf){
    //         array_push($pdf_arr,$pdf->getUrl());
    //         }

    //     }

//new method
            $img_arr = [];
            $video_arr = [];
            $pdf_arr = [];

            if ($chatGroup->hasMedia('group_chat_image')) {
                $imageMedia = $chatGroup->getMedia('group_chat_image');
                $img_arr = $imageMedia->map(fn($media) => $media->getUrl())->toArray();
            }

            if ($chatGroup->hasMedia('group_chat_video')) {
                $videoMedia = $chatGroup->getMedia('group_chat_video');
                $video_arr = $videoMedia->map(fn($media) => $media->getUrl())->toArray();
            }

            if ($chatGroup->hasMedia('group_chat_pdf')) {
                $pdfMedia = $chatGroup->getMedia('group_chat_pdf');
                $pdf_arr = $pdfMedia->map(fn($media) => $media->getUrl())->toArray();
            }

            // $fileUrls = array_merge($img_arr, $video_arr, $pdf_arr);
            // dd($fileUrls);


        $groupMessage=GroupChat::with('userInfo','media')->where(['group_id'=>$data['group_id'], 'message'=>$data['message'],'sender_id'=>$sender_id,])->first();

        $src=$groupMessage->userInfo->hasMedia('user_image') ? $groupMessage->userInfo->getMedia('user_image')[0]->getFullUrl(): asset('image/images.jpg');
        $time=$groupMessage->created_at->diffForHumans();


        // $src=$chatGroup->hasMedia('group_chat_image') ? $chatGroup->getMedia('group_chat_image')[0]->getFullUrl():'';


        event(new GroupChatEvent($groupMessage,$src,$time,$img_arr,$video_arr,$pdf_arr));

        return response()->json([
            'status'=>true,
            'view'=>view('frontend.group.component.messageSend',compact('groupMessage','src'))->render()
        ]);
    }
    public function loadGroupChatMessage(Request $request){
        $group=Group::find($request->group_id);
        $groupChats=GroupChat::with('userInfo','media')->where('group_id',$request->group_id)->get();


        $user=User::find($request->sender_id);

        return response()->json([
            'status'=>true,
            'group'=> $group,

            'view'=>view('frontend.group.component.groupMessageLoad',compact('groupChats','user','group'))->render()
        ]);
    }

    public function deleteMessage($id){
        // $chat=GroupChat::find($id);
        // if($chat){

        //     if($chat->hasMedia('group_chat_image') || $chat->hasMedia('group_chat_video') || $chat->hasMedia('group_chat_pdf') ){
        //         DB::table('media')->where('model_type','App\Models\GroupChat')->where('model_id',$chat->id)->delete();
        //     }
        //     $chat->delete();
        // }
        $chat = GroupChat::find($id);

        if ($chat) {
            $chat->clearMediaCollection(['group_chat_image', 'group_chat_video', 'group_chat_pdf']);
            $chat->delete();
        }

        event(new GroupChatMessageDelete($id));
        return response()->json([
            'status'=>true,
            'message'=>'Group message deleted successfully',
        ]);
    }

    public function updateMessage(Request $request){
        // dd($request->all());
        $data=$request->all();
        $groupMessage=GroupChat::find($data['id']);
        if($groupMessage){
            $groupMessage->update(['message'=>$data['message']]);
        }
        event(new GroupMessageUpdateEvent($groupMessage));
        return response()->json([
            'status'=>true,
            'groupMessage'=>$groupMessage,

        ]);

    }

    public function GroupImageSend(Request $request){
        $data=$request->all();
        // dd($data);
        $sender_id=$data['sender_id'];
        $groupChat=GroupChat::where('group_id',$data['group_id'])->first();
        $groupChat->addMedia($data['file'])->toMediaCollection('group_chat_image');
        // $sender_id=$data['sender_id'];
        $src=$groupChat->hasMedia('group_chat_image') ? $groupChat->getMedia('group_chat_image')[0]->getFullUrl():'';
        // dd($src);

        $src =DB::table('media')->where('model_type','App\Models\GroupChat')->where('model_id',$groupChat->id)->orderByDesc('created_at')->first();
      $path= url('/').'/storage/'.$src->id.'/'.$src->file_name;
        // dd($path);
        event(new FileAddGroupChatEvent($sender_id,$groupChat,$path));
        return response()->json([
             'status'=>true,
             'view'=>view('frontend.group.component.fileAdd',compact('groupChat','sender_id','path','src'))->render()
         ]);

    }
    public function deleteGroupChatImage(Request $request){
        dd($request->all());
    }

    public function showGroupPic(Request $request){
        // dd($request->all());
        $group=GroupChat::where('group_id',$request->group_id)->with('media')->first();
        // dd(count($group->getMedia('group_chat_image'))>0);
        // foreach ($group->getMedia('group_chat_image') as $key => $value) {
        //     dd($value->getUrl());
        // }

        if($group){
            return response()->json([
                'status'=>true,
                'view'=> view('frontend.group.component.showgallery',compact('group'))->render()
            ]);
        }


    }
    public function DeleteGroupImageFile(Request $request){
        $id=$request->id;

if ($id) {
    $media = Media::find($id);

    if ($media) {
        $media->delete();

        return response()->json([
            'status' => true,
            'message' => 'Success'
        ]);
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Media not found'
        ]);
    }
} else {
    return response()->json([
        'status' => false,
        'message' => 'Invalid ID'
    ]);
}
    }

}
