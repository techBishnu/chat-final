@if($groupOwner->creator_id)
<div id="owner_profile">
    <h5 id="text">This Group Owner: </h5><br>
    <span id="owner_name">{{ $groupOwner->user->name }}</span>
    <a href="{{ route('friend_list.profile',$groupOwner->creator_id) }}">
        <img id="img-view-member" src="{{ $groupOwner->user->hasMedia('user_image') ? $groupOwner->user->getMedia('user_image')[0]->getFullUrl():asset('image/images.jpg') }}" alt="">
    </a>
</div>

    @endif
    @if (Auth::id()!=$groupOwner->creator_id)
        <div class="leave_button">
            <a  id="leave_group_user" class="float-right leave_member" data-id={{ $groupOwner->id }}>Leave Group</a>
        </div>
        @else
        <div class="delete_button">
            <a  id="delete_group_user" class="float-right leave_member" data-id={{ $groupOwner->id}}>Delete Group</a>
        </div>
    @endif
<br>
<hr>
    Members:
    <br>


@foreach ($groupMembers as $groupMember)


<div class="memberShow">

    <a id="group_member" href="{{ route('friend_list.profile',$groupMember->user_id) }}">
    <img  id="img-view-memberOnly" src="{{ $groupMember->user->hasMedia('user_image') ? $groupMember->user->getMedia('user_image')[0]->getFullUrl():'' }}" alt="">
    <span id="owner_name">{{ $groupMember->user->name }}</span>
</a>
</div>

@endforeach
